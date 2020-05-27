<?php

class Mtnrwanda_Model extends COREUSSD {

    function __construct() {
        parent::__construct();
    }

    function RequestHandler($xml_post, $params) {

        $status = $this->ManageRequestSession($params);
        $this->log->ExeLog($params, 'Mtnrwanda_Model::Handler ManageRequestSession Returning Status ' . $status, 2);
        $response = $this->MenuOptionHandler($params, $status);
		    //$response['sessionId']=$params['sessionId'];
        //   print_r($response);die();
    		if(empty($response['applicationResponse'])){
    		$response['applicationResponse']='Dear Customer, Request due to communication Problem, try again Later';
    		}

          $response = $this->WriteResponseXML($response);
        //$response = $this->lion->WriteGeneralXMLFile($params,'uganda_response',$response);
        $this->log->ExeLog($params, 'Mtnrwanda_Model::Handler Returning XML Response ' . $response, 3);

        return $response;
    }



      function InterpreteRequest($xml_post) {
            $standard_array = $this->format->ParseXMLRequest($xml_post);
            return $standard_array;
      }



}
