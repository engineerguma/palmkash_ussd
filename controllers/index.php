<?php

class Index extends Controller {

    function __construct() {
        parent::__construct();
    }



    function Index() {

            echo "Invalid Access"; die();
        }

        public function sim() {
      		 $todayDate = date("ymdhis");
         $transID = $todayDate.rand();

     $url ='http://localhost/palmkash/palmkash_ussd/mtnrwanda/';
    	 $todayDate = date("ymdhis");
    	 $xml ='<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
         <request type="pull">
         <transactionId>'.$transID.'</transactionId>
          <subscriberInput>2</subscriberInput>
          <sessionId>17155481562511143</sessionId>
          <msisdn>250781301110</msisdn>
          <newRequest>1</newRequest>
          <parameters>
          </parameters>
          <freeflow>
           <mode>FC</mode>
          </freeflow>
         </request>';
          $output= $this->SendByCurl($xml,$url);
         print_r($output);
            die();

        }


             function simulator(){

                $mult = array (
                    0 => 
                    array (
                      'date' => '2024-05-19 23:51:39',
                      'state_id' => '1',
                      'input_name' => 'menu_choice',
                      'input_value' => '4',
                    ),
                    1 => 
                    array (
                      'date' => '2024-05-19 23:52:16',
                      'state_id' => '39',
                      'input_name' => 'menu_choice',
                      'input_value' => '2',
                    ),
                );

                $preserved = array_reverse($mult, true);
                foreach($preserved as $key_val => $value){     
                    if($value['input_name']=='menu_choice'){
                        print_r($preserved[$key_val]); die();
                    }
                }
          print_r($preserved);die();
         // print_r(max(array_column($mult,'input_value')));die();
 //Number of tickets  (tickets_number) needs Validation.
                $params=array (
                  'requesttype' => 'pull',
                  'subscriberInput' => '6',
                  'sessionId' => '1642234858470',
                  'msisdn' => '250781301110',
                  'newRequest' => '1',
                  'mode' => 'FD',
                  'operator' => 'mtnrwanda',
                );
               $this->palm = new Palmkash();
            //   $response= $this->palm->ProcessGetOriginByName($params);
               $response= $this->palm->HomeGaSProcessGetProducts($params);

              print_r($response);
               die();

        }


        public function SendByCurl($url,$xml) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POSTFIELDS,$xml);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, 120);

            $content = curl_exec($ch);

            if (curl_errno($ch)) {
                echo 'Curl error: ' . curl_error($ch);
            }

            curl_close($ch);
            return $content;
        }





}
?>
