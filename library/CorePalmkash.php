<?php

class CorePalmkash {

    function __construct() {
        $this->mod = new Model();
        $this->log = new Logs();
        $this->format = new Formatclass();

    }

     function VerifyRegistration($params){
        $req = array(
            'request_method' => 'CheckRegistrationRequest',
            'msisdn' => $params['msisdn'],
        );
        $response = $this->CompleteRequest($params, $req, 1);
        return $response;
    }

     function CompleteRegistrationRequest($params){
        $req = array(
            'request_method' => 'RegistrationRequest',
            'msisdn' => $params['msisdn'],
        );
        $response = $this->CompleteRequest($params, $req, 1);
        return $response;
    }

      function GetStartStationsByName($params){
        $req = array(
            'request_method' => 'GetStartStationsByName',
            'departure_station' => $params['departure_station'],
            'msisdn' => $params['msisdn'],
        );
        $response = $this->CompleteRequest($params, $req, 1);
        return $response;
        }

      function GetDestinationStationsByName($params){
        $req = array(
            'request_method' => 'GetEndStationsByName',
            'from_station_id' => $params['start_station_id'],
            'destination_station' => $params['destination_station'],
            'msisdn' => $params['msisdn'],
        );
        $response = $this->CompleteRequest($params, $req, 1);
        return $response;
        }


      function GetBookingTimes($params){
        $req = array(
              'request_method' => 'GetRouteTimes',
              'from_station_id' => $params['start_station_id'],
              'to_station_id' => $params['end_station_id'],
              'msisdn' => $params['msisdn'],
        );
      $response = $this->CompleteRequest($params, $req, 1);
      return $response;
      }

      function GetAvailableBookingDays($params){
        $req = array(
            'request_method' => 'GetBalanceRequest',
            'msisdn' => $params['msisdn'],
        );
        $response = $this->CompleteRequest($params, $req, 1);
        return $response;
        }

     function CompleteBookingRequest($params){
        $req = array(
          "request_method"=>"MakeBooking",
          "route_id" => $params['route_id'],
          "route_type" =>$params['route_type'],
          "amount" => $params['amount'],
          "number_of_tickets" => $params['number_of_tickets'],
          "msisdn"=> $params['msisdn'],
          "name" => $params['names'],
          "date_of_travel"=>$params['date_of_travel']
        );
        $response = $this->CompleteRequest($params, $req, 1);
        return $response;
    }



    /*     * *************************************************************************
     *
     * Authentication & Transaction Request & Response Processing Functions
     *
     * ************************************************************************* */

    function CompleteRequest($params, $request, $resp = false) {
        $this->log->ExeLog($params, 'CorePalmKash::CompleteRequest Fired For ' . $request['request_method'] . ' With Data ' . var_export($request, true), 2);
         $json_request=json_encode($request);
        //   print_r($json_request);die();
        $this->log->ExeLog($params, 'CorePalmKash::CompleteRequest Preparing to send XML Request ' . $json_request . ' To ' . PALMKASH_TRANSPORT, 2);
        $result = $this->SendJSONByCURL(PALMKASH_TRANSPORT,$params, $json_request);
      /*
        $result =array(
        "status"=>"success",
        "result" =>array(
        array("id"=>13,
    "station"=>"Rusumo",
         ),
      array(
        "id"=>17,
        "station"=>"Rusizi",
        )
          ));
  $result =json_encode($result);*/
        $this->log->ExeLog($params, 'CorePalmKash::CompleteRequest SendByCURL Response XML ' . $result, 2);
        if ($resp == 1) {
            $response = $this->ParseRequest($params, $result);
        }
        $this->log->ExeLog($params, 'CorePalmKash::CompleteRequest Response From BETLION_CONNECT ' . var_export($response, true), 2);
        return $response;
    }

    function TimeStampPWEnc($pw) {
        $ts = date('YmdGis');
        $data['ts'] = $ts;
        $data['enc_pw'] = hash_hmac('MD5', $pw, $ts);
        return $data;
    }

    function WriteGeneralXMLFile($params,$temp, $trans_data) {

       // $this->log->ExeLog($params, 'CorePalmKash::WriteGeneralXMLFile Data to write XML'. var_export($trans_data, true), 2);

        $f_template = $temp;
        $template = 'templates/' . $temp . '.php';
        require($template);
        $trans_xml = ${$f_template};
        //$this->log->ExeLog($params, 'CorePalmKash::WriteGeneralXMLFile File For ' . $params['requesttype'] . ' Saved Under ' . $trans_xml, 2);
        return $trans_xml;
    }

   function SendJSONByCURL($url,$params,$xml) {
        $this->log->ExeLog($params,'CorePalmKash::SendJSONByCURL Sending ' . $xml . ' To ' . $url, 2);
        $cont_len = strlen($xml);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'cache-control: no-cache', 'Content-Length: ' . $cont_len));
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
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
            $standard_array = $this->format->Standardize($array,$params);
            return $standard_array;
        } catch (Exception $ex) {
            $this->log->ExeLog($params, 'CorePalmKash::ParseRequest unable to parse XML. Throwing Exception ' . $ex, 2);
        }
    }

}
