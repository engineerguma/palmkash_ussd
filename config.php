<?php

date_default_timezone_set("Africa/Kigali");

/*
 * System Paths
 */

  if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
  define('URL', 'https://localhost:81/2021/Palmkash/palmkash_ussd_main');
  }else{
  define('URL', 'http://localhost:81/2021/Palmkash/palmkash_ussd_main/');
  }
define('LIBS', 'library/');


?>
