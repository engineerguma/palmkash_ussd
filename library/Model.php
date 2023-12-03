<?php

class Model {

    function __construct() {

        $this->format = new Formatclass();
        $this->log = new Logs();
        $this->db = new Database();
        Session::start();
    }

        function getMerchantRouting($key){
            return $this->db->SelectData("SELECT * FROM palm_ussd_merchant WHERE search_key=:search_key", array('search_key' => $key));
        }


    function CheckSessionID($params){
        return $this->db->SelectData("SELECT * FROM palm_log_session_data WHERE session_status='active'
                AND session_id =:ssn ", array('ssn' => $params['sessionId']));
    }

    function GetSession($params){
        return $this->db->SelectData("SELECT * FROM palm_log_session_data WHERE session_status='active'
                AND session_id =:ssn AND telephone_number=:tn", array('ssn' => $params['sessionId'], 'tn' => $params['msisdn']));
    }

    function GetCurrentState($params){
        return $this->db->SelectData("SELECT * FROM palm_log_current_state c JOIN palm_ussd_states s
                ON c.current_state=s.state_id WHERE session_id=:sid AND telephone_number=:tn", array('sid' => $params['sessionId'],'tn' => $params['msisdn']));
    }

    function GetSessionLanguage($params){
        return $this->db->SelectData("SELECT * FROM palm_log_session_data WHERE session_id=:sid",
                array('sid' => $params['sessionId']));
    }

    function GetNextState($cs, $pc){
        return $this->db->SelectData("SELECT * FROM palm_ussd_choices WHERE ussd_state=:cs AND ussd_choice=:pc",
                array('cs' => $cs, 'pc' => $pc));
    }

    function GetResponseMsg($error){
        return $this->db->SelectData("SELECT * FROM palm_ussd_response_codes WHERE error_code=:error",
                array('error' => $error));
    }

    function GetResponseMsgByStatus($error){
      return $this->db->SelectData("SELECT * FROM palm_ussd_response_codes WHERE status_code=:status_code",
                    array('status_code' => $error));
    }

    function GetStateFull($state){
        return $this->db->SelectData("SELECT * FROM palm_ussd_states s LEFT OUTER JOIN palm_ussd_states_text t
                ON s.state_id=t.state_id WHERE s.state_id=:id", array('id' => $state));
    }


    function getRegistration($params){
        return $this->db->SelectData("SELECT * FROM palm_user_account WHERE msisdn=:msisdn", array('msisdn' => $params['msisdn']));
    }


    function OperationWatch($params, $stateid = false) {
        //Check If this is the first Request

    //$this->log->ExeLog($params, "Model::OperationWatch NEXT state." . $stateid, 2);

        $res = $this->db->SelectData("SELECT * FROM palm_log_current_state WHERE session_id=:sid AND telephone_number=:tn",
                array('sid' => $params['sessionId'], 'tn' => $params['msisdn']));
        $this->SetCurrentState($res, $params, $stateid);
        $this->LogPickedOptions($res, $params);
    }

    function SetCurrentState($res, $params, $stateid = false) {



        $records = count($res);
        if ($records == 0) {
            $postCS['session_id'] = $params['sessionId'];
            $postCS['telephone_number'] = $params['msisdn'];
            $postCS['current_state'] = 1;
            $this->db->InsertData('palm_log_current_state', $postCS);
        } else {
          /*  if($res[0]['current_state'] == 1){
                $this->SetLanguagePref($params);
            }*/
            //Already Exists, Do Determination Of Next State
           $prev=$this->GetCurrentState($params);

            $postCS['previous_state'] = $prev[0]['current_state'];
            $postCS['current_state'] = $stateid;
    $this->log->ExeLog($params, "Model::SetCurrentState Post Data." . var_export($postCS, true), 2);

            $this->db->UpdateData('palm_log_current_state', $postCS, "record_id = {$res[0]['record_id']}");
        }
    }

    function SaveUserRegistration($params) {

            $save['first_name'] = $params['first_name'];
            $save['last_name'] = $params['last_name'];
            $save['msisdn'] = $params['msisdn'];
            $save['language'] = $params['language'];
            $this->db->InsertData('palm_user_account', $save);
    }

    function SaveAddress($params, $stateid) {

            $postCS['address'] = $params['address'];
            $this->db->UpdateData('palm_user_account', $postCS, "account_id = {$stateid}");

    }

    function SetLanguagePref($params,$lang){
         $postLang = array();
         if(isset($lang['language'])){
        $postLang['session_language_pref']  = $lang['language'];
          }else{

          if($lang == '1'){
              $postLang['session_language_pref']  = 'kin';
          }elseif($lang == '2'){
              $postLang['session_language_pref']  = 'en';
          }

          }
        $this->db->UpdateData('palm_log_session_data', $postLang, "session_id = {$params['sessionId']}");
      }

    function UpdateLanguagePref($params,$lang){
         $postLang = array();
          if($lang == '1'){
              $postLang['language']  = 'kin';
          }elseif($lang == '2'){
              $postLang['language']  = 'en';
          }
        $this->db->UpdateData('palm_user_account', $postLang, "msisdn = {$params['msisdn']}");
    }



    function LogPickedOptions($res, $params) {
        $records = count($res);
        $postData['request_time'] = date('Y-m-d G:i:s');
      //  $postLog['transaction_id'] = $params['transactionId'];
        $postData['session_id'] = $params['sessionId'];
        $postData['telephone_number'] = $params['msisdn'];

        $this->log->ExeLog($params, "Model::LogPickedOptionsPost Data." . var_export($postData, true), 2);

        if ($records == 0) {
            //This is the initial request.
            $postData['menu_requests'] = $params['subscriberInput'];
            $this->db->InsertData('palm_log_session_activity', $postData);
        } else {
            $requeststring = $res[0]['current_state'] . ',' . $params['subscriberInput'];
            $postData['menu_requests'] = $requeststring;
            $this->db->UpdateData('palm_log_session_activity', $postData, "record_id = {$res[0]['record_id']}");
        }
    }

    function SessionCleanUp($request,$params) {
        $res = $this->db->SelectData("SELECT * FROM palm_log_session_data WHERE telephone_number=:tn AND session_id=:sid",
                array('tn' => $params['msisdn'], 'sid' => $params['sessionId']));
       if(empty($res)==false){
        $postCS = array();
        $postCS['session_status'] = 'closed';
        $postCS['session_close_date'] = date('Y-m-d G:i:s');
        $this->db->UpdateData('palm_log_session_data', $postCS, "record_id = {$res[0]['record_id']}");
      }
    }

    function StoreInputValues($params, $curr_state) {
        $this->log->ExeLog($params, "Model::StoreInputValues Called With Data " . var_export($params, true) . ' And ' . var_export($curr_state, true), 2);
        $postData = array(
            'date' => date('Y-m-d G:i:s'),
            'session_id' => $params['sessionId'],
            'state_id' => $curr_state['current_state'],
            'telephone_number' => $params['msisdn'],
            'input_name' => $curr_state['input_field_name'],
            'input_value' => $params['subscriberInput']
        );
        $this->log->ExeLog($params, "Model::StoreInputValues preparing to post " . var_export($postData, true), 2);
        $this->db->InsertData("palm_log_session_input_values", $postData);

    }


    function WriteResponseXML($array) {
        // create simpleXML object
        $xml = new SimpleXMLElement("<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?><response></response>");
        $this->ArrayToXML($array, $xml);
        return $xml->asXML();
    }

    function ArrayToXML($array, &$xml) {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                if (!is_numeric($key)) {
                    $subnode = $xml->addChild("$key");
                    $this->ArrayToXML($value, $subnode);
                } else {
                    $this->ArrayToXML($value, $xml);
                }
            } else {
              //$xml->addChild("$key",htmlspecialchars($value));
              $xml->$key = $value;
            }
        }
    }


    function SendGetByCURL($url,$params,$extra_headers=array()) {

         $this->log->ExeLog($params,'Model::SendGetByCURL Sending  To ' . $url, 2);
         $ch = curl_init();
         if(!empty($extra_headers)){
         curl_setopt($ch, CURLOPT_HTTPHEADER, $extra_headers);
         }
         curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
         curl_setopt($ch, CURLOPT_URL, $url);
         curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
         curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

         $content = curl_exec($ch);
         if (!curl_errno($ch)) {
             $info = curl_getinfo($ch);
             $log = 'Took ' . $info['total_time'] . ' seconds to send a request to ' . $info['url'];
         } else {
             $log = 'Curl error: ' . curl_error($ch);
         }
        //$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
         $this->log->ExeLog($params,'Model::SendGetByCURL Returning ' . $log, 2);

 	  $this->log->ExeLog($params,'Model::SendGetByCURL response content '. var_export($content, true), 2);
         return $content;
     }

}
?>
