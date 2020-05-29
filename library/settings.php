<?php

$conf = parse_ini_file("conf/config.ini",true);

define('DB_TYPE', $conf['datastore']['dtype']);
/* PDN  */
define('DB_HOST'  ,$conf['datastore']['dhost']);
define('DB_USER'  ,$conf['datastore']['duser']);
define('DB_PASS'  ,$conf['datastore']['dpass']);
define('DB_NAME'  ,$conf['datastore']['dname']);

/*
 * Encryption Algo
 */
define('PALMKASH_TRANSPORT'  ,$conf['api_connect']['access_url']);
define('WALLET_TOKEN'  ,$conf['api_connect']['token']);
