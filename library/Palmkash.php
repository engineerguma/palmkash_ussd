<?php

class Palmkash extends Model {

    function __construct() {
        parent::__construct();
        $this->kash = new CorePalmkash();
        $this->log = new Logs();
    }



    function CheckMsidn($params) {
                 return $this->db->SelectData("SELECT * FROM palm_user_account WHERE msisdn='".$params['msisdn']."' AND status='active' ");
    }


function getUserInput($params,$inputvalue){


  $res = $this->db->SelectData("SELECT MAX(record_id) as record_id FROM palm_log_session_input_values WHERE session_id='".$params['sessionId']."' AND input_name='".$inputvalue."' ");

return $res[0]['record_id'];
}


function GetSavedSelections($params){


  $res = $this->db->SelectData("SELECT * FROM betting_selection_store WHERE session_id='".$params['sessionId']."' AND msisdn='".$params['msisdn']."' ");

return $res;
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

        $res = $this->db->SelectData("SELECT * FROM palm_log_session_input_values WHERE record_id='".$this->getUserInput($params,'reg_language')."' ");
       $lang= $res[0]['input_value'];
        $this->kash->mod->SetLanguagePref($params,$lang);
         $response=1;
        return $response;
    }


    function ProcessLanguageChange($params) {

        $res = $this->db->SelectData("SELECT * FROM palm_log_session_input_values WHERE record_id='".$this->getUserInput($params,'laguange_select')."' ");
       $lang= $res[0]['input_value'];
        $this->kash->mod->SetLanguagePref($params,$lang);
        $this->kash->mod->UpdateLanguagePref($params,$lang);
         $response=1;
        return $response;
    }


    function ProcessUserRegistration($params) {
      $lang = $this->db->SelectData("SELECT * FROM palm_log_session_input_values WHERE record_id='".$this->getUserInput($params,'reg_language')."' ");
      $fname = $this->db->SelectData("SELECT * FROM palm_log_session_input_values WHERE record_id='".$this->getUserInput($params,'first_name')."' ");
      $onames = $this->db->SelectData("SELECT * FROM palm_log_session_input_values WHERE record_id='".$this->getUserInput($params,'first_name')."' ");
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

    function ProcessGetOriginByName($params) {

        $res = $this->db->SelectData("SELECT * FROM palm_log_session_input_values WHERE record_id='".$this->getUserInput($params,'origin_station_search')."' ");
        $params['departure_station']=$res[0]['input_value'];
        $response = $this->kash->GetStartStationsByName($params);
        //print_r($response);die();
        if(isset($response['status'])&&strtolower($response['status'])=='success'){
      	$return_response=$this->SaveStartEndStationsReference($params,$response,'start');
      	}else{
      	  $menu=null;
          $menu['state']='FC';
        	$msg_text ='Station Not found'.PHP_EOL;
          $msg_text .='Enter Start Station'.PHP_EOL;
          $menu['msg_response'] = $msg_text;
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
        if(isset($response['status'])&&strtolower($response['status'])=='success'){
      	$return_response=$this->SaveStartEndStationsReference($params,$response,'end');
      	}else{
      	  $menu=null;
          $menu['state']='FC';
        	$msg_text ='Station Not found'.PHP_EOL;
          $msg_text .='Enter End Station'.PHP_EOL;
          $menu['msg_response'] = $msg_text;
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
        if(isset($response['status'])&&strtolower($response['status'])=='success'){
      	$return_response=$this->SaveBookingTImesReference($params,$response,'end');
      	}else{
      	  $menu=null;
          $menu['state']='FB';
        	$msg_text ='No Available routes'.PHP_EOL;
          $msg_text .='No Available routes'.PHP_EOL;
          $menu['msg_response'] = $msg_text;
      	 $this->OperationWatch($params,4);
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

		     $response='';
         $msg_text .=$start_id[0]['station_name'].'-'.$end_id[0]['station_name'].PHP_EOL;
         $msg_text .=$route_time[0]['time'].PHP_EOL;
         $msg_text .='Tickets: '.$tickets[0]['input_value'].PHP_EOL;
         $msg_text .='Amount: '.number_format($route_time[0]['price']).PHP_EOL;

	    	$response['booking_info'] = $msg_text;
        return $response;
    }


      function ProcessBookingRequest($params) {
        $start = $this->db->SelectData("SELECT * FROM palm_log_session_input_values WHERE record_id='".$this->getUserInput($params,'origin_station')."' ");
        $end = $this->db->SelectData("SELECT * FROM palm_log_session_input_values WHERE record_id='".$this->getUserInput($params,'destination')."' ");

        $route = $this->db->SelectData("SELECT * FROM palm_log_session_input_values WHERE record_id='".$this->getUserInput($params,'departure_time')."' ");
        $tickets = $this->db->SelectData("SELECT * FROM palm_log_session_input_values WHERE record_id='".$this->getUserInput($params,'number_of_tickets')."' ");

        $route_time= $this->getRouteReference($params['msisdn'],$route[0]['input_value']);
        //$this->log->ExeLog($params, "Palmkash::ProcessGetConfirmationSummary getRouteReference ".var_export($route_time,true), 2);

         $params['names']=$route_time[0]['route_id'];
         $params['route_id']=$route_time[0]['route_id'];
         $params['route_type']=$route_time[0]['route_type'];
         $params['amount']=$route_time[0]['price'];
         $params['number_of_tickets']=$tickets[0]['input_value'];
         $params['date_of_travel']=date('Y-m-d') ;

         $response = $this->kash->CompleteBookingRequest($params);
         if(isset($response['status'])&&strtolower($response['status'])=='success'){
           $menu=null;
            $menu['state']='FB';
          	$msg_text ='Your Request has been successful'.PHP_EOL;
            $msg_text .='Approve the payment on mobile money'.PHP_EOL;
            $menu['msg_response'] = $msg_text;
            $return_response=$menu;
          	}else{
       	  $menu=null;
           $menu['state']='FB';
           $msg_text .='No Available routes'.PHP_EOL;
           $menu['msg_response'] = $msg_text;
       	 $this->OperationWatch($params,4);
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


}

?>
