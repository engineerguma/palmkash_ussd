<?php

class Clear_Model {

    function __construct() {
        $this->cldb =  new  ClearData();
    }



function ProcessClearTables(){

//  print_r(TO_CLEAR);die();

$cleared =array();
  $tables =  explode(",", TO_CLEAR['list']);

  //  $cleared = $this->db->TruncateData();
    foreach ($tables as $key => $value){
       $this->cldb->TruncateData($value);
    array_push($cleared,$value);

    }
  print_r($cleared);die();
return  $cleared;
}




}
