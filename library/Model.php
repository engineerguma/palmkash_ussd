<?php

class Model
{
    public Formatclass $format;
    public Logs $log;
    public $db;
    public Redisclass $redis;

    public function __construct()
    {
        $this->format = new Formatclass();
        $this->log = new Logs();
        $this->db = Database::getInstance();
        $this->redis = new Redisclass();
    }

    public function getMerchantRouting($key)
    {
        return $this->db->SelectData(
            "SELECT * FROM palm_ussd_merchant WHERE search_key = :search_key",
            ['search_key' => $key]
        );
    }

    public function CheckSessionID($params)
    {
        if (empty($params['sessionId'])) {
            return [];
        }

        return $this->db->SelectData(
            "SELECT * FROM palm_log_session_data 
             WHERE session_status = 'active' AND session_id = :ssn",
            ['ssn' => $params['sessionId']]
        );
    }

    public function SessionCleanUp($params): bool
    {
        if (empty($params['sessionId'])) {
            return false;
        }

        $keys = $this->redis->GetMatchingKeys($params['sessionId']);

        foreach ($keys as $key) {
            $this->redis->DeleteforMultiple($key);
        }

        return true;
    }

    public function SessionExists($req_params): bool
    {
        if (empty($req_params['session_key'])) {
            return false;
        }

        return $this->redis->KeyExists($req_params['session_key']);
    }

    public function GetSessionRecords($sessionkey): array
    {
        if (empty($sessionkey)) {
            return [];
        }

        return $this->redis->GetKeyRecords($sessionkey);
    }

    public function SetLanguagePref($params, $lang)
    {
        if (empty($params['session_key'])) {
            return false;
        }

        $language = '';

        if (is_array($lang) && isset($lang['language'])) {
            $language = $lang['language'];
        } elseif ($lang == '1') {
            $language = 'kin';
        } elseif ($lang == '2') {
            $language = 'en';
        }

        if ($language === '') {
            return false;
        }

        $this->redis->StoreNameWitValue(
            $params['session_key'],
            'session_language_pref',
            $language
        );

        return $language;
    }

    public function OperationWatch($params, $stateid = false): void
    {
        $res = $this->GetCurrentLogstate($params);

        $this->log->ExeLog(
            $params,
            "Model::OperationWatch GetCurrentLogstate response " . var_export($res, true),
            2
        );

        $this->SetCurrentState($res, $params, $stateid);
    }

    public function SetCurrentState($res, $params, $stateid = false): bool
    {
        if (empty($params['session_key']) || empty($params['sessionId']) || empty($params['msisdn'])) {
            return false;
        }

        $this->log->ExeLog(
            $params,
            "Model::SetCurrentState state ID is " . var_export($stateid, true) .
            " AND res is " . var_export($res, true),
            2
        );

        if (empty($res)) {
            $postCS = [
                'session_id' => $params['sessionId'],
                'telephone_number' => $params['msisdn'],
                'current_state' => 1
            ];
        } else {
            $postCS = [
                'previous_state' => $res['current_state'] ?? '',
                'current_state' => $stateid
            ];
        }

        $this->log->ExeLog(
            $params,
            "Model::SetCurrentState Post Data " . var_export($postCS, true),
            2
        );

        return $this->redis->StoreArrayRecords($params['session_key'] . '_current_state', $postCS);
    }

    public function GetCurrentLogstate($params): array
    {
        if (empty($params['session_key'])) {
            return [];
        }

        return $this->redis->GetKeyRecords($params['session_key'] . '_current_state');
    }

    public function GetCurrentState($params, $state_id = false)
    {
        if ($state_id == false) {
            $res = $this->GetCurrentLogstate($params);
            $state_id = $res['current_state'] ?? false;
        }

        if ($state_id == false) {
            return [];
        }

        return $this->db->SelectData(
            "SELECT * FROM palm_ussd_states WHERE state_id = :st_id",
            ['st_id' => $state_id]
        );
    }

    public function StoreInputValues($params, $curr_state): bool
    {
        if (
            empty($params['session_key']) ||
            !isset($params['subscriberInput']) ||
            empty($curr_state['current_state']) ||
            empty($curr_state['input_field_name'])
        ) {
            return false;
        }

        $key = $params['session_key'] . '_input_values';
        $current_values = $this->redis->GetKeyRecord($key);

        $postData = [
            'date' => date('Y-m-d G:i:s'),
            'state_id' => $curr_state['current_state'],
            'input_name' => $curr_state['input_field_name'],
            'input_value' => $params['subscriberInput']
        ];

        $this->log->ExeLog(
            $params,
            "Model::StoreInputValues post data " . var_export($postData, true),
            2
        );

        if (empty($current_values)) {
            $mult = [$postData];
        } else {
            $unserialized = @unserialize($current_values);

            if (!is_array($unserialized)) {
                $unserialized = [];
            }

            $unserialized[] = $postData;
            $mult = $unserialized;
        }

        $serialized = serialize($mult);

        $this->log->ExeLog(
            $params,
            "Model::StoreInputValues serialized data " . var_export($serialized, true),
            2
        );

        return $this->redis->StoreKeyData($key, $serialized);
    }

    public function GetSession($params)
    {
        if (empty($params['sessionId']) || empty($params['msisdn'])) {
            return [];
        }

        return $this->db->SelectData(
            "SELECT * FROM palm_log_session_data 
             WHERE session_status = 'active' 
             AND session_id = :ssn 
             AND telephone_number = :tn",
            [
                'ssn' => $params['sessionId'],
                'tn' => $params['msisdn']
            ]
        );
    }

    public function GetSessionLanguage($params)
    {
        if (empty($params['sessionId'])) {
            return [];
        }

        return $this->db->SelectData(
            "SELECT * FROM palm_log_session_data WHERE session_id = :sid",
            ['sid' => $params['sessionId']]
        );
    }

    public function GetNextState($cs, $pc)
    {
        return $this->db->SelectData(
            "SELECT * FROM palm_ussd_choices 
             WHERE ussd_state = :cs AND ussd_choice = :pc",
            [
                'cs' => $cs,
                'pc' => $pc
            ]
        );
    }

    public function GetResponseMsg($error)
    {
        return $this->db->SelectData(
            "SELECT * FROM palm_ussd_response_codes WHERE error_code = :error",
            ['error' => $error]
        );
    }

    public function GetResponseMsgByStatus($error)
    {
        return $this->db->SelectData(
            "SELECT * FROM palm_ussd_response_codes WHERE status_code = :status_code",
            ['status_code' => $error]
        );
    }

    public function GetStateFull($state)
    {
        return $this->db->SelectData(
            "SELECT * FROM palm_ussd_states s 
             LEFT OUTER JOIN palm_ussd_states_text t ON s.state_id = t.state_id 
             WHERE s.state_id = :id",
            ['id' => $state]
        );
    }

    public function getRegistration($params)
    {
        if (empty($params['msisdn'])) {
            return [];
        }

        return $this->db->SelectData(
            "SELECT * FROM palm_user_account WHERE msisdn = :msisdn",
            ['msisdn' => $params['msisdn']]
        );
    }

    public function SaveUserRegistration($params): bool
    {
        if (
            empty($params['first_name']) ||
            empty($params['last_name']) ||
            empty($params['msisdn']) ||
            empty($params['language'])
        ) {
            return false;
        }

        $save = [
            'first_name' => $params['first_name'],
            'last_name' => $params['last_name'],
            'msisdn' => $params['msisdn'],
            'language' => $params['language']
        ];

        $this->db->InsertData('palm_user_account', $save);

        return true;
    }

    public function SaveAddress($params, $stateid): bool
    {
        if (empty($params['address']) || empty($stateid)) {
            return false;
        }

        $postCS = [
            'address' => $params['address']
        ];

        $this->db->UpdateData(
            'palm_user_account',
            $postCS,
            "account_id = {$stateid}"
        );

        return true;
    }

    public function UpdateLanguagePref($params, $lang): bool
    {
        if (empty($params['msisdn'])) {
            return false;
        }

        $language = '';

        if (isset($params['session_language_pref'])) {
            $language = $params['session_language_pref'];
        } elseif ($lang == '1') {
            $language = 'kin';
        } elseif ($lang == '2') {
            $language = 'en';
        }

        if ($language === '') {
            return false;
        }

        $postLang = [
            'language' => $language
        ];

        $this->db->UpdateData(
            'palm_user_account',
            $postLang,
            "msisdn = {$params['msisdn']}"
        );

        return true;
    }

    public function LogPickedOptions($res, $params): bool
    {
        if (empty($params['sessionId']) || empty($params['msisdn'])) {
            return false;
        }

        $records = is_array($res) ? count($res) : 0;

        $postData = [
            'request_time' => date('Y-m-d G:i:s'),
            'session_id' => $params['sessionId'],
            'telephone_number' => $params['msisdn']
        ];

        $this->log->ExeLog(
            $params,
            "Model::LogPickedOptions Post Data " . var_export($postData, true),
            2
        );

        if ($records == 0) {
            $postData['menu_requests'] = $params['subscriberInput'] ?? '';
            $this->db->InsertData('palm_log_session_activity', $postData);
        } else {
            $requeststring = ($res[0]['current_state'] ?? '') . ',' . ($params['subscriberInput'] ?? '');
            $postData['menu_requests'] = $requeststring;

            if (!empty($res[0]['record_id'])) {
                $this->db->UpdateData(
                    'palm_log_session_activity',
                    $postData,
                    "record_id = {$res[0]['record_id']}"
                );
            }
        }

        return true;
    }

    public function WriteResponseXML($array)
    {
        $xml = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><response></response>'
        );

        $this->ArrayToXML($array, $xml);

        return $xml->asXML();
    }

    public function ArrayToXML($array, SimpleXMLElement &$xml): void
    {
        foreach ($array as $key => $value) {
            $key = is_numeric($key) ? 'item' : $key;

            if (is_array($value)) {
                $subnode = $xml->addChild($key);
                $this->ArrayToXML($value, $subnode);
            } else {
                $xml->addChild(
                    $key,
                    htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8')
                );
            }
        }
    }

    public function SendGetByCURL($url, $params, $extra_headers = [])
    {
        $this->log->ExeLog($params, 'Model::SendGetByCURL Sending To ' . $url, 2);

        $ch = curl_init();

        if ($ch === false) {
            $this->log->ExeLog($params, 'Model::SendGetByCURL failed to initialize CURL', 2);
            return false;
        }

        if (!empty($extra_headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $extra_headers);
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_URL => $url,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ]);

        $content = curl_exec($ch);

        if (curl_errno($ch)) {
            $log = 'Curl error: ' . curl_error($ch);
        } else {
            $info = curl_getinfo($ch);
            $log = 'Took ' . $info['total_time'] . ' seconds to send a request to ' . $info['url'];
        }

        curl_close($ch);

        $this->log->ExeLog($params, 'Model::SendGetByCURL Returning ' . $log, 2);
        $this->log->ExeLog($params, 'Model::SendGetByCURL response content ' . var_export($content, true), 2);

        return $content;
    }
}

?>