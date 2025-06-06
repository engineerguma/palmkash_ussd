<?php

class Mtnrwanda_Model extends COREUSSD {

    function __construct() {
        parent::__construct();
    }

    function RequestHandler($xml_post, $params) {
      $response = array();
     if($this->ValidateAllowedCharacters($params['subscriberInput'])){
         $status = $this->ManageRequestSession($params);
   //     $this->log->ExeLog($params, 'Mtnrwanda_Model::Handler ManageRequestSession Returning Status ' .var_export($status, true), 2);
            if(isset($status['session_language_pref'])){ 
              $params['session_language_pref'] = $status['session_language_pref']; 
            }

            $param_array = explode("*", $params['subscriberInput']);
            $this->log->ExeLog($params, 'Mtnrwanda_Model::Handler ManageRequestSession input string ' . var_export($param_array, true), 2);    
            $registered = $this->IsRegistered($params);   // Added temporariry

            if(count($param_array)>1&&$registered['code']==1){
           // Register Language
           $language['language']=$registered['language'];
           $lang = $this->kash->mod->SetLanguagePref($params,$language);
           $params['session_language_pref'] = $lang;
            //end of language registration
          $response = $this->BreakDownCodes($params,$param_array,$status['status']);
         //print_r(count($return));die();
            }else{
            //   print_r("No long code");die();
              $response = $this->MenuOptionHandler($params, $status['status']);

            }
		    //$response['sessionId']=$params['sessionId'];
        //   print_r($response);die();
    		if(empty($response['applicationResponse'])){
    		$response['msg_response']='Dear Customer, Request failed, try again Later.'.PHP_EOL.'Nshuti Mukiriya, Gusaba byarananiye, gerageza nanone Nyuma';
        $response['state']= 'FB';
        $response = $this->MenuArray($params,$response);
    		}

      }else{
    		$response['msg_response']='Dear customer, What you entered is not allowed.'.PHP_EOL.'Nshuti mukiriya,Ibyo winjiye ntibyemewe.';
        $response['state']= 'FB';
        $response = $this->MenuArray($params,$response);
      }
      $response = $this->WriteResponseXML($response);
      $this->log->ExeLog($params, 'Mtnrwanda_Model::Handler Returning XML Response ' . $response, 3);

      return $response;
    }


      function BreakDownCodes($params,$inputString, $status){

        if($inputString[1]==1){ //shool

        $response = $this->MenuOptionHandler($params, $status);
        }else if($inputString[1]==2){ //Bus

       $response = $this->MenuOptionHandler($params, $status);

        } else if($inputString[1]==3){ //Events
        //  print_r($inputString);die();
         //go to get events
         $fxn_array[0]['ussd_new_state']=1;
         $this->OperationWatch($params, 1);
         $params['subscriberInput'] = '3';
          $state = $this->GetCurrentState($params);
          $res =  $this->GetCurrentLogstate($params);  
            //print_r($state);die();
 // $this->log->ExeLog($params, 'Mtnrwanda_Model::Inside BreakDownCodes GetCurrentState ' . var_export($state, true), 2);
  
  //$this->log->ExeLog($params, 'Mtnrwanda_Model::Inside BreakDownCodes GetCGetCurrentLogstateurrentState ' . var_export($res, true), 2);
           $state[0]['current_state']  = $res['current_state'];

          $this->StoreInputValues($params, $state[0]);
           $call_fxn = $this->GetNextState($state[0]['current_state'], $params['subscriberInput']);
          //$call_fxn = $this->GetNextState($state[0]['current_state'], -1);
          // print_r($call_fxn);die();
          $this->log->ExeLog($params, 'Mtnrwanda_Model::Inside BreakDownCodes GetNextState ' . var_export($call_fxn, true), 2);
         
          $this->OperationWatch($params, $call_fxn[0]['ussd_new_state']);
                    //$params['subscriberInput'] = 'event_category';
      $this->log->ExeLog($params, 'Mtnrwanda_Model::Inside Option 3', 2);

          // print_r($menu);die();
        $result = $this->ProcessGetEventsCategories($params);
        $this->log->ExeLog($params, 'Mtnrwanda_Model::Inside BreakDownCodes ProcessGetEventsCategories ' . var_export($result, true), 2);

       if(isset($result['events'])&&isset($inputString[2])){ //
           $params['subscriberInput'] = $inputString[2];
           $state = $this->GetCurrentState($params);
           $res =  $this->GetCurrentLogstate($params);  
           $state[0]['current_state']  = $res['current_state'];
             //print_r($state);die();
           $this->StoreInputValues($params, $state[0]);
        $call_fxn = $this->GetNextState($state[0]['current_state'], -1);
       //print_r($call_fxn);die();
        $result = $this->ProcessCategoryEvents($params);

//      $this->log->ExeLog($params, 'Mtnrwanda_Model::ProcessCategoryEvents Inside Option 3 categories ' . var_export($result, true), 2);
//////////////////////////////////////////////////////////////////////////////////////////
          $this->OperationWatch($params, $call_fxn[0]['ussd_new_state']);
          if(isset($result['error_code'])){
            $menu = $result['error_code'];

          }else{
            $menu = $this->GetStateFull($call_fxn[0]['ussd_new_state']);
          }
          if (isset($params['session_language_pref'])) {
            $ln_text = 'text_' . $params['session_language_pref'];          
          } else {
            $ln_text = 'text_en';
          }
//         $this->log->ExeLog($params, 'Mtnrwanda_Model::Inside Option 3 categories ' . var_export($result, true), 2);
          $prepared_response = $this->ReplacePlaceHolders($params, $menu[0][$ln_text], $result);
          $resp['state'] = $menu[0]['state_indicator'];
          $resp['msg_response'] = $prepared_response;
          $response = $this->MenuArray($params, $resp);

          //print_r($response);die();
          return  $response;


      ///////////////////////////////////////////////////////
       }else if(isset($result['events'])){
          $menu = $this->GetStateFull($call_fxn[0]['ussd_new_state']);
          if (isset($params['session_language_pref'])) {
            $ln_text = 'text_' . $params['session_language_pref'];          
          } else {
            $ln_text = 'text_en';
          }
        $this->log->ExeLog($params, 'Mtnrwanda_Model::Inside Option 3 categories ' . var_export($result, true), 2);
        $prepared_response = $this->ReplacePlaceHolders($params, $menu[0][$ln_text], $result);
        $resp['state'] = $menu[0]['state_indicator'];
        $resp['msg_response'] = $prepared_response;
         $response = $this->MenuArray($params, $resp);

      //print_r($response);die();
         return  $response;
        }


      } else if($inputString[1]==4){ //Home Gas
        $fxn_array[0]['ussd_new_state']=1;
        $this->OperationWatch($params, 1);
        $params['subscriberInput'] = '4';
         $state = $this->GetCurrentState($params);
         $res =  $this->GetCurrentLogstate($params);  
         $state[0]['current_state']  = $res['current_state'];
         //   print_r($state);die();
         $this->StoreInputValues($params, $state[0]);
         $call_fxn = $this->GetNextState($state[0]['current_state'], $params['subscriberInput']);
         // print_r($call_fxn);die();
         $this->OperationWatch($params, $call_fxn[0]['ussd_new_state']);
                   //$params['subscriberInput'] = 'event_category';
     $this->log->ExeLog($params, 'Mtnrwanda_Model::Inside Option 4', 2);

         // print_r($menu);die();
       $result = $this->HomeGasCheckRegistration($params);
       if(isset($result['status'])&&strtolower($result['status'])=='success'){
         $menu = $this->GetStateFull($call_fxn[0]['ussd_new_state']);
         if (isset($params['session_language_pref'])) {
          $ln_text = 'text_' . $params['session_language_pref'];          
        } else {
          $ln_text = 'text_en';
        }
     //  $this->log->ExeLog($params, 'Mtnrwanda_Model::Inside Option 3 categories ' . var_export($result, true), 2);
       $prepared_response = $this->ReplacePlaceHolders($params, $menu[0][$ln_text], $result);
       $resp['state'] = $menu[0]['state_indicator'];
       $resp['msg_response'] = $prepared_response;
        $response = $this->MenuArray($params, $resp);

     //print_r($response);die();
        return  $response;
      }else if(isset($result['status'])&&strtolower($result['status'])=='failed'&&isset($result['error_code'])){
             
        $menu=null;
        $menu = $result['error_code'];      
        //$menu = $this->GetStateFull($call_fxn[0]['ussd_new_state']);
          if (isset($params['session_language_pref'])) {
           $ln_text = 'text_' . $params['session_language_pref'];          
         } else {
           $ln_text = 'text_en';
         }
     //   $this->log->ExeLog($params, 'Mtnrwanda_Model::Inside Option 3 categories ' . var_export($result, true), 2);
        $prepared_response = $this->ReplacePlaceHolders($params, $menu[0][$ln_text], $result);
        $resp['state'] = $menu[0]['state_indicator'];
        $resp['msg_response'] = $prepared_response;
         $response = $this->MenuArray($params, $resp);
 
      //print_r($response);die();
         return  $response;
        }
       
       else{

        $response = $this->MenuOptionHandler($params, $status);     
       }
    


      } else if($inputString[1]==5){ //Amahoro
        //  print_r($inputString);die();
         //go to get events
         $fxn_array[0]['ussd_new_state']=1;
         $this->OperationWatch($params, 1);
         $params['subscriberInput'] = '5';
          $state = $this->GetCurrentState($params);
          $res =  $this->GetCurrentLogstate($params);  
            //print_r($state);die();
 // $this->log->ExeLog($params, 'Mtnrwanda_Model::Inside BreakDownCodes GetCurrentState ' . var_export($state, true), 2);
  
  //$this->log->ExeLog($params, 'Mtnrwanda_Model::Inside BreakDownCodes GetCGetCurrentLogstateurrentState ' . var_export($res, true), 2);
           $state[0]['current_state']  = $res['current_state'];

          $this->StoreInputValues($params, $state[0]);
           $call_fxn = $this->GetNextState($state[0]['current_state'], $params['subscriberInput']);
          //$call_fxn = $this->GetNextState($state[0]['current_state'], -1);
          // print_r($call_fxn);die();
          $this->log->ExeLog($params, 'Mtnrwanda_Model::Inside BreakDownCodes GetNextState ' . var_export($call_fxn, true), 2);
         
          $this->OperationWatch($params, $call_fxn[0]['ussd_new_state']);
                    //$params['subscriberInput'] = 'event_category';
      $this->log->ExeLog($params, 'Mtnrwanda_Model::Inside Option 3', 2);

          // print_r($menu);die();
        $result = $this->ProcessGetEventsCategories($params);
        $this->log->ExeLog($params, 'Mtnrwanda_Model::Inside BreakDownCodes ProcessGetEventsCategories ' . var_export($result, true), 2);

       if(isset($result['events'])&&isset($inputString[2])){ //
           $params['subscriberInput'] = $inputString[2];
           $state = $this->GetCurrentState($params);
           $res =  $this->GetCurrentLogstate($params);  
           $state[0]['current_state']  = $res['current_state'];
             //print_r($state);die();
           $this->StoreInputValues($params, $state[0]);
        $call_fxn = $this->GetNextState($state[0]['current_state'], -1);
       //print_r($call_fxn);die();
        $result = $this->ProcessCategoryEvents($params);

//      $this->log->ExeLog($params, 'Mtnrwanda_Model::ProcessCategoryEvents Inside Option 3 categories ' . var_export($result, true), 2);
//////////////////////////////////////////////////////////////////////////////////////////
          $this->OperationWatch($params, $call_fxn[0]['ussd_new_state']);
          if(isset($result['error_code'])){
            $menu = $result['error_code'];

          }else{
            $menu = $this->GetStateFull($call_fxn[0]['ussd_new_state']);
          }
          if (isset($params['session_language_pref'])) {
            $ln_text = 'text_' . $params['session_language_pref'];          
          } else {
            $ln_text = 'text_en';
          }
//         $this->log->ExeLog($params, 'Mtnrwanda_Model::Inside Option 3 categories ' . var_export($result, true), 2);
          $prepared_response = $this->ReplacePlaceHolders($params, $menu[0][$ln_text], $result);
          $resp['state'] = $menu[0]['state_indicator'];
          $resp['msg_response'] = $prepared_response;
          $response = $this->MenuArray($params, $resp);

          //print_r($response);die();
          return  $response;


      ///////////////////////////////////////////////////////
       }else if(isset($result['events'])){
          $menu = $this->GetStateFull($call_fxn[0]['ussd_new_state']);
          if (isset($params['session_language_pref'])) {
            $ln_text = 'text_' . $params['session_language_pref'];          
          } else {
            $ln_text = 'text_en';
          }
        $this->log->ExeLog($params, 'Mtnrwanda_Model::Inside Option 3 categories ' . var_export($result, true), 2);
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

    function GoToEvents(){




    }

    function GoToBusTicket(){




    }

    function GoToSchools(){




    }




      function InterpreteRequest($xml_post) {
      $this->log->LogXML('mtn_rw','pull' ,$xml_post);
            $standard_array = $this->format->ParseXMLRequest($xml_post);
            return $standard_array;
      }

    
  

}
