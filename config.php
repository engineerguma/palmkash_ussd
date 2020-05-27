<?php

date_default_timezone_set("Africa/Kampala");

/*
 * System Paths
 */

  if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
  define('URL', 'https://localhost:81/2020/palmcash/ussd');
  }else{
  define('URL', 'http://localhost:81/2020/palmcash/ussd/');	
  }
define('LIBS', 'library/');


?>
