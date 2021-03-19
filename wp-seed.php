<?php
/*
Plugin Name: Aleapp Core
Description: Utilities. Required for various plugins developped by Aleapp.
Text Domain: ac
Domain Path: langs
Version:     1.3.0
Author:      Aleapp
*/

define('AC_VER', '1.3.0');
define('AC_ROOT', dirname(__FILE__));
define('AC_ROOT_URI', plugins_url('', __FILE__));

$_ac_req=null;
$_ac_configs = array();
$_ac_config = array();
$_ac_namespaces = array();

add_action('plugins_loaded', function(){
    
    require AC_ROOT . '/src/setup-init.php';
    
    do_action('ac_init');
    
}, 100);
