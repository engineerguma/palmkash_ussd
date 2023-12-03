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

     $url ='http://localhost:81/2020/palmkash/mtnrwanda/';
    	 $todayDate = date("ymdhis");
    	 $xml ='<request type="pull">
        <msisdn>0734586934</msisdn>
        <groupcode>2</groupcode>
        <newRequest>1</newRequest>
        <freeflow>
        <status>FC</status>
        </freeflow>
        <sessionId>123456784</sessionId>
        <subscriberInput>737</subscriberInput>
        <transactionId>'.$transID.'</transactionId>
    	</request>' ;
          $output= $this->SendByCurl($url,$url);
         print_r($output);
            die();

        }


             function simulator(){

                $params=array (
                  'requesttype' => 'pull',
                  'subscriberInput' => '1',
                  'sessionId' => '1642234858455',
                  'msisdn' => '2507802446031',
                  'newRequest' => '1',
                  'mode' => 'FD',
                  'operator' => 'mtnrwanda',
        );
               $this->palm = new Palmkash();
            //   $response= $this->palm->ProcessGetOriginByName($params);
               $response= $this->palm->HomeGasCheckRegistration($params);

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
