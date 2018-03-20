<?php

/*
 * Telegram Bot Package
 * Version : 1.0.0
 * Author : Amir Kabiri
 * Github : https://github.com/amirkabiri
 * Phone : +98 914 687 8528
 */

define('APP_PATH', __DIR__.'/..');

ini_set("log_errors", 1);
ini_set("error_log", APP_PATH . "/error_log");
error_reporting(E_ALL);
date_default_timezone_set("Asia/Tehran");
set_time_limit(60);

spl_autoload_register(function($classname){
    require_once str_replace("\\","/", APP_PATH.'/'.$classname.'.php');
});