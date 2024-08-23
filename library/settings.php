<?php

$conf = parse_ini_file("conf/config.ini",true);

define('DOMAIN', $conf['urconnect']['ussd_domain']);
define('DB_TYPE', $conf['datastore']['dtype']);
/* PDN  */
define('DB_HOST'  ,$conf['datastore']['dhost']);
define('DB_USER'  ,$conf['datastore']['duser']);
define('DB_PASS'  ,$conf['datastore']['dpass']);
define('DB_NAME'  ,$conf['datastore']['dname']);

define('ALLOWED_CHARS'  ,$conf['datastore']['allowed_input']);

define('NAMES_MAXSIZE'  ,$conf['datastore']['names_maxlength']);
define('NAMES_MINSIZE'  ,$conf['datastore']['names_minlength']);

##REDIS
define('CL_USER'  ,$conf['datastore']['cld_user']);
define('CL_PASS'  ,$conf['datastore']['cld_pass']);

define('REDIS_HOST'  ,$conf['PK_REDIS']['host']);
define('REDIS_PORT'  ,$conf['PK_REDIS']['port']);
define('REDIS_PASSWORD'  ,$conf['PK_REDIS']['password']);
define('SESSION_ID_EXP'  ,$conf['PK_REDIS']['session_id_expiry']);


define('ENVIRONMENT'  ,$conf['datastore']['env']);

define('TO_CLEAR'  ,$conf['CLEAR_TABLES']);

define('SCHOOL_CHARGE'  ,$conf['CHARGES']['school_fee_charge']);
/*
 * Encryption Algo
 */
define('PALMKASH_TRANSPORT'  ,$conf['api_connect']['access_url']);
define('WALLET_TOKEN'  ,$conf['api_connect']['token']);

define('PAYMENT_SUBMITTED_MSG'  ,$conf['PAYMENT_MESSAGES']);
