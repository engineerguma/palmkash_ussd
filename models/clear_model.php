<?php

class Clear_Model {

    function __construct() {
        $this->cldb =  new  ClearData();
    }



function ProcessClearTables(){

$cleared =array();
  $tables =  explode(",", TO_CLEAR['list']);

    foreach ($tables as $key => $value){
       $this->cldb->TruncateData($value);
    array_push($cleared,$value);

    }

return  $cleared;
}




}
