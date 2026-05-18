<?php

class Redisclass {

    function __construct() {
     $this->redis = new Redis();
     try {
         $this->redis->pconnect(REDIS_HOST, REDIS_PORT);
         // $this->redis->auth(REDIS_PASSWORD);
     } catch (Exception $e) {
         // handle error silently
     }
    }


    function connect() {
        // No-op
    }


    public function DeleteforMultiple($key) {
      $reponse =$this->redis->del($key);
       return $reponse;
       }
        public function DisConnect() {
            // No-op
        }

        public function DeleteKey($key) {
       $reponse =$this->redis->del($key);
        return $reponse;
        }


        function KeyExists($key){
         $response = $this->redis->exists($key);
        return  $response;
        }

 
        function StoreKeyData($key,$value){
          $response = $this->redis->SET($key,$value);
          $this->redis->expire($key,SESSION_ID_EXP);
           return  $response;
        }

 
        function GetKeyRecord($key){
          $response = $this->redis->GET($key);
           return  $response;
        }       

       function StoreNameWitValue($key,$name,$value){
         $response = $this->redis->HSET($key,$name,$value);
         $this->redis->expire($key,SESSION_ID_EXP);
          return  $response;
       }

       function GetRecordByValue($key,$value){
        $response = $this->redis->HGET($key,$value);
         return  $response;
      }

       function GetKeyRecords($key){
         $response = $this->redis->HGETALL($key);
          return  $response;
       }

       function StoreArrayRecords($key,$array=array()){
         //print_r($array);die();
         $response =  $this->redis->HMSET($key,$array);
             $this->redis->expire($key,SESSION_ID_EXP);
          return  $response;
       }

       function StoreCommonInputRecords($key,$array=array()){
         //print_r($array);die();
                  foreach($array as $key_val => $value){      
           $response = $this->redis->HSET($key,$key_val,$value);                   
           // $response =  $this->redis->ZADD($key,$key_val,$value);
                  }
             $this->redis->expire($key,SESSION_ID_EXP);
          return  $response;
       }

       function ExpireRecords($key,$seconds=200){

         return $this->redis->expire($key,$seconds);
       }


       function GetMatchingKeys($key_prefix){
        $response =  $this->redis->keys('*'.$key_prefix.'*');
         return  $response;
       }




}
