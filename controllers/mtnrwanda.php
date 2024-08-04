<?php

class Mtnrwanda extends Controller {

    function __construct() {
        parent::__construct();
    }


        function Index() {

            $request = file_get_contents('php://input');
            if (empty($request)) {
              echo "Invalid Access";
              echo "<br/>";
              echo "<br/>";
            } else {
                $standard_array = $this->model->InterpreteRequest($request);
                $standard_array['operator'] = 'mtnrwanda';
                if ($standard_array['requesttype'] == 'pull') {
                    $standard_array['session_key'] = $standard_array['msisdn'].'_'.$standard_array['sessionId'];
                    $response_xml = $this->model->RequestHandler($request, $standard_array);
                    header('Content-Type: application/xml; charset=UTF-8');
                    echo $response_xml;

                }
                if (strtolower($standard_array['requesttype']) == 'cleanup') {
              $this->model->log->ExeLog($standard_array, " Mtnrwanda::Session Cleanup request for session ID ".$standard_array['sessionId']." for msisdn ".$standard_array['msisdn'] ,3);
                    $this->model->SessionCleanUp($standard_array);
                    exit();
                }
            }
        }
}
