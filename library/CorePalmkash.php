<?php

class CorePalmkash {

    function __construct() {
        $this->mod = new Model();
        $this->log = new Logs();
        $this->format = new Formatclass();

    }

     function VerifyRegistration($params){
       $routing =$this->mod->getMerchantRouting('transport');

        $req = array(
            'request_method' => 'CheckRegistrationRequest',
            'token' => $routing[0]['merchant_token'],
            'msisdn' => $params['msisdn'],
        );
           $url_data = array(
          "url"=>$routing[0]['merchant_url'],
          "method" => 'POST',
        );
        $response = $this->CompleteRequest($params, $req, $url_data,$header_extras=array());
        return $response;
    }

     function CompleteRegistrationRequest($params){
       $routing =$this->mod->getMerchantRouting('transport');

        $req = array(
            'request_method' => 'RegistrationRequest',
            'token' => $routing[0]['merchant_token'],
            'msisdn' => $params['msisdn'],
        );
           $url_data = array(
          "url"=>$routing[0]['merchant_url'],
          "method" => 'POST',
        );
        $response = $this->CompleteRequest($params, $req, $url_data,$header_extras=array());
        return $response;
    }

      function GetStartStationsByName($params){
        $routing =$this->mod->getMerchantRouting('transport');

        $req = array(
            'request_method' => 'GetStartStationsByName',
            'departure_station' => $params['departure_station'],
            'token' => $routing[0]['merchant_token'],
            'msisdn' => $params['msisdn'],
        );
           $url_data = array(
          "url"=>$routing[0]['merchant_url'],
          "method" => 'POST',
        );
        $response = $this->CompleteRequest($params, $req, $url_data,$header_extras=array());
        return $response;
        }

      function GetDestinationStationsByName($params){
        $routing =$this->mod->getMerchantRouting('transport');
        $req = array(
            'request_method' => 'GetEndStationsByName',
            'from_station_id' => $params['start_station_id'],
            'destination_station' => $params['destination_station'],
            'token' => $routing[0]['merchant_token'],
            'msisdn' => $params['msisdn'],
        );
           $url_data = array(
          "url"=>$routing[0]['merchant_url'],
          "method" => 'POST',
        );
        $response = $this->CompleteRequest($params, $req, $url_data,$header_extras=array());
        return $response;
        }


      function GetBookingTimes($params){
        $routing =$this->mod->getMerchantRouting('transport');
        $req = array(
              'request_method' => 'GetRouteTimes',
              'from_station_id' => $params['start_station_id'],
              'to_station_id' => $params['end_station_id'],
              'token' => $routing[0]['merchant_token'],
              'msisdn' => $params['msisdn'],
        );
           $url_data = array(
          "url"=>$routing[0]['merchant_url'],
          "method" => 'POST',
        );
        $response = $this->CompleteRequest($params, $req, $url_data,$header_extras=array());
      return $response;
      }

      function GetAvailableBookingDays($params){
        $routing =$this->mod->getMerchantRouting('transport');
        $req = array(
            'request_method' => 'GetBalanceRequest',
            'token' => $routing[0]['merchant_token'],
            'msisdn' => $params['msisdn'],
        );
           $url_data = array(
          "url"=>$routing[0]['merchant_url'],
          "method" => 'POST',
        );
        $response = $this->CompleteRequest($params, $req, $url_data,$header_extras=array());
        return $response;
        }

     function CompleteBookingRequest($params){
       $routing =$this->mod->getMerchantRouting('transport');
       $req = array(
          "request_method"=>"MakeBooking",
          "route_id" => $params['route_id'],
          "route_type" =>$params['route_type'],
          "amount" => $params['amount'],
          "number_of_tickets" => $params['number_of_tickets'],
          'token' => $routing[0]['merchant_token'],
          'msisdn' => $params['msisdn'],
          "name" => $params['names'],
          "language" => $params['language'],
          "date_of_travel"=>$params['date_of_travel']
        );
        $url_data = array(
          "url"=>$routing[0]['merchant_url'],
          "method" => 'POST',
        );
        $response = $this->CompleteRequest($params, $req, $url_data,$header_extras=array());
        return $response;
    }

///Events


      function GetEventCategories($params=false){
        $routing =$this->mod->getMerchantRouting('events');
        $req = array(
              'request_method' => 'GetEventCategories',
              'token' => $routing[0]['merchant_token'],
              'msisdn' => $params['msisdn'],
        );
           $url_data = array(
          "url"=>$routing[0]['merchant_url'],
          "method" => 'POST',
        );
        $response = $this->CompleteRequest($params, $req, $url_data,$header_extras=array());
      return $response;
      }

      function GetEventsByCategory($params){
        $routing =$this->mod->getMerchantRouting('events');
        $req = array(
            'request_method' => 'GetEventsByCategory',
            'event_category_id' => $params['category_id'],
            'token' => $routing[0]['merchant_token'],
            'msisdn' => $params['msisdn'],
        );
           $url_data = array(
          "url"=>$routing[0]['merchant_url'],
          "method" => 'POST',
        );
        $response = $this->CompleteRequest($params, $req, $url_data,$header_extras=array());
        return $response;
        }

     function CompleteEventsBookingRequest($params){
       $routing =$this->mod->getMerchantRouting('events');
       $req = array(
          "request_method"=>"MakeBooking",
          "amount" => $params['amount'],
          "number_of_tickets" => $params['number_of_tickets'],
          'token' => $routing[0]['merchant_token'],
          'msisdn' => $params['msisdn'],
          "name" => $params['names'],
          "language" => $params['language'],
          "price_id"=>$params['price_id']
        );
        $url_data = array(
          "url"=>$routing[0]['merchant_url'],
          "method" => 'POST',
        );
        $response = $this->CompleteRequest($params, $req, $url_data,$header_extras=array());
        return $response;
    }

////////////SCHOOL
  ///Pocket Money
function ProcessGetPMStudentDetails($params){
  $routing =$this->mod->getMerchantRouting('pocket_money');
  $req = array(
      'request_method' => 'GetStudent',
      'student_id' => $params['account_number'],
      'token' => $routing[0]['merchant_token'],
      'msisdn' => $params['msisdn'],
  );
     $url_data = array(
    "url"=>$routing[0]['merchant_url'],
    "method" => 'POST',
  );
  $response = $this->CompleteRequest($params, $req, $url_data,$header_extras=array());
  return $response;
  }

function CompletePocketMoneyPayment($params){
 $routing =$this->mod->getMerchantRouting('pocket_money');
 $req = array(
    "request_method"=>"MakePayment",
    "amount" => $params['amount'],
    'msisdn' => $params['msisdn'],
    "student_id" => $params['account_number'],
    "language" => $params['language'],
    'token' => $routing[0]['merchant_token'],
  );
  $url_data = array(
    "url"=>$routing[0]['merchant_url'],
    "method" => 'POST',
  );
  $response = $this->CompleteRequest($params, $req, $url_data,$header_extras=array());
  return $response;
}



////////////STUDENT SCHOOL FEES TRANSPORT

     function ProcessGetStudentDetails($params){

     $routing =$this->mod->getMerchantRouting($params['merchant']);
       //print_r($routing);die();
        $url_data = array(
          "url"=>$routing[0]['merchant_url'].'api/v1/student/'.$params['account_number'],
          "method" => 'GET',
        );

      //  $header_extras = ['Content-Type: application/json'];
        $header_extras = ['Authorization: Bearer '.$routing[0]['merchant_token']];
      $response = $this->CompleteRequest($params, $req=array(),$url_data,$header_extras);
        return $response;
    }

     function CompleteSchoolfeesTransportPayment($params){

     $routing =$this->mod->getMerchantRouting($params['merchant']);
       //print_r($routing);die();
        $req_data = array(
          "token" => $routing[0]['gateway_token'],
          "transaction_amount" => $params['amount'],
          "account_number" => $params['account_number'],
          "transaction_account" => $params['msisdn'],
          "merchant_account" => $routing[0]['gateway_account'],
          "transaction_source" => 'ussd',
          "transaction_reference_number" => 'ussd'.$this->genRandStr(),
          "transaction_reason" => $params['reason'],
          "currency" => 'RWF',
        );

        $url_data = array(
          "url"=>$routing[0]['gateway_url'],
          "method" => 'POST',
        );

        //removed
//payment_operator,transaction_destination

      $response = $this->CompleteRequest($params, $req_data,$url_data,$header_extras = []);
        return $response;
    }

    function genRandStr(){
      $a = $b = '';

      for($i = 0; $i < 3; $i++){
        $a .= chr(mt_rand(65, 90)); // see the ascii table why 65 to 90.
        $b .= mt_rand(0, 99);
      }
      return $a . $b;
    }


    /*     * *************************************************************************
     *
     * Authentication & Transaction Request & Response Processing Functions
     *
     * ************************************************************************* */

    function CompleteRequest($params, $request,$url_data,$header_extras) {
      if($url_data['method']=='POST'){
       $this->log->ExeLog($params, 'CorePalmKash::CompleteRequest  Request Data ' . var_export($request, true), 2);
        //  $request['token']=WALLET_TOKEN;
        $json_request=json_encode($request);
        //   print_r($json_request);die();
        $this->log->ExeLog($params, 'CorePalmKash::CompleteRequest Preparing to send XML Request ' . $json_request . ' To ' . $url_data['url'], 2);
        $result = $this->SendJSONByCURL($url_data['url'],$params, $json_request,$header_extras);
      }else{
        $result = $this->mod->SendGetByCURL($url_data['url'],$params,$header_extras);
      }
        $this->log->ExeLog($params, 'CorePalmKash::CompleteRequest SendByCURL Response XML ' . $result, 2);

            $response = $this->ParseRequest($params, $result);

        if(empty($response)){
          return  array(
                       'error' =>'API ERROR, Notify Admin',
                    );
        }else{
        return $response;
        }
        $this->log->ExeLog($params, 'CorePalmKash::CompleteRequest Response From BETLION_CONNECT ' . var_export($response, true), 2);
        return $response;
    }


   function SendJSONByCURL($url,$params,$post_,$extra_headers) {
        $this->log->ExeLog($params,'CorePalmKash::SendJSONByCURL Sending ' . $post_ . ' To ' . $url, 2);
        $cont_len = strlen($post_);

          $header=['Content-Type: application/json',
          'cache-control: no-cache',
          'Content-Length: ' . $cont_len];
          if(!empty($extra_headers)){
          $header = array_merge($header,$extra_headers);
          }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $content = curl_exec($ch);
        if (!curl_errno($ch)) {
            $info = curl_getinfo($ch);
            $log = 'Took ' . $info['total_time'] . ' seconds to send a request to ' . $info['url'];
        } else {
            $log = 'Curl error: ' . curl_error($ch);
        }
        $this->log->ExeLog($params,'CorePalmKash::SendJSONByCURL Returning ' . $log, 2);

	  $this->log->ExeLog($params,'CorePalmKash::SendJSONByCURL response content '. var_export($content, true), 2);
        return $content;
    }

    function ParseRequest($params, $json) {
		//print_r($xml);die();
        $this->log->ExeLog($params, 'CorePalmKash::ParseRequest response xml' . $json, 2);
        try {

            $array=json_decode($json,true);
            //$array='';
            if(empty($array)){
            $standard_array =array(
               'error' =>'API ERROR, Notify Admin',
            );
            }else{
              $standard_array = $this->format->Standardize($array,$params);
            }
            return $standard_array;
        } catch (Exception $ex) {
            $this->log->ExeLog($params, 'CorePalmKash::ParseRequest unable to parse Response. Throwing Exception ' . $ex, 2);
            $standard_array =array(
               'error' =>'API ERROR, Notify Admin',
            );
          return $standard_array;
        }
    }

}
