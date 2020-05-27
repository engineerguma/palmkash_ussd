<?php

ini_set('display_errors', 1);
require 'config.php';
require 'library/settings.php';

function __autoload($class) {
    require LIBS . $class . ".php";
}

$app = new Bootstrap();


?>
