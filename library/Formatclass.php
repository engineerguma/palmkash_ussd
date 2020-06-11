<?php

class Formatclass {

    function __construct() {
           $this->log = new Logs();
           $this->db = new Database();
    }

    public $_match_up_array = array(
        'type' => 'requesttype',
        'methodName' => 'requesttype',
        'username' => 'username',
        'password' => 'password',
        'timestamp' => 'timestamp',
        'msisdn' => 'msisdn',
        'sessionId' => 'sessionId',
        'session' => 'sessionId',
        'shortcode' => 'subscriberInput',
        'mode' => 'mode',
        'response' => 'subscriberInput',
        'amount' => 'amount',
        'language' => 'language',
        'name' => 'name',
        'string' => 'string',
        'newRequest' => 'newRequest',
        'subscriberInput' => 'subscriberInput',
        'transactionId' => 'transactionId',
        'code'=>'resultcode',
        'response_code' => 'responsecode',
        'returncode' => 'responsecode',
        'statusCode' => 'statusCode',
        'respmessage' => 'respmessage',
        'message' => 'respmessage',
        'status' => 'status',
        'result' => 'result',
        'id' => 'id',
        'station' => 'station',
        'time' => 'time',
        'price' => 'price',
        'route'=>'route',
    );


    function ParseXMLFromURL($url) {
        $xmlp = simplexml_load_file($url);
        $p_array = $this->ObjectToArray($xmlp);
        return $p_array;
    }

   // function ParseXMLRequest($xml_post, $level = false, $source = false, $serv_id = false, $array = false) {
    function ParseXMLRequest($xml_post, $level = false) {


        if ($level) {
            $doc = new DOMDocument();
            libxml_use_internal_errors(true);
            $doc->loadHTML($xml_post);
            libxml_clear_errors();
            $xmln = $doc->saveXML($doc->documentElement);
        } else {
            $xmln = $xml_post;
        }
        $xmlp = simplexml_load_string($xmln);
        $p_array = $this->ObjectToArray($xmlp);

        $request_array = $this->ArrayFlattener($p_array);
        $standard_array = $this->Standardize($request_array);
        return $standard_array;
    }

    function Standardize($data_array) {
        //Convert to Single
        $result_array = array();
        foreach ($data_array as $key => $value) {
            $standard_key = $this->_match_up_array[$key];
            if (!empty($standard_key)) {
                $result_array[$standard_key] = $value;
            }
        }
        return $result_array;
    }

    function ObjectToArray($obj) {
        if (!is_array($obj) && !is_object($obj))
            return $obj;
        if (is_object($obj))
            $obj = get_object_vars($obj);
        return array_map(__METHOD__, $obj);
    }

    function ArrayFlattener($array) {
        if (!is_array($array)) {
            return FALSE;
        }
        $result = array();
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $result = array_merge($result, $this->ArrayFlattener($value));
            } else {
                $result[$key] = $value;
            }
        }
        return $result;
    }




}
