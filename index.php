<?php

ini_set('display_errors',0);
require 'library/settings.php';
require 'config.php';


function  _autoloader($class) {
    require LIBS . $class . ".php";

  }

  spl_autoload_register('_autoloader');

$app = new Bootstrap();


?>
