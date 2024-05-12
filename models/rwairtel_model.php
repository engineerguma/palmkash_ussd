<?php

class RWAirtel_Model extends COREUSSD {

    function __construct() {
        parent::__construct();
    }

    function RequestHandler($xml_post, $params) {

        $status = $this->ManageRequestSession($params);
        $this->log->ExeLog($params, 'RWAirtel_Model::Handler ManageRequestSession Returning Status ' . $status, 2);
        if(isset($status['session_language_pref'])){ $params['session_language_pref']= $status['session_language_pref']; }
            $param_array = explode("*", $params['subscriberInput']);
          $registered = $this->IsRegistered($params);   // Added temporariry
            if(count($param_array)>1&&$registered==1){
            //$this->CheckRegistration($params);   // Added temporariry
             $response = $this->BreakDownCodes($params,$param_array,$status['status']);
                 //print_r(count($return));die();
          }else{
                    //   print_r("No long code");die();
         $response = $this->MenuOptionHandler($params, $status['status']);

          }

		    $response['sessionId']=$params['sessionId'];
    		if(empty($response['applicationResponse'])){
    		$response['applicationResponse']='Dear Customer, Request due to communication Problem, try again Later';
    		}

        $this->log->ExeLog($params, 'RWAirtel_Model::Handler Returning XML Response ' . var_export($response,true), 3);

        return $response;
    }




          function BreakDownCodes($params,$inputString,$status){


            if($inputString[1]==1){ //shool
         //print_r($inputString[1]);die();
            $response = $this->MenuOptionHandler($params, $status);

            }else if($inputString[1]==2){ //Bus
         //print_r($inputString[2]);die();
           $response = $this->MenuOptionHandler($params, $status);

            } else if($inputString[1]==3){ //Events
            // print_r($inputString[1]);die();
             //go to get events
             $fxn_array[0]['ussd_new_state']=1;
             $this->OperationWatch($params, 1);
             $params['subscriberInput'] = '3';
              $state = $this->GetCurrentState($params);
              //   print_r($state);die();
              $this->StoreInputValues($params, $state[0]);
              $call_fxn = $this->GetNextState($state[0]['current_state'], $params['subscriberInput']);
              // print_r($call_fxn);die();
              $this->OperationWatch($params, $call_fxn[0]['ussd_new_state']);
                        //$params['subscriberInput'] = 'event_category';
          $this->log->ExeLog($params, 'RWAirtel_Model::Inside Option 3', 2);

              // print_r($menu);die();
            $result = $this->ProcessGetEventsCategories($params);
          //  print_r($result);die();
           if(isset($result['events'])&&isset($inputString[2])){ //
               $params['subscriberInput'] = $inputString[2];
               $state = $this->GetCurrentState($params);
                 //print_r($state);die();
               $this->StoreInputValues($params, $state[0]);
           $call_fxn = $this->GetNextState($state[0]['current_state'], -1);
           //print_r($call_fxn);die();
            $result = $this->ProcessCategoryEvents($params);
            //print_r($result);die();
          $this->log->ExeLog($params, 'RWAirtel_Model::ProcessCategoryEvents Inside Option 3 categories ' . var_export($result, true), 2);
    //////////////////////////////////////////////////////////////////////////////////////////
              $this->OperationWatch($params, $call_fxn[0]['ussd_new_state']);
              if(isset($result['error_code'])){
                $menu = $result['error_code'];

              }else{
                $menu = $this->GetStateFull($call_fxn[0]['ussd_new_state']);
              }
              $ln = $this->GetSessionLanguage($params);
              if ($ln[0]['session_language_pref'] == '') {
                  $ln_text = 'text_en';
              } else {
                  $ln_text = 'text_' . $ln[0]['session_language_pref'];
               }
              $this->log->ExeLog($params, 'RWAirtel_Model::Inside Option 3 categories ' . var_export($result, true), 2);
              $prepared_response = $this->ReplacePlaceHolders($params, $menu[0][$ln_text], $result);
              $resp['state'] = $menu[0]['state_indicator'];
              $resp['msg_response'] = $prepared_response;
              $response = $this->MenuArray($params, $resp);

              //print_r($response);die();
              return  $response;


          ///////////////////////////////////////////////////////
           }else if(isset($result['events'])){
              $menu = $this->GetStateFull($call_fxn[0]['ussd_new_state']);
              $ln = $this->GetSessionLanguage($params);
              if ($ln[0]['session_language_pref'] == '') {
                  $ln_text = 'text_en';
              } else {
                  $ln_text = 'text_' . $ln[0]['session_language_pref'];
               }
            $this->log->ExeLog($params, 'RWAirtel_Model::Inside Option 3 categories ' . var_export($result, true), 2);
            $prepared_response = $this->ReplacePlaceHolders($params, $menu[0][$ln_text], $result);
            $resp['state'] = $menu[0]['state_indicator'];
            $resp['msg_response'] = $prepared_response;
             $response = $this->MenuArray($params, $resp);

          //print_r($response);die();
             return  $response;
            }


          }else{

            $response = $this->MenuOptionHandler($params, $status);

          }

          return $response;
        }



    	function FormatRequest($query){
    	   $request_r=urldecode($query);

    	   $array = explode("&", $request_r);
    	  $transdata=array();

    		foreach ($array as $item) {
    		$values=explode("=", $item);
    		   $fkey = strtolower($values[0]);
    		 $transdata["$fkey"] = $values[1];

    		}
        //     print_r($transdata);die();
    	 $reqdata=$this->format->Standardize($transdata);

    	return $reqdata;
    	}


}
