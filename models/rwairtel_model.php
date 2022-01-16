<?php

class RWAirtel_Model extends COREUSSD {

    function __construct() {
        parent::__construct();
    }

    function RequestHandler($xml_post, $params) {
//print_r($params);die();
        $status = $this->ManageRequestSession($params);
        $this->log->ExeLog($params, 'Mtnrwanda_Model::Handler ManageRequestSession Returning Status ' . $status, 2);
        $response = $this->MenuOptionHandler($params, $status);
		    $response['sessionId']=$params['sessionId'];
    		if(empty($response['applicationResponse'])){
    		$response['applicationResponse']='Dear Customer, Request due to communication Problem, try again Later';
    		}
        //  $response = $this->WriteResponseXML($response);
        //$response = $this->lion->WriteGeneralXMLFile($params,'uganda_response',$response);
        $this->log->ExeLog($params, 'Mtnrwanda_Model::Handler Returning XML Response ' . var_export($response,true), 3);

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
