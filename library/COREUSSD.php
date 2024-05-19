<?php

class COREUSSD extends Palmkash {

    function __construct() {
        parent::__construct();
    }


    function VerifyMobile($params) {
             return $this->db->SelectData("SELECT * FROM palm_test_account WHERE telephone_number='".$params['msisdn']."' AND status='active' ");
    }

    function ManageRequestSession($params) {
        //Check If Session Already Exists:
        //$res = $this->SessionExists($params);
        $response =array();
        $res = $this->GetSessionRecords($params['session_key']);
        $this->log->ExeLog($params, "COREUSSD::ManageRequestSession retrieved session records ...".var_export($res,true), 2);
       
        if(empty($res)){
                   //Register Session On DB.
               $postData['session_date'] = date('Y-m-d G:i:s');
               $postData['session_id'] = $params['sessionId'];
               $postData['telephone_number'] = $params['msisdn'];
                  // $this->db->InsertData("palm_log_session_data", $postData);
                     //print_r($postData);die();
       $this->log->ExeLog($params, "COREUSSD::ManageRequestSession records to store records ...".var_export($postData,true), 2);
                 
               $this->redis->StoreArrayRecords($params['session_key'], $postData);
          $response['status']=0; 
          return $response;  
        } else {
          $response['status']=1; 
          if(isset($res['session_language_pref'])){
          $response['session_language_pref']= $res['session_language_pref']; 
          }
          return $response;
        }
    }

    function MenuOptionHandler($params, $status) {
      if(ENVIRONMENT=='dev'){
     $access = $this->VerifyMobile($params);
       if (empty($access)==true) {
         $call_fxn[0]['ussd_new_state'] =  35;

        $response = $this->DisplayMenu($params, $call_fxn);
        return $response;
       }
        }
        if ($status == 0) {
            //No State Exists

                $call_fxn[0]['ussd_new_state'] = 1;

            $this->log->ExeLog($params, "COREUSSD::MenuOptionHandler Initial Session Call. Returned New State ..." . $call_fxn[0]['ussd_new_state'], 2);
        } else {
          $res =  $this->GetCurrentLogstate($params);         
            $state = $this->GetCurrentState($params);
            $state  = $state[0];
            $state['current_state']  = $res['current_state'];
            $this->log->ExeLog($params, "COREUSSD::MenuOptionHandler GetCurrentState. Returned New State ...".var_export($state,true), 2);

            if ($state['state_type'] == 'input') {
                $this->StoreInputValues($params, $state);
                $choice = '-1';
            } else {
              $this->StoreInputValues($params, $state);
                $choice = $params['subscriberInput'];
            }
            $this->log->ExeLog($params, "COREUSSD::MenuOptionHandler Serching For Option From " .
                    $res['current_state'] . ' When User Choice Is ' . $choice, 2);
            $call_fxn = $this->GetNextState($res['current_state'], $choice);
            $this->log->ExeLog($params, "COREUSSD::MenuOptionHandler::GetNextState Returned state " . var_export($call_fxn,true), 2);

            if(count($call_fxn)==0){
            //set it to previous state previous_state
      			$prev_state=$res['current_state'];
      			if($state['call_fxn_name']==''){
                   $call_fxn[0]['ussd_new_state'] =  $prev_state;
      			}else{ //if called prev, refer to main
                   $call_fxn[0]['ussd_new_state'] =  1;
      			}

            }


            $this->log->ExeLog($params, "COREUSSD::MenuOptionHandler Continued Session Returned Function To Call..." . $call_fxn[0]['ussd_new_state'], 2);
        }

        $response = $this->DisplayMenu($params, $call_fxn);
        return $response;
    }

    function DisplayMenu($params, $fxn_array) {

     $this->log->ExeLog($params, "COREUSSD::DisplayMenu next state " . var_export($fxn_array, true), 2);
    
        $this->OperationWatch($params, $fxn_array[0]['ussd_new_state']);

        $menu = $this->GetStateFull($fxn_array[0]['ussd_new_state']);
        $this->log->ExeLog($params, "COREUSSD::DisplayMenu GetStateFull Returning Array for the menu " . var_export($menu, true), 2);
        $response_array = $this->PrepareMenu($params, $menu);

        return $response_array;
    }

    function PrepareMenu($params, $menu) {

          $result = array();
        if ($menu[0]['fxn_call_flag'] == 1) {
            $this->log->ExeLog($params, "COREUSSD::DisplayMenu PrepareMenu Required to make remote function call to " . $menu[0]['call_fxn_name'], 2);
            $result = $this->{$menu[0]['call_fxn_name']}($params);

   //$this->log->ExeLog($params, "COREUSSD::External function call Call Result " . var_export($result, true), 2);
           if(isset($result['language'])){
            $params['session_language_pref']  = $result['language'];  
 //            $this->log->ExeLog($params, "COREUSSD::GetStateFull Normal Member language ".$params['session_language_pref']." and Menu text " . var_export($menu, true), 2);
          }else if(isset($result['error_code'])){
            $menu = $result['error_code'];

          }
            //$prepared_response = $this->ReplacePlaceHolders($params, $menu[0][$ln_text], $result);
          //  $resp['state'] = $menu[0]['state_indicator'];
          //  $resp['msg_response'] = $prepared_response;
        }else{
         // $this->log->ExeLog($params, "COREUSSD::PrepareMenu else params check language " . var_export($params, true), 2);

          $result=$params;
        }
        
        //$ln = $this->GetSessionLanguage($params);
        if (isset($params['session_language_pref'])) {
          $ln_text = 'text_' . $params['session_language_pref'];          
        } else {
          $ln_text = 'text_en';
        }
      //  $this->log->ExeLog($params, "COREUSSD::MenuArray Session Language Is " . $ln_text, 2);

        $this->log->ExeLog($params, "COREUSSD::DisplayMenu PrepareMenu Menu data ".var_export( $menu,true), 2);
        $prepared_response = $this->ReplacePlaceHolders($params, $menu[0][$ln_text], $result);
        $resp['state'] = $menu[0]['state_indicator'];
        $resp['msg_response'] = $prepared_response;

         $response = $this->MenuArray($params, $resp);
        return $response;

    }

    function MenuArray($params, $resp) {
        $response_array = array(
            'msisdn' => $params['msisdn'],
            'sessionid' => $params['sessionId'],
          //  'transactionid' => $params['transactionId'],
            'freeflow' => array(
                'freeflowState' => $resp['state']
            ),
            'applicationResponse' => $resp['msg_response'],
        );
        return $response_array;
    }

    function ReplacePlaceHolders($request, $text, $array) {
        $this->log->ExeLog($request, 'COREUSSD::ReplacePlaceHolders Fired for ' . $text . ' With Data '
                . var_export($array, true), 2);
			 $new_text = $text;

			if(isset($array['start_station'])){
	          $new_text = str_replace("[ORIGIN]", $array['start_station'], $new_text);
			}
			if(isset($array['end_station'])){
	          $new_text = str_replace("[DESTINATION]", $array['end_station'], $new_text);
			}

			if(isset($array['time_available'])){
	          $new_text = str_replace("[TIMES]", $array['time_available'], $new_text);
			}

			if(isset($array['route_name'])){
	          $new_text = str_replace("[ROUTE_NAME]", $array['route_name'], $new_text);
			}

			if(isset($array['route_time'])){
	          $new_text = str_replace("[ROUTE_TIME]", $array['route_time'], $new_text);
			}

			if(isset($array['tickets'])){
	          $new_text = str_replace("[TICKETS]", $array['tickets'], $new_text);
			}

			if(isset($array['amount'])){
	          $new_text = str_replace("[AMOUNT]", $array['amount'], $new_text);
			}

			if(isset($array['student_name'])){
	          $new_text = str_replace("[NAME]", $array['student_name'], $new_text);
			}

			if(isset($array['school'])){
	          $new_text = str_replace("[SCHOOL]", $array['school'], $new_text);
			}

			if(isset($array['account_number'])){
	          $new_text = str_replace("[STUDENT_ACCOUNT]", $array['account_number'], $new_text);
			}
			if(isset($array['events'])){
	          $new_text = str_replace("[EVENTS]", $array['events'], $new_text);
			}
			if(isset($array['event'])){
	          $new_text = str_replace("[EVENT]", $array['event'], $new_text);
			}
			if(isset($array['no_tickets'])){
	          $new_text = str_replace("[TICKETS]", $array['no_tickets'], $new_text);
			}
			if(isset($array['ticket_class'])){
	          $new_text = str_replace("[TICKET_CLASS]", $array['ticket_class'], $new_text);
			}
			if(isset($array['venue'])){
	          $new_text = str_replace("[VENUE]", $array['venue'], $new_text);
			}
			if(isset($array['total_amount'])){
	          $new_text = str_replace("[TOTAL]", $array['total_amount'], $new_text);
			}
			if(isset($array['charge'])){
	          $new_text = str_replace("[CHARGE]", $array['charge'], $new_text);
			}
               if(isset($array['options'])){
	          $new_text = str_replace("[OPTIONS]", $array['options'], $new_text);
			}
                if(isset($array['quantity'])){
	          $new_text = str_replace("[QUANTITY]", $array['quantity'], $new_text);
			}
               if(isset($array['name'])){
                    $new_text = str_replace("[NAME]", $array['name'], $new_text);
               }              
                if(isset($array['size'])){
	          $new_text = str_replace("[SIZE]", $array['size'], $new_text);
			}


        return $new_text;
    }

}
