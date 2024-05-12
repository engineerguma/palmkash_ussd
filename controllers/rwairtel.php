<?php

class RWAirtel extends Controller {

    function __construct() {
        parent::__construct();
    }


    function Index() {

    	 $todayDate = date("ymdhis");
         $transID = $todayDate.rand();
         $mytransdata = parse_url($_SERVER['REQUEST_URI']);

		$decodeddata=urldecode($mytransdata['query']);
	  //$this->model->log->ExeLog($standard_array, 'Kenya::Index Initial Request ' . var_export($decodeddata, true), 1);
        if (empty($mytransdata['query'])) {

           echo 'You are not allowed on this location';
           $size = ob_get_length();
            header('HTTP/1.1 200 OK');
            header('Freeflow: FC');
            header('charge: Y');
            header('cpRefId: 12345');
            header('Expires: -1');
            header('Pragma: no-cache');
            header('Cache-Control: max-age=0');
            header('Content-Type: UTF-8');
            header('Content-Length:'.$size);
        } else {
            $standard_array = $this->model->FormatRequest($decodeddata);
		      	$standard_array['operator'] = 'Airtel';
            $standard_array['session_key'] = $standard_array['msisdn'].'_'.$standard_array['sessionId'];

      $this->model->log->ExeLog($standard_array, 'Airtel::Index Function decoded '.$decodeddata.' and Standard Array ' . var_export($standard_array, true), 1);
           $response_xml = $this->model->RequestHandler($mytransdata, $standard_array);
            $response =  $response_xml['applicationResponse'];
                      echo $response;
                      $size = ob_get_length();
                       header('HTTP/1.1 200 OK');
                       header('Freeflow: '.$response_xml['freeflow']['freeflowState']);
                  //     header('charge: N');
                       header('cpRefId: 12345');
                       header('Expires: -1');
                       header('Pragma: no-cache');
                       header('Cache-Control: max-age=0');
                       header('Content-Type: UTF-8');
                       header('Content-Length:'.$size);
          /*  if ($standard_array['requesttype'] == 'USSD.END') {
                $this->model->SessionCleanUp($standard_array);
            }*/
        }
    }
}
