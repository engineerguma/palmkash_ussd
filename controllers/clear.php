<?php

class Clear extends Controller {

    function __construct() {
        parent::__construct();
    }


    function Index() {


          $return = $this->model->ProcessClearTables();
        //  print_r($return);die();          
          exit();

        }


}
?>
