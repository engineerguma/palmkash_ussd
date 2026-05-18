<?php

class Logs {

    function __construct() {

    }


        function LogXML($sp, $sv,$xml) {

            if(strtolower(APP_LOG)=='trace'){
            $file_ext = microtime();
            $todays_folder = 'systemlog/xml_depo/'. date('Y_m_d');
            $sv_folder = $todays_folder.'/'.$sp;
            $file_name = $sv_folder . '/' . $sp . '_' . $file_ext . '.xml';
           //print_r($file_name);die();
            if (!is_dir($sv_folder)) {
                @mkdir($sv_folder, 0777, true);
            }
            file_put_contents($file_name, $xml . "\n", FILE_APPEND | LOCK_EX);
            return $file_name;

        }

        }

    function ExeLog($sa, $log, $id = false) {
		//print_r($log);die();
        if(strtolower(APP_LOG)=='trace'){
        $todays_folder = 'systemlog/tmp';
        $file_name = $todays_folder . '/palmkash_log_' . date('Y_m_d') .'.txt';

        if (!is_dir($todays_folder)) {
            @mkdir($todays_folder, 0777, true);
        }

        $trace_info = '[' . (isset($sa['operator']) ? $sa['operator'] : 'UNKNOWN') . '|' . (isset($sa['msisdn']) ? $sa['msisdn'] : 'UNKNOWN') . '|' . (isset($sa['sessionId']) ? $sa['sessionId'] : 'UNKNOWN') . '] ';
        $full_log = $trace_info . $log;

        $this->PrepareLog($file_name, $full_log, $id);

        return $file_name;
    }
    }
    
    function InfoLog($sa, $log, $id = false) {
		//print_r($log);die();
        $todays_folder = 'systemlog/tmp';
        $file_name = $todays_folder . '/palmkash_info_' . date('Y_m_d') .'.txt';

        if (!is_dir($todays_folder)) {
            @mkdir($todays_folder, 0777, true);
        }

        $trace_info = '[' . (isset($sa['operator']) ? $sa['operator'] : 'UNKNOWN') . '|' . (isset($sa['msisdn']) ? $sa['msisdn'] : 'UNKNOWN') . '|' . (isset($sa['sessionId']) ? $sa['sessionId'] : 'UNKNOWN') . '] ';
        $full_log = $trace_info . $log;

        $this->PrepareLog($file_name, $full_log, $id);

        return $file_name;
    }

    function PrepareLog($file_name, $log, $level) {
        switch ($level) {
            case 1:
                //Log Start & Date
                file_put_contents($file_name, '[LOG START]' . "\n", FILE_APPEND);
                //Entry
                file_put_contents($file_name, '[' . date('Y-m-d H:i:s') . '] ' . $log . "\n", FILE_APPEND);
                break;
            case 2:
                //Entry
                file_put_contents($file_name, '[' . date('Y-m-d H:i:s') . '] ' . $log . "\n", FILE_APPEND);
                break;
            case 3:
                //Entry
                file_put_contents($file_name, '[' . date('Y-m-d H:i:s') . '] ' . $log . "\n", FILE_APPEND);
                //End Log
                file_put_contents($file_name, '[LOG STOP]' . "\n", FILE_APPEND);
                break;
            default:
                break;
        }
    }

}
