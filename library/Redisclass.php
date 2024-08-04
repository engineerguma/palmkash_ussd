<?php

class Redisclass {

    function __construct() {
     $this->redis = new Redis();
    }


    function connect() {
    $this->redis->connect(REDIS_HOST, REDIS_PORT);
    $this->redis->auth(REDIS_PASSWORD);
    }


        public function DisConnect() {

          return $this->redis->close();
        }

        public function DeleteKey($key) {
       $this->connect();
       $reponse =$this->redis->del($key);
       $this->DisConnect();
        return $reponse;
        }


        function KeyExists($key){
         $this->connect();
         $response = $this->redis->exists($key);
       $this->DisConnect();
        return  $response;
        }

 
        function StoreKeyData($key,$value){
          $this->connect();
          $response = $this->redis->SET($key,$value);
          $this->redis->expire($key,SESSION_ID_EXP);
          $this->DisConnect();
           return  $response;
        }

 
        function GetKeyRecord($key){
          $this->connect();
          $response = $this->redis->GET($key);
          $this->DisConnect();
           return  $response;
        }       

       function StoreNameWitValue($key,$name,$value){
         $this->connect();
         $response = $this->redis->HSET($key,$name,$value);
         $this->DisConnect();
          return  $response;
       }

       function GetRecordByValue($key,$value){
        $this->connect();
        $response = $this->redis->HGET($key,$value);
        $this->DisConnect();
         return  $response;
      }

       function GetKeyRecords($key){
         $this->connect();
         $response = $this->redis->HGETALL($key);
         $this->DisConnect();
          return  $response;
       }

       function StoreArrayRecords($key,$array=array()){
         //print_r($array);die();
                  $this->connect();
         $response =  $this->redis->HMSET($key,$array);
             $this->redis->expire($key,SESSION_ID_EXP);
                $this->DisConnect();
          return  $response;
       }

       function StoreCommonInputRecords($key,$array=array()){
         //print_r($array);die();
                  $this->connect();
                  foreach($array as $key_val => $value){      
           $response = $this->redis->HSET($key,$key_val,$value);                   
           // $response =  $this->redis->ZADD($key,$key_val,$value);
                  }
             $this->redis->expire($key,SESSION_ID_EXP);
                $this->DisConnect();
          return  $response;
       }

       function ExpireRecords($key,$seconds=190){

         return $this->redis->expire($key,$seconds);
       }




}
