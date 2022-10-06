<?php

ini_set('display_errors',1);
require 'config.php';
require 'library/settings.php';


function  _autoloader($class) {
    require LIBS . $class . ".php";

  }

  spl_autoload_register('_autoloader');

$app = new Bootstrap();


?>
