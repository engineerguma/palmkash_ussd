<?php

class Palmkash extends Model {

    function __construct() {
        parent::__construct();
        $this->kash = new CorePalmkash();
        $this->log = new Logs();
    }


    function GetBookingErrorCode($response){
          $codes =array();
      	  if(strpos(strtolower($response),'fully bought') !== false){

      			$codes =110;
      	  }else if(strpos(strtolower($response),'booked') !== false){

      			$codes =110;
               }else{

      		$codes =104;
      	  }
      	 return $codes;
         }



    function CheckMsidn($params) {
                 return $this->db->SelectData("SELECT * FROM palm_user_account WHERE msisdn='".$params['msisdn']."' AND status='active' ");
    }


  function FieldvalidateNames($name){
    $nameError = array();

    if (!preg_match("/^[a-zA-Z ]*$/",$name)) {
      $nameError['names_error'] = 1;
    }
     
    if(strlen($name)>NAMES_MAXSIZE || strlen($name)<NAMES_MINSIZE){
      $nameError['names_error'] = 1;
    }
    return $nameError;
  }
/* 
function getUserInput($params,$inputvalue){

  $res = $this->db->SelectData("SELECT MAX(record_id) as record_id FROM palm_log_session_input_values WHERE session_id='".$params['sessionId']."' AND input_name='".$inputvalue."' ");
return $res[0]['record_id'];
}
*/


function getUserInput($params,$inputvalue){

  $res = $this->redis->GetKeyRecord($params['session_key'].'_input_values');
  $input_array = unserialize($res);
  $reversed = array_reverse($input_array);
  foreach($reversed as $key_val => $value){     
     if($value['input_name']==$inputvalue){
       return $reversed[$key_val];
     }
  }
}


function getCategoryEvents($params,$category){

  $this->log->ExeLog($params, "Palmkash::getCategoryEvents input value " . $category, 2);

  $res = $this->redis->GetKeyRecord($params['session_key'].'_event_categories');
  $events_array = unserialize($res);
  $i = 0;
  foreach($events_array as $key_val => $value){     
    $i++;
  //$this->log->ExeLog($params, "Palmkash::getCategoryEvents looped values for index ".$i." " . var_export($value, true), 2);

     if($value['ref_id']==$category){
       return $value;
     }
  
  }
}

function getEventReference($params,$inputvalue){

  $this->log->ExeLog($params, "Palmkash::getEventReference input value " . $inputvalue, 2);

  $res = $this->redis->GetKeyRecord($params['session_key'].'_events');
  $events_array = unserialize($res);
  $i = 0;
  foreach($events_array as $key_val => $value){     
    $i++;
  //$this->log->ExeLog($params, "Palmkash::getEventReference looped values for index ".$i." " . var_export($value, true), 2);

     if($value['ref_id']==$inputvalue){
       return $value;
     }
  
  }
}

function getEventTicketsReference($params,$inputvalue){
  $multi_array = array();
  $res = $this->redis->GetKeyRecord($params['session_key'].'_event_tickets');
  $event_tickets_array = unserialize($res);
  foreach($event_tickets_array as $key_val => $value){     

  //  $this->log->ExeLog($params, "Palmkash::getEventTicketsReference  values  ". var_export($value, true), 2);
     if($value['event_ref']==$inputvalue){
      array_push($multi_array,$value);
 
     }
  }

  return $multi_array;
 }
  function getEventTicketDetails($params,$event_ref,$ticket_ref){

    $this->log->ExeLog($params, "Palmkash::getEventTicketDetails  event ref ".$event_ref." and ticekt ref ".$ticket_ref , 2);

    $res = $this->redis->GetKeyRecord($params['session_key'].'_event_tickets');
    $event_tickets_array = unserialize($res);
    foreach($event_tickets_array as $key_val => $value){     
  
    //  $this->log->ExeLog($params, "Palmkash::getEventTicketDetails  values   ". var_export($value, true), 2);
       if($value['ref_id']==$ticket_ref && $value['event_ref']==$event_ref){
        return $value;
    
       }
    }

}


function getPaymentTextMsg($params){

  //$ln = $this->GetSessionLanguage($params);
  $ln = $this->GetSessionRecords($params['session_key']);
    $this->log->ExeLog($params, 'Palmkash::getPaymentTextMsg GetSessionRecords  ' . var_export($ln, true), 2);

  $network = $params['operator'];
  $message_param= strtoupper($params['operator']).'_PAYMENT_MESSAGE_'.strtoupper($ln['session_language_pref']);
  $message =PAYMENT_SUBMITTED_MSG[$message_param];
  $msg_array =array();
  $msg_array[0]['text_'.$ln['session_language_pref']]=$message;
  $msg_array[0]['state_indicator']='FB';

  return $msg_array;
}




function getReference($msisdn,$map_id){

       $res = $this->db->SelectData("SELECT * FROM data_mapper WHERE map_id='".$map_id."'
                AND msisdn=:msisdn", array('msisdn' => $msisdn));

 return  $res;
}


function getEndStationReference($msisdn,$map_id){
       $res = $this->db->SelectData("SELECT * FROM b_stations_end WHERE ref_id='".$map_id."'
                AND msisdn=:msisdn", array('msisdn' => $msisdn));
 return  $res;
}

function getStartStationReference($msisdn,$map_id){
       $res = $this->db->SelectData("SELECT * FROM b_stations_start WHERE ref_id='".$map_id."'
                AND msisdn=:msisdn", array('msisdn' => $msisdn));
 return  $res;
}

function getRouteReference($msisdn,$map_id){
       $res = $this->db->SelectData("SELECT * FROM b_route_times WHERE ref_id='".$map_id."'
                AND msisdn=:msisdn", array('msisdn' => $msisdn));
 return  $res;
}

    function VerifyRegistration($params){

        $response = $this->kash->mod->getRegistration($params);
        return $response;
    }



    function IsRegistered($params) {
        $array = array();
        $response = $this->kash->mod->getRegistration($params);

        if(empty($response)){
        
         $array['code'] =0;
        }else{
          $array['code'] =1;
          $array['language'] = $response[0]['language'];

        }
        return $array;
    }

    function CheckRegistration($params) {
        $response = $this->kash->mod->getRegistration($params);
        if(empty($response)){
    	 $this->OperationWatch($params,15);
       $response['error_code'] = $this->GetStateFull(15);
        }else{
          //save languege
          $language['language']= $response[0]['language'];
          $lang = $this->kash->mod->SetLanguagePref($params,$language);
          $params['session_language_pref'] = $lang;
          $response['language']=$lang;
        }
        return $response;
    }


    function ProcessLanguageRegistration($params) {
            //Language Registration issue
        $res = $this->getUserInput($params,'reg_language');
        $lang= $res['input_value'];
         if($lang==1||$lang==2){
        $language = $this->kash->mod->SetLanguagePref($params,$lang);
        //$this->kash->mod->UpdateLanguagePref($params,$lang);
         $response['language'] = $language;
       }else{
         $menu=null;
         $menu['error_code'] = $this->GetResponseMsg(113);
         $this->OperationWatch($params,15);
         $response=$menu;

       }
        return $response;
    }


    function ProcessLanguageChange($params) {

        $res = $this->getUserInput($params,'laguange_select');
        $lang = $res['input_value'];
         if($lang==1||$lang==2){
       $lang_change= $this->kash->mod->SetLanguagePref($params,$lang);
       $params['session_language_pref'] = $lang_change;
        $this->kash->mod->UpdateLanguagePref($params,$lang);
        $response=1;
      }else{
        $menu=null;
        $menu['error_code'] = $this->GetResponseMsg(113);
        $this->OperationWatch($params,12);
        $response=$menu;

      }
        return $response;
    }


    function ProcessUserRegistration($params) {
      $lang = $this->getUserInput($params,'reg_language');
      $fname = $this->getUserInput($params,'first_name');
      $onames = $this->getUserInput($params,'other_names');
      if($lang['input_value']==1){
         $lang ='kin';
       }else{
         $lang ='en';
       }
      $params['language']=$lang;
      $params['first_name']=$fname['input_value'];
      $response =1;
      $errors = 0;
      $validate = $this->FieldvalidateNames($fname['input_value']);  //1 means error found
      if(isset($validate['names_error'])){
        $errors = $errors + 1;  
      }
      $params['last_name']=$onames['input_value'];
      $validate = $this->FieldvalidateNames($onames['input_value']);  //1 means error found 
      if(isset($validate['names_error'])){
        $errors = $errors + 1;  
      }
      if($errors>0){
        $menu=null;
        $menu['error_code'] = $this->GetResponseMsg(116);
        $menu['names_error'] = 1;
        $response=$menu;
        
      }else{
     $this->kash->mod->SaveUserRegistration($params);
      $response =1;
      }
      return $response;
    }
 ##################Home Gas #############################

    function SaveGasSelectionType($params){
      $res = $this->getUserInput($params,'menu_choice');
    // $this->log->ExeLog($params, "Palmkash::SaveGasSelectionType GetKeyRecords  " .var_export($res,true), 2);
     
      //$states = $this->db->SelectData("SELECT previous_state,current_state FROM palm_log_current_state WHERE session_id='".$params['sessionId']."' ORDER BY record_id DESC LIMIT 1");
      $states = $this->GetCurrentLogstate($params);
      $curr_state = array();
      $curr_state['current_state'] = $states['previous_state'];
      $curr_state['input_field_name'] = 'order_type';
      $temp_params  =$params;
      if($res['input_value']==1){
      $temp_params['subscriberInput'] = 'refill';
      }else{
      $temp_params['subscriberInput'] = 'new';
      }

      $this->StoreInputValues($temp_params, $curr_state);
      $request = file_get_contents('conf/config_data.json');
      $cylinder_sizes = json_decode($request,true);
      $xml = null;
      foreach ($cylinder_sizes['configs']['cylinders']['sizes'] as $key => $value) {
         	$xml .=$value['id'].') '.$value['size'].$value['unit'].PHP_EOL;
      }
      $menu['options'] = $xml;
      //  print_r($menu);die();
     return $menu;

    }

    function HomeGaSProcessGetProducts($params){
    //  $this->log->ExeLog($params, "Palmkash::HomeGaSProcessGetProducts params " .var_export($params,true), 2);
    $sizes = $this->getUserInput($params,'cylinder_size');
    $this->log->ExeLog($params, "Palmkash::HomeGaSProcessGetProducts getUserInput sizes  " .var_export($sizes,true), 2);
   
    $order_type = $this->getUserInput($params,'order_type');
    $this->log->ExeLog($params, "Palmkash::HomeGaSProcessGetProducts getUserInput order types  " .var_export($order_type,true), 2);
  
      $curr_state = array();
      $request = file_get_contents('conf/config_data.json');
      $cylinder_sizes = json_decode($request,true);
      $xml = null;
      $search_key = array_search($sizes['input_value'], array_column($cylinder_sizes['configs']['cylinders']['sizes'], 'id'));
      $response = array();
      if($search_key!== false){
        $params['order_type'] = $order_type['input_value'];
        $params['cylinder_size'] = $cylinder_sizes['configs']['cylinders']['sizes'][$search_key]['size'];
        $response_array = $this->kash->HomegasCompleteGetProducts($params);
      //  $this->log->ExeLog($params, "Palmkash::HomeGaSProcessGetProducts HomegasCompleteGetProducts response " .var_export($response_array,true), 2);
        if(isset($response_array['status'])&&$response_array['status']=='success'){
        $keys = array_keys($response_array['result']);
        $products_array = $response_array['result'][$keys[0]];
        if(isset($products_array['types'])&&!empty($products_array['types'])){
          $response =  $this->SaveGasProductsReference($params,$products_array,$params['order_type']);
        }else{
          $menu=null;
          $menu['error_code'] = $this->GetResponseMsg(115);
          $menu['size'] = $params['cylinder_size'];
          $response=$menu;
        }
        }

        //$response = json_decode($response,true);
      }else{
        $this->log->ExeLog($params, "Palmkash::HomeGaSProcessGetProducts Else loop failing to call external system " .$search_key, 2);
        // Request failed try again
      }

    return $response;

    }

    function HomeGasCheckRegistration($params){

    $response = $this->kash->HomegasVerifyRegistration($params);
    $return_response = "";
    if(isset($response['status'])&&strtolower($response['status'])=='success'){
       $return_response=$response;
     }else if(isset($response['status'])&&strtolower($response['status'])=='failed'&&strtolower($response['result']==false)){
       //46
       $menu=null;
       $menu['error_code'] = $this->GetResponseMsg(111);
       $menu['status'] = $response['status'];
       $menu['result'] = $response['result'];
   
       $this->OperationWatch($params,46);
       $return_response=$menu;
     }else{
       $menu=null;
       $menu['error_code'] = $this->GetResponseMsg(105);
       $this->OperationWatch($params,14);
       $return_response=$menu;
      }

      return $return_response;
    }

    Function HomeGasProcessRegistration($params){

      $address = $this->getUserInput($params,'home_address');
      $names = $this->kash->mod->getRegistration($params);
      $params['address'] =  $address['input_value'];
      $this->kash->mod->SaveAddress($params,$names[0]['account_id']);
      $params['name'] =  $names[0]['first_name']." ".$names[0]['last_name'];
      $response = $this->kash->HomegasCompleteRegistration($params);
      $return_response = "";
      if(isset($response['status'])&&strtolower($response['status'])=='success'){
         $return_response=$response;
       }else{
         $menu=null;
         $menu['error_code'] = $this->GetResponseMsg(112);
         $this->OperationWatch($params,14);
         $return_response=$menu;
         }

    return $return_response;
    }


    Function ValidateGasTypeEntry($params){
      $chosen_gas = $this->getUserInput($params,'gas_type');
      $gas_ref= $this->getGasTyypeReference($params['msisdn'],$chosen_gas['input_value']);
      $response = array();
      if(!empty($gas_ref)){
        $response['total_amount'] = $gas_ref[0]['gas_price'];
        $response['size'] =  $gas_ref[0]['gas_size'];
      }else{
      //Wrong input
      $menu=null;
      $menu['error_code'] = $this->GetResponseMsg(115);
     $response=$menu;     
      }

     return   $response;
    }


    Function ProcessOrderGasConfirmationSummary($params){
      $chosen_gas = $this->getUserInput($params,'gas_type');
      $gas_ref= $this->getGasTyypeReference($params['msisdn'],$chosen_gas['input_value']);
     
      $gas_quantity = $this->getUserInput($params,'gas_quantity');
      $response = array();
      if(!empty($gas_quantity)&&is_numeric($gas_quantity['input_value'])){
        $response['quantity'] =  $gas_quantity['input_value'];
        $response['total_amount'] = number_format($gas_ref[0]['gas_price']*$gas_quantity['input_value']);
        $response['size'] =  $gas_ref[0]['gas_size'];
        $response['name'] =  $gas_ref[0]['gas_name'];
      }else{
       // Invalid entry
        $menu=null;
        $menu['error_code'] = $this->GetResponseMsg(115);
       $response=$menu;

      }

     return   $response;
    }



        Function HomeGasCompleteorderRequest($params){
          $chosen_gas = $this->getUserInput($params,'gas_type');
          $gas_ref= $this->getGasTyypeReference($params['msisdn'],$chosen_gas['input_value']);
          $gas_quantity = $this->getUserInput($params,'gas_quantity');
          $menu=null;
          $return_response=null;
          //$response['total_amount'] = $gas_ref[0]['gas_price'];
          $params['size_id'] =  $gas_ref[0]['size_id'];
          $params['order_type'] =  $gas_ref[0]['order_type'];
          $params['actualgas_id'] =  $gas_ref[0]['gas_id'];
          $params['quantity'] =  $gas_quantity['input_value'];

          $response_array = $this->kash->HomegasCompleteOrder($params);
          if(isset($response_array['status'])&&strtolower($response_array['status'])=='successfull'){
             //$return_response=$response;
             //added these below to change message
             $menu['error_code'] = $this->getPaymentTextMsg($params);
             $return_response=$menu;
           }else if(isset($response['error'])){
             $menu['error_code'] = $this->GetResponseMsg(114);
            $return_response=$menu;
            }else{
            $menu['error_code'] = $this->GetResponseMsg(114);
           $return_response=$menu;
          }
          return $return_response;
          }


    function getGasTyypeReference($msisdn,$map_id){
           $res = $this->db->SelectData("SELECT * FROM palm_log_gas_reference WHERE ref_id='".$map_id."'
                    AND msisdn=:msisdn", array('msisdn' => $msisdn));
     return  $res;
    }


    function SaveGasProductsReference($params,$array,$order_type){

      $xml=null;
      $menu=null;
      $i=1;
      $price = 0;
  	foreach($array['types'] as $key=>$value){
      $postData[$i]['ref_id'] =$i;
    	$postData[$i]['order_type'] = $order_type;
   	  $postData[$i]['gas_size'] =$array['size'];
   	  $postData[$i]['size_id'] =$array['id'];
   	  $postData[$i]['gas_id'] =$value['id'];
   	  $postData[$i]['gas_name'] =$value['name'];
      if($order_type=='refill'){
        $postData[$i]['gas_price'] =$value['price'];
        $price = $value['price'];
      }else{
        $postData[$i]['gas_price'] =$value['purchase_price'];
        $price = $value['purchase_price'];
      }
   	  $postData[$i]['gas_currency'] =$value['currency'];
      $postData[$i]['msisdn'] =$params['msisdn'];
      $postData[$i]['session_id'] =$params['sessionId'];

   	$xml .=$i.') '.$value['name']."-".$value['currency']." ".number_format($price).PHP_EOL;
      $i++;
  		}
     $this->StoreGasProductsReferences($params,$postData);
      //  print_r($menu);die();
            $menu['options'] = $xml;
  	 return $menu;
    }



    function StoreGasProductsReferences($params,$array){

  		  $sth = $this->db->prepare("DELETE FROM palm_log_gas_reference where msisdn='".$params['msisdn']."'");
  		  $sth->execute();
     // $this->log->ExeLog($params, "Palmkash::StoreGasProductsReferences Before storage " .var_export($array,true), 2);
  		foreach($array as $key=>$value){

          $this->db->InsertData("palm_log_gas_reference", $value);
  	  }

  	}

    ##############END of Home Gas

    function ProcessGetOriginByName($params) {

        $res = $this->getUserInput($params,'origin_station_search');
        $params['departure_station']=$res['input_value'];
        $response = $this->kash->GetStartStationsByName($params);
        //print_r($response);die();
        if(isset($response['status'])&&strtolower($response['status'])=='success'){
      	$return_response=$this->SaveStartEndStationsReference($params,$response,'start');
       }else if(isset($response['error'])){
         $menu=null;
         $menu['error_code'] = $this->GetResponseMsg(101);
         $this->OperationWatch($params,2);
         $return_response=$menu;
       }else{
      	  $menu=null;
          $menu['error_code'] = $this->GetResponseMsg(100);
      	  $this->OperationWatch($params,2);
      	  $return_response=$menu;
      	}
        return $return_response;
    }



    function ProcessGetDestinationByName($params) {
        //Get All Data From The DB
        $result = $this->getUserInput($params,'origin_station_search');
        $result1 = $this->getUserInput($params,'destination_search');
        $params['start_station_id']=$result['input_value'];
        $params['destination_station']=$result1['input_value'];
        $response = $this->kash->GetDestinationStationsByName($params);
        //print_r($response);die();
        $return_response = '';
        if(isset($response['status'])&&strtolower($response['status'])=='success'){
      	$return_response=$this->SaveStartEndStationsReference($params,$response,'end');
       }else if(isset($response['error'])){
        $menu=null;
        $menu['error_code'] = $this->GetResponseMsg(101);
        $this->OperationWatch($params,4);
        $return_response=$menu;
        }else{
         $menu=null;
         $menu['error_code'] = $this->GetResponseMsg(102);
         $this->OperationWatch($params,4);
         $return_response=$menu;
         }

        return $return_response;

        }


    function ProcessGetTimeSelections($params) {
        //Get All Data From The DB
        $start = $this->getUserInput($params,'origin_station');
        $end = $this->getUserInput($params,'destination');
        $start_id= $this->getStartStationReference($params['msisdn'],$start['input_value']);
        $end_id= $this->getEndStationReference($params['msisdn'],$end['input_value']);

        $params['start_station_id']=$start_id[0]['station_id'];
        $params['end_station_id']=$end_id[0]['station_id'];
        $response = $this->kash->GetBookingTimes($params);
        //print_r($response);die();
          $return_response = '';
        if(isset($response['status'])&&strtolower($response['status'])=='success'){
      	$return_response=$this->SaveBookingTImesReference($params,$response,'end');
       }else if(isset($response['error'])){
          $menu=null;
          $menu['error_code'] = $this->GetResponseMsg(101);
        	 $this->OperationWatch($params,5);
      	 $return_response=$menu;
        	}else{
          $menu=null;
          $menu['error_code'] = $this->GetResponseMsg(103);
        	 $this->OperationWatch($params,5);
      	 $return_response=$menu;
         	}
        return $return_response;

        }



	   function ProcessGetConfirmationSummary($params) {
        //Get All Data From The DB
        $start = $this->getUserInput($params,'origin_station');
        $end = $this->getUserInput($params,'destination');
        $route = $this->getUserInput($params,'departure_time');
        $tickets = $this->getUserInput($params,'number_of_tickets');
        $start_id= $this->getStartStationReference($params['msisdn'],$start['input_value']);
      //  $this->log->ExeLog($params, "Palmkash::ProcessGetConfirmationSummary getStartStationReference ".var_export($start_id,true), 2);

        $end_id= $this->getEndStationReference($params['msisdn'],$end['input_value']);
      //  $this->log->ExeLog($params, "Palmkash::ProcessGetConfirmationSummary getEndStationReference ".var_export($end_id,true), 2);

        $route_time= $this->getRouteReference($params['msisdn'],$route['input_value']);
        $this->log->ExeLog($params, "Palmkash::ProcessGetConfirmationSummary getRouteReference ".var_export($route_time,true), 2);
		     $response=array();
         $response['route_name']=$start_id[0]['station_name'].'-'.$end_id[0]['station_name'];
         $response['route_time']=$route_time[0]['time'];
         $response['tickets']= $tickets['input_value'];
         $response['amount']= number_format(($route_time[0]['price']*$tickets['input_value']));
       	 //$this->OperationWatch($params,8);

        return $response;
    }


      function ProcessBookingRequest($params) {
        $start = $this->getUserInput($params,'origin_station');
        $end = $this->getUserInput($params,'destination');
        $route = $this->getUserInput($params,'departure_time');
        $tickets = $this->getUserInput($params,'number_of_tickets');
        $route_time= $this->getRouteReference($params['msisdn'],$route['input_value']);
        $this->log->ExeLog($params, "Palmkash::ProcessGetConfirmationSummary getRouteReference ".var_export($route_time,true), 2);

         $user = $this->getRegistration($params);
         $params['names']=$user[0]['first_name'];
         $params['route_id']=$route_time[0]['route_id'];
         $params['route_type']=$route_time[0]['route_type'];
         $params['amount']=$route_time[0]['price'];
         $params['number_of_tickets']=$tickets['input_value'];
           $language ='kinyarwanda';
         if($user[0]['language']=='en'){
             $language ='english';
          }
         $params['language']=$language;
         $params['date_of_travel']=date('Y-m-d') ;
     //$this->log->ExeLog($params, "Palmkash::Before CompleteBookingRequest ".var_export($params,true), 2);
            $return_response = '';
         $response = $this->kash->CompleteBookingRequest($params);
         if(isset($response['status'])&&strtolower($response['status'])=='success'){
           $menu=null;
           /* $menu['state']='FB';
          	$msg_text ='Your Request has been successful'.PHP_EOL;
            $msg_text .='Approve the payment on mobile money'.PHP_EOL;
            $menu['msg_response'] = $msg_text;
            $return_response=$menu; */
            $menu['error_code'] = $this->getPaymentTextMsg($params);
            $return_response=$menu;

          }else if(isset($response['error'])){
             $menu=null;
             $menu['error_code'] = $this->GetResponseMsg(101);
              $this->OperationWatch($params,1);
            $return_response=$menu;
             }else{
             $menu=null;
             $menu['error_code'] = $this->GetResponseMsg(104);
              $this->OperationWatch($params,1);
            $return_response=$menu;
             }

        return $return_response;
    }


  function SaveStartEndStationsReference($params,$array,$stage){

    $xml=null;
    $menu=null;

    $i=1;
	foreach($array['result'] as $key=>$value){
    $postData[$i]['ref_id'] =$i;
  	$postData[$i]['station_id'] =$value['id'];
 	  $postData[$i]['station_name'] =$value['station'];
    $postData[$i]['msisdn'] =$params['msisdn'];
    $postData[$i]['session_id'] =$params['sessionId'];

 	$xml .=$i.') '.$value['station'].PHP_EOL;
    $i++;
		}

   $this->StoreStartEndStations($params,$postData,$stage);
      if($stage=='start'){
      $menu['start_station'] = $xml;
      }else{
        $menu['end_station'] = $xml;
      }
    //  print_r($menu);die();
	 return $menu;
  }



 	function StoreStartEndStations($params,$array,$stage){

		  $sth = $this->db->prepare("DELETE FROM b_stations_".$stage." where msisdn='".$params['msisdn']."'");
		  $sth->execute();
   // $this->log->ExeLog($params, "Palmkash::StoreStartStations Before storage " .var_export($array,true), 2);
		foreach($array as $key=>$value){

        $this->db->InsertData("b_stations_".$stage, $value);
	  }

	}


 	function StoreEndStations($params,$array){
		  $sth = $this->db->prepare("DELETE FROM b_stations_end where msisdn='".$params['msisdn']."'");
		  $sth->execute();
   // $this->log->ExeLog($params, "Palmkash::StoreEndStations Before storage " .var_export($array,true), 2);

		foreach($array as $key=>$value){

        $this->db->InsertData("b_stations_end", $value);
	  }
	}




    function SaveBookingTImesReference($params,$array,$stage){


      $sth = $this->db->prepare("DELETE FROM b_route_times where msisdn='".$params['msisdn']."'");
		  $sth->execute();

      $xml=null;
      $menu=null;

      $i=1;
  	foreach($array['result'] as $key=>$value){
      $postData[$i]['ref_id'] =$i;
    	$postData[$i]['route_id'] =$value['id'];
    	$postData[$i]['route_type'] =$value['route_type'];
   	  $postData[$i]['time'] =$value['time'];
   	  $postData[$i]['price'] =$value['price'];
      $postData[$i]['msisdn'] =$params['msisdn'];
      $postData[$i]['session_id'] =$params['sessionId'];

      $this->db->InsertData("b_route_times", $postData[$i]);

   	$xml .=$i.') '.$value['time']." ".number_format($value['price']).PHP_EOL;
      $i++;
  		}
      $menu['time_available'] = $xml;
      //  print_r($menu);die();
  	 return $menu;
    }

////////////SCHOOL

  function ConfirmStudentTransport($params){

    $amount = $this->getUserInput($params,'amount');
    $student_account = $this->getUserInput($params,'student_account');
    $params['account_number']=$student_account['input_value'];
    $params['merchant']= 'student_transport';
    $response = $this->kash->ProcessGetStudentDetails($params);
      $return_response = '';
    if(isset($response['status'])&&strtolower($response['status'])=='success'){

      $response['amount']=$amount['input_value'];
      $response['student_name']=$response['name'];
      $response['charge']=SCHOOL_CHARGE;
      $return_response=$response;
    }else if(isset($response['error'])&&isset($response['status_code'])){
        $menu=null;
        $menu['error_code'] = $this->GetResponseMsgByStatus($response['status_code']);
        $menu['account_number'] = $params['account_number'];
         $this->OperationWatch($params,20);
      $return_response=$menu;
       }else if(isset($response['error'])){
        $menu=null;
        $menu['error_code'] = $this->GetResponseMsg(106);
        $menu['account_number'] = $params['account_number'];
         $this->OperationWatch($params,20);
      $return_response=$menu;
      }else{
        $menu=null;
        $menu['error_code'] = $this->GetResponseMsg(105);
         $this->OperationWatch($params,1);
       $return_response=$menu;
        }

   return $return_response;
      }



  function ProcessStudentTransportRequest($params){
    $amount = $this->getUserInput($params,'amount');
    $student_account = $this->getUserInput($params,'student_account');
    $params['account_number']=$student_account['input_value'];
    $params['amount']=$amount['input_value'];
    $params['merchant']= 'student_transport';
    $params['reason']= 'Student Transport Payment';
    $response = $this->kash->CompleteSchoolfeesTransportPayment($params);
      $return_response = '';
    if(isset($response['status'])&&strtolower($response['status'])=='pending'){
     // $return_response=$response; //was there before
      $menu=null;
      $menu['error_code'] = $this->getPaymentTextMsg($params);
      $return_response=$menu;     
    }else{
        $menu=null;
        $menu['error_code'] = $this->GetResponseMsg(107);
       $return_response=$menu;
     }

   return $return_response;
  }



    function ConfirmStudentSchoolfees($params){

      $amount = $this->getUserInput($params,'amount');
      $student_account = $this->getUserInput($params,'student_account');
      $params['account_number']=$student_account['input_value'];
      $params['merchant']= 'school_fees';
      $response = $this->kash->ProcessGetStudentDetails($params);
        $return_response = '';
      if(isset($response['status'])&&strtolower($response['status'])=='success'){

        $response['amount']=$amount['input_value'];
        $response['student_name']=$response['name'];
        $response['charge']=SCHOOL_CHARGE;
        $return_response=$response;
        }else if(isset($response['error'])&&isset($response['status_code'])){
        $menu=null;
        $menu['error_code'] = $this->GetResponseMsgByStatus($response['status_code']);
        $menu['account_number'] = $params['account_number'];
         $this->OperationWatch($params,28);
         $return_response=$menu;
         }else if(isset($response['error'])){
          $menu=null;
          $menu['error_code'] = $this->GetResponseMsg(106);
          $menu['account_number'] = $params['account_number'];
           $this->OperationWatch($params,28);
        $return_response=$menu;
          }else{
          $menu=null;
          $menu['error_code'] = $this->GetResponseMsg(105);
           $this->OperationWatch($params,1);
         $return_response=$menu;
          }

     return $return_response;
    }



  function ProcessSchoolfeesPayment($params){
    $amount = $this->getUserInput($params,'amount');
    $student_account = $this->getUserInput($params,'student_account');
    $params['account_number']=$student_account['input_value'];
    $params['amount']=$amount['input_value'];
    $params['merchant']= 'school_fees';
    $params['reason']= 'School fees Payment';
    $response = $this->kash->CompleteSchoolfeesTransportPayment($params);
      $return_response = '';
    if(isset($response['status'])&&strtolower($response['status'])=='pending'){
     //$return_response=$response;
     $menu=null;
     $menu['error_code'] = $this->getPaymentTextMsg($params);
     $return_response=$menu;      
    }else{
        $menu=null;
        $menu['error_code'] = $this->GetResponseMsg(107);
       $return_response=$menu;
     }

   return $return_response;
  }


        function ConfirmPocketMoneyPayment($params){

          $amount = $this->getUserInput($params,'student_account');
          $student_account = $this->getUserInput($params,'student_account');
          $params['account_number']=$student_account['input_value'];
          $response = $this->kash->ProcessGetPMStudentDetails($params);
          //print_r($response);die();
            $return_response = '';
          if(isset($response['status'])&&strtolower($response['status'])=='success'){

            $response['amount']=$amount['input_value'];
            $response['student_name']=$response['result']['last_name']." ".$response['result']['first_name'];
            $response['school']=$response['result']['school'];
            $response['charge']=SCHOOL_CHARGE;
            $return_response=$response;
          }else if(isset($response['status'])&&$response['result']==''){
              $menu=null;
              $menu['error_code'] = $this->GetResponseMsg(106);
              $menu['account_number'] = $params['account_number'];
               $this->OperationWatch($params,24);
            $return_response=$menu;
              }else{
              $menu=null;
              $menu['error_code'] = $this->GetResponseMsg(105);
               $this->OperationWatch($params,1);
             $return_response=$menu;
              }

         return $return_response;
        }



      function ProcessPocketMoneyPaymentRequest($params){
        $amount = $this->getUserInput($params,'amount');
        $student_account = $this->getUserInput($params,'student_account');
        $params['account_number']=$student_account['input_value'];
        $params['amount']=$amount['input_value'];
        $params['merchant']= 'pocket_money';
        $params['reason']= 'Pocket Money Payment';
        $user = $this->getRegistration($params);
        $language ='kinyarwanda';
        if($user[0]['language']=='en'){
          $language ='english';
        }
       $params['language']=$language;
         $return_response = '';
        $response = $this->kash->CompletePocketMoneyPayment($params);
        if(isset($response['status'])&&strtolower($response['status'])=='success'){
          
         //$return_response=$response;
         $menu=null;
         $menu['error_code'] = $this->getPaymentTextMsg($params);
         $return_response=$menu;        
        }else{
          $menu=null;
          $menu['error_code'] = $this->GetResponseMsg(107);
          $return_response=$menu;
         }

       return $return_response;
      }

  ////Events

      function CompleteEventBookingRequest($params) {

        $event_ref = $this->getUserInput($params,'single_event');
        $ticket_ref = $this->getUserInput($params,'ticket_class');
        //print_r($ticket_ref);die();
        //$ticket_class = $this->db->SelectData("SELECT * FROM event_tickets WHERE ref_id='".$ticket_ref['input_value']."' AND  event_ref='".$event_ref['input_value']."' AND session_id='".$params['sessionId']."' ");
        $ticket_class = $this->getEventTicketDetails($params,$event_ref['input_value'],$ticket_ref['input_value']);
     
        $no_tickets = $this->getUserInput($params,'tickets_number');
       
        $user = $this->getRegistration($params);
         $params['names']=$user[0]['first_name'];
         $params['price_id']=$ticket_class['api_id'];
         $params['amount']=$ticket_class['amount'];
         $params['number_of_tickets']=$no_tickets['input_value'];
           $language ='kinyarwanda';
           if($user[0]['language']=='en'){
             $language ='english';
           }
         $params['language']=$language;
          $return_response = '';
         $response = $this->kash->CompleteEventsBookingRequest($params);
         if(isset($response['status'])&&strtolower($response['status'])=='pending'){
            //$return_response=$response;
            //added these below to change message
            $menu=null;
            $menu['error_code'] = $this->getPaymentTextMsg($params);
            $return_response=$menu;
          }else if(isset($response['error'])){
             $menu=null;
             $menu['error_code'] = $this->GetResponseMsg(101);
              $this->OperationWatch($params,1);
            $return_response=$menu;
          }else if(isset($response['status'])&&strtolower($response['status']=='failed')){
            $menu=null;
            $menu['error_code'] = $this->GetResponseMsg(104);
             $this->OperationWatch($params,1);
           $return_response=$menu;
         }else{
             $menu=null;
             $code = $this->GetBookingErrorCode($response['result']);
             $menu['error_code'] = $this->GetResponseMsg($code);
            $menu['ticket_class']=$ticket_class['name'];
              $this->OperationWatch($params,1);
            $return_response=$menu;
          }

        return $return_response;
    }

  function GetBookingConfirmationDetails($params){

          $menu=null;
          $event_ref = $this->getUserInput($params,'single_event');
          $event = $this->getEventReference($params,$event_ref['input_value']);
          //$event = $this->db->SelectData("SELECT * FROM events WHERE ref_id='".$event_ref['input_value']."' AND session_id='".$params['sessionId']."' ");
          $menu['event']=$event['name'];
          $menu['venue']=$event['venue'];
          $ticket_ref = $this->getUserInput($params,'ticket_class');
          //print_r($ticket_ref);die();
          //$ticket_class = $this->db->SelectData("SELECT * FROM event_tickets WHERE ref_id='".$ticket_ref['input_value']."' AND  event_ref='".$event_ref['input_value']."' AND session_id='".$params['sessionId']."' ");
          $ticket_class = $this->getEventTicketDetails($params,$event_ref['input_value'],$ticket_ref['input_value']);

          $menu['ticket_class']=$ticket_class['name'];
          $menu['amount']=number_format($ticket_class['amount']);
         //This Needs Validation.
          $no_tickets = $this->getUserInput($params,'tickets_number');
          if(!empty($no_tickets)&&is_numeric($no_tickets['input_value'])){
          $menu['no_tickets']=$no_tickets['input_value'];
          $menu['total_amount']=number_format($no_tickets['input_value']*$ticket_class['amount']);
          }else{
            //Invalid Entry
            $menu['error_code'] = $this->GetResponseMsg(115);
          }

     return $menu;
  }

  function SaveRouteKey(){

    $this->redis->StoreNameWitValue($params['session_key'],'session_language_pref', $postLang['session_language_pref']);    
  
  }
  function ProcessGetEventsCategories($params){

    ###fetch the key
   $current_state = $this->GetCurrentLogstate($params);
  $this->log->ExeLog($params, "Palmkash::ProcessGetEventsCategories  Current State details  ". var_export($current_state, true), 2);

   $state_info = $this->GetCurrentState($params,$current_state['current_state']);
    if(isset($state_info[0]['search_key'])&&$state_info[0]['search_key']!=''){
    $this->redis->StoreNameWitValue($params['session_key'],'routing_key',$state_info[0]['search_key']);    
    } 
    $response = $this->kash->GetEventCategories($params);
       $return_response = '';
    if(isset($response['status'])&&strtolower($response['status'])=='success'){
         $ret =$this->SaveEventsCategoriesReferences($params,$response['result']);
        $return_response=$ret;
    }else if(isset($response['error'])){
        $menu=null;
        $menu['error_code'] = $this->GetResponseMsg(109);
         $this->OperationWatch($params,1);
      $return_response=$menu;
    }else{
        $menu=null;
        $menu['error_code'] = $this->GetResponseMsg(109);
         $this->OperationWatch($params,1);
       $return_response=$menu;
     }

   return $return_response;
  }

  function ProcessCategoryEvents($params){

    $category = $this->getUserInput($params,'event_category');
    //$category_id = $this->db->SelectData("SELECT * FROM event_categories WHERE ref_id='".$category['input_value']."' AND session_id='".$params['sessionId']."' ");
    $category_id = $this->getCategoryEvents($params,$category['input_value']);
    
     if(empty($category_id)==false){
    $params['category_id']=$category_id['api_id'];
      //print_r($category_id);die();
        $return_response = '';
        $response = $this->kash->GetEventsByCategory($params);
    if(isset($response['status'])&&strtolower($response['status'])=='success'){
    $ret =$this->SaveEventsReferences($params,$response['result']);

     $return_response=$ret;
    }else{
        $menu=null;
        $menu['error_code'] = $this->GetResponseMsg(109);
       $return_response=$menu;
     }
   }else{
      //invalid Entry
      $menu=null;
      $menu['error_code'] = $this->GetResponseMsg(115);
     $return_response=$menu;
   }
   return $return_response;
  }



  function ProcessGetEventTicketClasses($params){

    $event = $this->getUserInput($params,'single_event');
    
    $event_id = $this->getEventReference($params,$event['input_value']);
    //$this->log->ExeLog($params, "Palmkash::ProcessGetEventTicketClasses  getEventReference returned event data  ". var_export($event_id, true), 2);

    //$event_id = $this->db->SelectData("SELECT * FROM events WHERE ref_id='".$event['input_value']."' AND session_id='".$params['sessionId']."' ");
     $xml=null;
     $menu=null;
     if(empty($event_id)==false){

      $tickets = $this->getEventTicketsReference($params,$event_id['ref_id']);
     // $this->log->ExeLog($params, "Palmkash::ProcessGetEventTicketClasses  getEventTicketsReference returned tickets data  ". var_export($tickets, true), 2);

      //$tickets = $this->db->SelectData("SELECT * FROM event_tickets WHERE event_ref='".$event_id[0]['ref_id']."' AND session_id='".$params['sessionId']."' ");
        $i=1;
       foreach($tickets as $key=>$value){
       $xml .=$value['ref_id'].') '.$value['name']." ".number_format($value['amount']).PHP_EOL;
         $i++;
         }
    $menu['tickets'] = $xml;
    return $menu;
    }else{
      //invalid Entry
      $menu['error_code'] = $this->GetResponseMsg(115);
   }
   return $menu;
  }

  function ValidateTicketClass($params){

    $menu=null;
    $event_ref = $this->getUserInput($params,'single_event');
    $ticket_ref = $this->getUserInput($params,'ticket_class');
  
    //print_r($ticket_ref);die();
   // $ticket_class = $this->db->SelectData("SELECT * FROM event_tickets WHERE ref_id='".$ticket_ref['input_value']."' AND  event_ref='".$event_ref['input_value']."' AND session_id='".$params['sessionId']."' ");
 
      $ticket_class = $this->getEventTicketDetails($params,$event_ref['input_value'],$ticket_ref['input_value']);
    //  $this->log->ExeLog($params, "Palmkash::ValidateTicketClass  getEventTicketDetails returned event data  ". var_export($ticket_class, true), 2);

   
    if(!empty($ticket_class)&&is_numeric($ticket_ref['input_value'])){ 
 
    }else{
      //invalid ticket Class
      $menu['error_code'] = $this->GetResponseMsg(115);
   }
   return $menu;
  }




      function SaveEventsCategoriesReferences($params,$array){

       /* $sth = $this->db->prepare("DELETE FROM event_categories where msisdn='".$params['msisdn']."'");
  		  $sth->execute(); */
        $this->redis->DeleteKey($params['session_key'].'_event_categories');
        $xml=null;
        $menu=null;
        $mult_event_categories=array();
        $i=1;
    	foreach($array as $key=>$value){
        $postData['ref_id'] =$i;
      	$postData['api_id'] =$value['id'];
     	  $postData['name'] =$value['name'];
        $postData['msisdn'] =$params['msisdn'];
        $postData['session_id'] =$params['sessionId'];

      //$this->db->InsertData("event_categories", $postData[$i]);
      array_push($mult_event_categories,$postData);
      $postData  = null;
     	//$xml .=$i.') '.$value['name']." ".number_format($value['price']).PHP_EOL;
     	$xml .=$i.') '.$value['name'].PHP_EOL;
        $i++;
    		}
      //  $this->log->ExeLog($params, "Palmkash::SaveEventsCategoriesReferences mult_events " . var_export($mult_event_categories, true), 2);

        $serialized_categories = serialize($mult_event_categories);
        $this->redis->StoreKeyData($params['session_key'].'_event_categories',$serialized_categories);        
        $menu['events'] = $xml;
        //  print_r($menu);die();
    	 return $menu;
      }

      function SaveEventsReferences($params,$array){

       /* $sth = $this->db->prepare("DELETE FROM events where msisdn='".$params['msisdn']."'");
        $sth1 = $this->db->prepare("DELETE FROM event_tickets where msisdn='".$params['msisdn']."'");
  		  $sth->execute();
  		  $sth1->execute(); */
        $this->redis->DeleteKey($params['session_key'].'_events');
        $this->redis->DeleteKey($params['session_key'].'_event_tickets');

        $xml=null;
        $menu=null;
        $i=1;
        $mult_events = array();
        $mult_tickets = array();
    	foreach($array as $key=>$value){
       /* $postData[$i]['ref_id'] =$i;
     	  $postData[$i]['name'] =$value[$i-1]['name'];
     	  $postData[$i]['venue'] =$value[$i-1]['venue'];
     	  $postData[$i]['time'] =$value[$i-1]['time'];
        $postData[$i]['msisdn'] =$params['msisdn'];
        $postData[$i]['session_id'] =$params['sessionId']; */
        $postData['ref_id'] =$i;
        $postData['name'] =$value[$i-1]['name'];
        $postData['venue'] =$value[$i-1]['venue'];
        $postData['time'] =$value[$i-1]['time'];
        $postData['msisdn'] =$params['msisdn'];
        $postData['session_id'] =$params['sessionId'];
     
        array_push($mult_events,$postData);
        $postData  = null;
     // $this->db->InsertData("events", $postData[$i]);
         $j=1;
      	foreach($value[$i-1]['prices'] as $key1=>$prices){
       $TicketData['event_ref'] =$i;
       $TicketData['ref_id'] =$j;
       $TicketData['api_id'] =$prices['price_id'];
       $TicketData['name'] =$prices['ticket_category'];
       $TicketData['amount'] =$prices['ticket_price'];
       $TicketData['msisdn'] =$params['msisdn'];
       $TicketData['session_id'] =$params['sessionId'];
       array_push($mult_tickets,$TicketData);
       $TicketData  = null;
      //$this->db->InsertData("event_tickets", $TicketData);
        $j++;
          }
     //	$xml .=$i.') '.$value[$i-1]['name']." - ".$value[$i-1]['venue'].PHP_EOL;
     	$xml .=$i.') '.$value[$i-1]['name'].PHP_EOL;
        $i++;
    		}
    //  $this->log->ExeLog($params, "Palmkash::SaveEventsReferences mult_events " . var_export($mult_events, true), 2);

        $serialized_events = serialize($mult_events);
     //   $this->log->ExeLog($params, "Palmkash::SaveEventsReferences mult_tickets " . var_export($mult_tickets, true), 2);

        $serialized_eventtickets = serialize($mult_tickets);
        $this->redis->StoreKeyData($params['session_key'].'_events',$serialized_events);    
        $this->redis->StoreKeyData($params['session_key'].'_event_tickets',$serialized_eventtickets);         
        $menu['events'] = $xml;
  

        //  print_r($menu);die();
    	 return $menu;
      }



}

?>
