<?php

class Index_Model extends COREUSSD {

    function __construct() {
        parent::__construct();
    }

 
    function ParseRequest($xml_post) {
        $standard_array = $this->stan->ParseXMLRequest($xml_post);
        return $standard_array;
    }

 
 
function determineNetwork($xml_post,$standard_array){

 //  print_r(substr($contact,0,-7));die();
    $contact=$standard_array['msisdn'];
   if(substr($contact,0,-7)=='78'||substr($contact,0,-7)=='25078'||substr($contact,0,-7)=='078'){

 $networkrequest=$this->SendByCURL(URL.'mtnrw',$xml_post);
  
   }else  if(substr($contact,0,-7)=='73'||substr($contact,0,-7)=='25073'||substr($contact,0,-7)=='073'){
	   
 $networkrequest=$this->SendByCURL(URL.'airtelrw',$xml_post);
 	   
   }else if(substr($contact,0,-7)=='72'||substr($contact,0,-7)=='25072'||substr($contact,0,-7)=='072'){
 
$networkrequest=$this->SendByCURL(URL.'tigorw',$xml_post);
   }		

return  $networkrequest;
}
    
    
    
 
    function SendByCURL($url, $xml) {
   //     $this->log->ExeLog('Routing USSD Request '. $xml.' To End Point '.$url.'...', 2);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/xml'));
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $content = curl_exec($ch);
   //     $this->log->ExeLog('USSD Request Response from Partner Application '. $content, 2);
        return $content;
    }
   
   
}
