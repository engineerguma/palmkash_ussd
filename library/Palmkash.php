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


function getUserInput($params,$inputvalue){

  $res = $this->db->SelectData("SELECT MAX(record_id) as record_id FROM palm_log_session_input_values WHERE session_id='".$params['sessionId']."' AND input_name='".$inputvalue."' ");
return $res[0]['record_id'];
}


function getPaymentTextMsg($params){

  $ln = $this->GetSessionLanguage($params);
  $network = $params['operator'];
  $message_param= strtoupper($params['operator']).'_PAYMENT_MESSAGE_'.strtoupper($ln[0]['session_language_pref']);
  $message =PAYMENT_SUBMITTED_MSG[$message_param];
  $msg_array =array();
  $msg_array[0]['text_'.$ln[0]['session_language_pref']]=$message;
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
        $response = $this->kash->mod->getRegistration($params);
        if(empty($response)){
         return 0;
        }else{
         return 1;

        }
    }

    function CheckRegistration($params) {
        $response = $this->kash->mod->getRegistration($params);
        if(empty($response)){
    	 $this->OperationWatch($params,15);
        $response['language']=1;
        }else{
          //save languege
          $language['language']=$response[0]['language'];
         $this->kash->mod->SetLanguagePref($params,$language);
         $response =1;
        }
        return $response;
    }


    function ProcessLanguageRegistration($params) {
            //Language Registration issue
        $res = $this->db->SelectData("SELECT * FROM palm_log_session_input_values WHERE record_id='".$this->getUserInput($params,'reg_language')."' ");
         $lang= $res[0]['input_value'];
         if($lang==1||$lang==2){
        $this->kash->mod->SetLanguagePref($params,$lang);
         $response=1;
       }else{
         $menu=null;
         $menu['error_code'] = $this->GetResponseMsg(113);
         $this->OperationWatch($params,15);
         $response=$menu;

       }
        return $response;
    }


    function ProcessLanguageChange($params) {

        $res = $this->db->SelectData("SELECT * FROM palm_log_session_input_values WHERE record_id='".$this->getUserInput($params,'laguange_select')."' ");
        $lang= $res[0]['input_value'];
         if($lang==1||$lang==2){
        $this->kash->mod->SetLanguagePref($params,$lang);
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
      $lang = $this->db->SelectData("SELECT * FROM palm_log_session_input_values WHERE record_id='".$this->getUserInput($params,'reg_language')."' ");
      $fname = $this->db->SelectData("SELECT * FROM palm_log_session_input_values WHERE record_id='".$this->getUserInput($params,'first_name')."' ");
      $onames = $this->db->SelectData("SELECT * FROM palm_log_session_input_values WHERE record_id='".$this->getUserInput($params,'other_names')."' ");
       if($lang[0]['input_value']==1){
         $lang ='kin';
       }else{
         $lang ='en';
       }
      $params['language']=$lang;
      $params['first_name']=$fname[0]['input_value'];
      $params['last_name']=$onames[0]['input_value'];
     $this->kash->mod->SaveUserRegistration($params);
      $response =1;
      return $response;
    }
 ##################Home Gas #############################

    function SaveGasSelectionType($params){
      $res = $this->db->SelectData("SELECT * FROM palm_log_session_input_values WHERE record_id='".$this->getUserInput($params,'menuchoice')."' ");
      $states = $this->db->SelectData("SELECT previous_state,current_state FROM palm_log_current_state WHERE session_id='".$params['sessionId']."' ORDER BY record_id DESC LIMIT 1");
      $curr_state = array();
      $curr_state['current_state'] = $states[0]['previous_state'];
      $curr_state['input_field_name'] = 'order_type';
      $temp_params  =$params;
      if($res[0]['input_value']==1){
      $temp_params['subscriberInput'] = 'refill';
      }else{
        $temp_params['subscriberInput'] = 'new';
      }

      $this->StoreInputValues($temp_params, $curr_state);
      $request = file_get_contents('conf/config_data.json');
      $cylinder_sizes = json_decode($request,true);
      $xml = null;
      foreach ($cylinder_sizes['gas']['cylinders']['sizes'] as $key => $value) {
         	$xml .=$value['id'].') '.$value['size'].$value['unit'].PHP_EOL;
      }
      $menu['options'] = $xml;
      //  print_r($menu);die();
     return $menu;

    }

    function HomeGaSProcessGetProducts($params){
    //  $this->log->ExeLog($params, "Palmkash::HomeGaSProcessGetProducts params " .var_export($params,true), 2);
      $sizes = $this->db->SelectData("SELECT * FROM palm_log_session_input_values WHERE record_id='".$this->getUserInput($params,'cylinder_size')."' ");
      $order_type = $this->db->SelectData("SELECT * FROM palm_log_session_input_values WHERE record_id='".$this->getUserInput($params,'order_type')."' ");
      $curr_state = array();
      $request = file_get_contents('conf/config_data.json');
      $cylinder_sizes = json_decode($request,true);
      $xml = null;
      $search_key = array_search($sizes[0]['input_value'], array_column($cylinder_sizes['gas']['cylinders']['sizes'], 'id'));
      $response = array();
      if($search_key!== false){
        $params['order_type'] = $order_type[0]['input_value'];
        $params['cylinder_size'] = $cylinder_sizes['gas']['cylinders']['sizes'][$search_key]['size'];
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

      $address = $this->db->SelectData("SELECT * FROM palm_log_session_input_values WHERE record_id='".$this->getUserInput($params,'home_address')."' ");
      $names = $this->kash->mod->getRegistration($params);
      $params['address'] =  $address[0]['input_value'];
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

    Function ProcessOrderGasConfirmationSummary($params){
      $chosen_gas = $this->db->SelectData("SELECT * FROM palm_log_session_input_values WHERE record_id='".$this->getUserInput($params,'gas_type')."' ");
      $gas_ref= $this->getGasTyypeReference($params['msisdn'],$chosen_gas[0]['input_value']);
      $response = array();
      if(!empty($gas_ref)){
        $response['total_amount'] = $gas_ref[0]['gas_price'];
        $response['size'] =  $gas_ref[0]['gas_size'];
        $response['name'] =  $gas_ref[0]['gas_name'];

      }else{
      //Wrong input


      }

     return   $response;
    }



        Function HomeGasCompleteorderRequest($params){
          $chosen_gas = $this->db->SelectData("SELECT * FROM palm_log_session_input_values WHERE record_id='".$this->getUserInput($params,'gas_type')."' ");
          $gas_ref= $this->getGasTyypeReference($params['msisdn'],$chosen_gas[0]['input_value']);

          $menu=null;
          $return_response=null;
          //$response['total_amount'] = $gas_ref[0]['gas_price'];
          $params['size_id'] =  $gas_ref[0]['size_id'];
          $params['order_type'] =  $gas_ref[0]['order_type'];
          $params['actualgas_id'] =  $gas_ref[0]['gas_id'];
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

        $res = $this->db->SelectData("SELECT * FROM palm_log_session_input_values WHERE record_id='".$this->getUserInput($params,'origin_station_search')."' ");
        $params['departure_station']=$res[0]['input_value'];
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
        $result = $this->db->SelectData("SELECT * FROM palm_log_session_input_values WHERE record_id='".$this->getUserInput($params,'destination_search')."' ");
        $result1 = $this->db->SelectData("SELECT * FROM palm_log_session_input_values WHERE record_id='".$this->getUserInput($params,'destination_search')."' ");
        $params['start_station_id']=$result[0]['input_value'];
        $params['destination_station']=$result1[0]['input_value'];
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
        $start = $this->db->SelectData("SELECT * FROM palm_log_session_input_values WHERE record_id='".$this->getUserInput($params,'origin_station')."' ");
        $end = $this->db->SelectData("SELECT * FROM palm_log_session_input_values WHERE record_id='".$this->getUserInput($params,'destination')."' ");

        $start_id= $this->getStartStationReference($params['msisdn'],$start[0]['input_value']);
        $end_id= $this->getEndStationReference($params['msisdn'],$end[0]['input_value']);

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
        $start = $this->db->SelectData("SELECT * FROM palm_log_session_input_values WHERE record_id='".$this->getUserInput($params,'origin_station')."' ");
        $end = $this->db->SelectData("SELECT * FROM palm_log_session_input_values WHERE record_id='".$this->getUserInput($params,'destination')."' ");

        $route = $this->db->SelectData("SELECT * FROM palm_log_session_input_values WHERE record_id='".$this->getUserInput($params,'departure_time')."' ");
        $tickets = $this->db->SelectData("SELECT * FROM palm_log_session_input_values WHERE record_id='".$this->getUserInput($params,'number_of_tickets')."' ");

        $start_id= $this->getStartStationReference($params['msisdn'],$start[0]['input_value']);
      //  $this->log->ExeLog($params, "Palmkash::ProcessGetConfirmationSummary getStartStationReference ".var_export($start_id,true), 2);

        $end_id= $this->getEndStationReference($params['msisdn'],$end[0]['input_value']);
      //  $this->log->ExeLog($params, "Palmkash::ProcessGetConfirmationSummary getEndStationReference ".var_export($end_id,true), 2);

        $route_time= $this->getRouteReference($params['msisdn'],$route[0]['input_value']);
        //$this->log->ExeLog($params, "Palmkash::ProcessGetConfirmationSummary getRouteReference ".var_export($route_time,true), 2);
		     $response=array();
         $response['route_name']=$start_id[0]['station_name'].'-'.$end_id[0]['station_name'];
         $response['route_time']=$route_time[0]['time'];
         $response['tickets']= $tickets[0]['input_value'];
         $response['amount']= number_format(($route_time[0]['price']*$tickets[0]['input_value']));
       	 //$this->OperationWatch($params,8);

        return $response;
    }


      function ProcessBookingRequest($params) {
        $start = $this->db->SelectData("SELECT * FROM palm_log_session_input_values WHERE record_id='".$this->getUserInput($params,'origin_station')."' ");
        $end = $this->db->SelectData("SELECT * FROM palm_log_session_input_values WHERE record_id='".$this->getUserInput($params,'destination')."' ");

        $route = $this->db->SelectData("SELECT * FROM palm_log_session_input_values WHERE record_id='".$this->getUserInput($params,'departure_time')."' ");
        $tickets = $this->db->SelectData("SELECT * FROM palm_log_session_input_values WHERE record_id='".$this->getUserInput($params,'number_of_tickets')."' ");

        $route_time= $this->getRouteReference($params['msisdn'],$route[0]['input_value']);
        //$this->log->ExeLog($params, "Palmkash::ProcessGetConfirmationSummary getRouteReference ".var_export($route_time,true), 2);

         $user = $this->getRegistration($params);
         $params['names']=$user[0]['first_name'];
         $params['route_id']=$route_time[0]['route_id'];
         $params['route_type']=$route_time[0]['route_type'];
         $params['amount']=$route_time[0]['price'];
         $params['number_of_tickets']=$tickets[0]['input_value'];
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
            $menu['state']='FB';
          	$msg_text ='Your Request has been successful'.PHP_EOL;
            $msg_text .='Approve the payment on mobile money'.PHP_EOL;
            $menu['msg_response'] = $msg_text;
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

    $amount = $this->db->SelectData("SELECT * FROM palm_log_session_input_values WHERE record_id='".$this->getUserInput($params,'amount')."' ");
    $student_account = $this->db->SelectData("SELECT * FROM palm_log_session_input_values WHERE record_id='".$this->getUserInput($params,'student_account')."' ");

    $params['account_number']=$student_account[0]['input_value'];
    $params['merchant']= 'student_transport';
    $response = $this->kash->ProcessGetStudentDetails($params);
      $return_response = '';
    if(isset($response['status'])&&strtolower($response['status'])=='success'){

      $response['amount']=$amount[0]['input_value'];
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
    $amount = $this->db->SelectData("SELECT * FROM palm_log_session_input_values WHERE record_id='".$this->getUserInput($params,'amount')."' ");
    $student_account = $this->db->SelectData("SELECT * FROM palm_log_session_input_values WHERE record_id='".$this->getUserInput($params,'student_account')."' ");

    $params['account_number']=$student_account[0]['input_value'];
    $params['amount']=$amount[0]['input_value'];
    $params['merchant']= 'student_transport';
    $params['reason']= 'Student Transport Payment';
    $response = $this->kash->CompleteSchoolfeesTransportPayment($params);
      $return_response = '';
    if(isset($response['status'])&&strtolower($response['status'])=='pending'){
     $return_response=$response;
    }else{
        $menu=null;
        $menu['error_code'] = $this->GetResponseMsg(107);
       $return_response=$menu;
     }

   return $return_response;
  }



    function ConfirmStudentSchoolfees($params){

      $amount = $this->db->SelectData("SELECT * FROM palm_log_session_input_values WHERE record_id='".$this->getUserInput($params,'amount')."' ");
      $student_account = $this->db->SelectData("SELECT * FROM palm_log_session_input_values WHERE record_id='".$this->getUserInput($params,'student_account')."' ");

      $params['account_number']=$student_account[0]['input_value'];
      $params['merchant']= 'school_fees';
      $response = $this->kash->ProcessGetStudentDetails($params);
        $return_response = '';
      if(isset($response['status'])&&strtolower($response['status'])=='success'){

        $response['amount']=$amount[0]['input_value'];
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
    $amount = $this->db->SelectData("SELECT * FROM palm_log_session_input_values WHERE record_id='".$this->getUserInput($params,'amount')."' ");
    $student_account = $this->db->SelectData("SELECT * FROM palm_log_session_input_values WHERE record_id='".$this->getUserInput($params,'student_account')."' ");

    $params['account_number']=$student_account[0]['input_value'];
    $params['amount']=$amount[0]['input_value'];
    $params['merchant']= 'school_fees';
    $params['reason']= 'School fees Payment';
    $response = $this->kash->CompleteSchoolfeesTransportPayment($params);
      $return_response = '';
    if(isset($response['status'])&&strtolower($response['status'])=='pending'){
     $return_response=$response;
    }else{
        $menu=null;
        $menu['error_code'] = $this->GetResponseMsg(107);
       $return_response=$menu;
     }

   return $return_response;
  }


        function ConfirmPocketMoneyPayment($params){

          $amount = $this->db->SelectData("SELECT * FROM palm_log_session_input_values WHERE record_id='".$this->getUserInput($params,'amount')."' ");
          $student_account = $this->db->SelectData("SELECT * FROM palm_log_session_input_values WHERE record_id='".$this->getUserInput($params,'student_account')."' ");

          $params['account_number']=$student_account[0]['input_value'];
          $response = $this->kash->ProcessGetPMStudentDetails($params);
          //print_r($response);die();
            $return_response = '';
          if(isset($response['status'])&&strtolower($response['status'])=='success'){

            $response['amount']=$amount[0]['input_value'];
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
        $amount = $this->db->SelectData("SELECT * FROM palm_log_session_input_values WHERE record_id='".$this->getUserInput($params,'amount')."' ");
        $student_account = $this->db->SelectData("SELECT * FROM palm_log_session_input_values WHERE record_id='".$this->getUserInput($params,'student_account')."' ");

        $params['account_number']=$student_account[0]['input_value'];
        $params['amount']=$amount[0]['input_value'];
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
         $return_response=$response;
        }else{
            $menu=null;
            $menu['error_code'] = $this->GetResponseMsg(107);
           $return_response=$menu;
         }

       return $return_response;
      }

  ////Events

      function CompleteEventBookingRequest($params) {

        $event_ref = $this->db->SelectData("SELECT * FROM palm_log_session_input_values WHERE record_id='".$this->getUserInput($params,'single_event')."' ");

        $ticket_ref = $this->db->SelectData("SELECT * FROM palm_log_session_input_values WHERE record_id='".$this->getUserInput($params,'ticket_class')."' ");
      //print_r($ticket_ref);die();
        $ticket_class = $this->db->SelectData("SELECT * FROM event_tickets WHERE ref_id='".$ticket_ref[0]['input_value']."' AND  event_ref='".$event_ref[0]['input_value']."' AND session_id='".$params['sessionId']."' ");
        $no_tickets = $this->db->SelectData("SELECT * FROM palm_log_session_input_values WHERE record_id='".$this->getUserInput($params,'tickets_number')."' ");

        $user = $this->getRegistration($params);
         $params['names']=$user[0]['first_name'];
         $params['price_id']=$ticket_class[0]['api_id'];
         $params['amount']=$ticket_class[0]['amount'];
         $params['number_of_tickets']=$no_tickets[0]['input_value'];
           $language ='kinyarwanda';
           if($user[0]['language']=='en'){
             $language ='english';
           }
         $params['language']=$language;
          $return_response = '';
         $response = $this->kash->CompleteEventsBookingRequest($params);
         if(isset($response['status'])&&strtolower($response['status'])=='success'){
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
          }else{
             $menu=null;
             $code = $this->GetBookingErrorCode($response['result']);
             $menu['error_code'] = $this->GetResponseMsg($code);
            $menu['ticket_class']=$ticket_class[0]['name'];
              $this->OperationWatch($params,1);
            $return_response=$menu;
          }

        return $return_response;
    }

  function GetBookingConfirmationDetails($params){

          $menu=null;
          $event_ref = $this->db->SelectData("SELECT * FROM palm_log_session_input_values WHERE record_id='".$this->getUserInput($params,'single_event')."' ");
          $event = $this->db->SelectData("SELECT * FROM events WHERE ref_id='".$event_ref[0]['input_value']."' AND session_id='".$params['sessionId']."' ");
          $menu['event']=$event[0]['name'];
          $menu['venue']=$event[0]['venue'];
          $ticket_ref = $this->db->SelectData("SELECT * FROM palm_log_session_input_values WHERE record_id='".$this->getUserInput($params,'ticket_class')."' ");
        //print_r($ticket_ref);die();
        $ticket_class = $this->db->SelectData("SELECT * FROM event_tickets WHERE ref_id='".$ticket_ref[0]['input_value']."' AND  event_ref='".$event_ref[0]['input_value']."' AND session_id='".$params['sessionId']."' ");
          $menu['ticket_class']=$ticket_class[0]['name'];
          $menu['amount']=number_format($ticket_class[0]['amount']);
         //This Needs Validation.
          $no_tickets = $this->db->SelectData("SELECT * FROM palm_log_session_input_values WHERE record_id='".$this->getUserInput($params,'tickets_number')."' ");
          $menu['no_tickets']=$no_tickets[0]['input_value'];
          $menu['total_amount']=number_format($no_tickets[0]['input_value']*$ticket_class[0]['amount']);

     return $menu;
  }

  function ProcessGetEventsCategories($params){
    $response = $this->kash->GetEventCategories($params);
       $return_response = '';
    if(isset($response['status'])&&strtolower($response['status'])=='success'){
         $ret =$this->SaveProductsReference($params,$response['result']);
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

    $category = $this->db->SelectData("SELECT * FROM palm_log_session_input_values WHERE record_id='".$this->getUserInput($params,'event_category')."' ");
    $category_id = $this->db->SelectData("SELECT * FROM event_categories WHERE ref_id='".$category[0]['input_value']."' AND session_id='".$params['sessionId']."' ");
     $params['category_id']=$category_id[0]['api_id'];
     //$tickets = $this->db->SelectData("SELECT * FROM event_tickets WHERE event_ref='".$event_id[0]['ref_id']."' ");
     if(empty($category_id)==false){
    $params['category_id']=$category_id[0]['api_id'];
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

   }
   return $return_response;
  }



  function ProcessGetEventTicketClasses($params){

    $event = $this->db->SelectData("SELECT * FROM palm_log_session_input_values WHERE record_id='".$this->getUserInput($params,'single_event')."' ");
     $event_id = $this->db->SelectData("SELECT * FROM events WHERE ref_id='".$event[0]['input_value']."' AND session_id='".$params['sessionId']."' ");
     $xml=null;
     $menu=null;
     if(empty($event_id)==false){
       $tickets = $this->db->SelectData("SELECT * FROM event_tickets WHERE event_ref='".$event_id[0]['ref_id']."' AND session_id='".$params['sessionId']."' ");
        $i=1;
       foreach($tickets as $key=>$value){
       $xml .=$value['ref_id'].') '.$value['name']." ".number_format($value['amount']).PHP_EOL;
         $i++;
         }
    $menu['tickets'] = $xml;
    return $menu;
    }else{
      //invalid Entry

   }
   return $menu;
  }




      function SaveProductsReference($params,$array){

        $sth = $this->db->prepare("DELETE FROM event_categories where msisdn='".$params['msisdn']."'");
  		  $sth->execute();
        $xml=null;
        $menu=null;
        $i=1;
    	foreach($array as $key=>$value){
        $postData[$i]['ref_id'] =$i;
      	$postData[$i]['api_id'] =$value['id'];
     	  $postData[$i]['name'] =$value['name'];
        $postData[$i]['msisdn'] =$params['msisdn'];
        $postData[$i]['session_id'] =$params['sessionId'];

      $this->db->InsertData("event_categories", $postData[$i]);

     	//$xml .=$i.') '.$value['name']." ".number_format($value['price']).PHP_EOL;
     	$xml .=$i.') '.$value['name'].PHP_EOL;
        $i++;
    		}
        $menu['events'] = $xml;
        //  print_r($menu);die();
    	 return $menu;
      }

      function SaveEventsReferences($params,$array){

        $sth = $this->db->prepare("DELETE FROM events where msisdn='".$params['msisdn']."'");
        $sth1 = $this->db->prepare("DELETE FROM event_tickets where msisdn='".$params['msisdn']."'");
  		  $sth->execute();
  		  $sth1->execute();
        $xml=null;
        $menu=null;
        $i=1;
    	foreach($array as $key=>$value){
        $postData[$i]['ref_id'] =$i;
     	  $postData[$i]['name'] =$value[$i-1]['name'];
     	  $postData[$i]['venue'] =$value[$i-1]['venue'];
     	  $postData[$i]['time'] =$value[$i-1]['time'];
        $postData[$i]['msisdn'] =$params['msisdn'];
        $postData[$i]['session_id'] =$params['sessionId'];

      $this->db->InsertData("events", $postData[$i]);
         $j=1;
      	foreach($value[$i-1]['prices'] as $key1=>$prices){
       $TicketData['event_ref'] =$i;
       $TicketData['ref_id'] =$j;
       $TicketData['api_id'] =$prices['price_id'];
       $TicketData['name'] =$prices['ticket_category'];
       $TicketData['amount'] =$prices['ticket_price'];
       $TicketData['msisdn'] =$params['msisdn'];
       $TicketData['session_id'] =$params['sessionId'];
      $this->db->InsertData("event_tickets", $TicketData);
        $j++;
          }
     //	$xml .=$i.') '.$value[$i-1]['name']." - ".$value[$i-1]['venue'].PHP_EOL;
     	$xml .=$i.') '.$value[$i-1]['name'].PHP_EOL;
        $i++;
    		}
        $menu['events'] = $xml;
        //  print_r($menu);die();
    	 return $menu;
      }



}

?>
