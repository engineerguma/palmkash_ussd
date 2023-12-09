<?php

class Index extends Controller {

    function __construct() {
        parent::__construct();
    }



    function Index() {
    $array = array (
  'status' => 'success',
  'result' =>
  array (
    2 =>
    array (
      'id' => 44,
      'size' => 6,
      'types' =>
      array (
        0 =>
        array (
          'id' => 34,
          'name' => 'URUGARA',
          'image' => 'https://res.cloudinary.com/iwacu-heza/image/upload/v1654373698/rkxhlwashx5onghwvm7g.png',
          'currency' => 'RFW',
          'price' => 10000,
          'created_at' => '2021-06-01T07:39:54.000000Z',
          'updated_at' => '2023-10-12T10:08:38.000000Z',
          'purchase_price' => 45000,
        ),
        1 =>
        array (
          'id' => 44,
          'name' => 'RISANZWE',
          'image' => 'https://res.cloudinary.com/iwacu-heza/image/upload/v1654373701/gpyfptkngodyingeiplr.jpg',
                    'currency' => 'RFW',
          'price' => 12000,
          'created_at' => '2021-11-22T06:29:47.000000Z',
          'updated_at' => '2023-09-18T10:36:19.000000Z',
          'purchase_price' => 55000,
        ),
        3 =>
        array (
          'id' => 194,
          'name' => 'VAN_CYLINDER',
          'image' => 'https://res.cloudinary.com/iwacu-heza/image/upload/v1654374662/fltv3o7rinspbfhsspn4.jpg',
          'currency' => 'RFW',
          'price' => 12000,
          'created_at' => '2022-06-04T20:31:03.000000Z',
          'updated_at' => '2023-12-06T19:14:26.000000Z',
          'purchase_price' => 45000,
        ),
      ),
      'accessories' =>
      array (
      ),
      'price' => 45000,
      'currency' => 'RWF',
      'deleted_at' => NULL,
      'created_at' => '2021-06-01T07:39:54.000000Z',
      'updated_at' => '2023-10-12T10:08:38.000000Z',
      'slug' => '21061563',
      'refill_price' => '10000',
      'bottle_image' => NULL,
  ),
  ),
);
 $keys = array_keys($array['result']);
$products_array = $array['result'][$keys[0]];
 print_r($products_array);
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
 //Number of tickets  (tickets_number) needs Validation.
                $params=array (
                  'requesttype' => 'pull',
                  'subscriberInput' => '2',
                  'sessionId' => '17019017722521595',
                  'msisdn' => '250781301110',
                  'newRequest' => '0',
                  'mode' => 'FE',
                  'operator' => 'mtnrwanda'
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
