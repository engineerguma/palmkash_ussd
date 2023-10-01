<?php

date_default_timezone_set("Africa/Kigali");

/*
 * System Paths
 */
  if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
  define('URL', 'https://'.DOMAIN);
  }else{
  define('URL', 'http://'.DOMAIN);
  }

define('LIBS', 'library/');


?>
