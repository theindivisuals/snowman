<?php
/**
 * snowman-php-server - PHP script to run a snowman server.
 * http://code.google.com/p/snowman/
 *
 * Copyright (C) 2013 Bernard Ladenthin <bernard@ladenthin.net>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Main file for all PHP files
 */

//global variables
$camerauploadNameParameter = "cameraname";
$camerauploadFileParameter = "cameraimage";
$sessionUsernameParameter = "username";
$sessionPasswordParameter = "password";
$sessionIsLoginOkParameter = "isLoginOK";
$requestUsernameParameter = "username";
$requestPasswordParameter = "password";
$requestPhpsessidParameter = "PHPSESSID";
$requestJsonpParameter = "jsonp";
$requestCallbackParameter = "callback";

function alClass($class) {
	include 'class/' . $class . '.php';
}

spl_autoload_register('alClass');

require_once('functions.php');

error_reporting(E_ALL);

if(PHP_INT_SIZE < 8) {
	echo "require PHP_INT_SIZE > 8 (as example 64 bit, no support for 32 bit)";
	exit;
}

if(!checkPHPGDExtension()) {
	echo "require the php5-gd extension. "
	."As example "
	."http://packages.debian.org/de/jessie/php5-gd "
	."or "
	."http://packages.ubuntu.com/en/raring/php5-gd";
	exit;
}

//TODO: BLCRITICAL: put this in the config with a overwrite flag
setlocale(LC_TIME, 'German_Germany');
date_default_timezone_set('Europe/Berlin');
if(!ob_start("ob_gzhandler")) ob_start();

$configFilename = 'config.js.php';
if(!file_exists ($configFilename)) {
	echo "config file not found: ".$configFilename;
	exit;
}

$contents = file($configFilename, FILE_IGNORE_NEW_LINES);
//remove first line
array_shift($contents);
$config = json_decode(implode("\r\n", $contents));

//we only need one snowman instance
$snowman = new Snowman($config->snowman);
$snowman->loadUsers($config->users);
$snowman->loadCameras($config->cameras);

if(isset($_REQUEST[$requestPhpsessidParameter])) {
	session_id($_REQUEST[$requestPhpsessidParameter]);
}
$snowman->session_start();

$isloginok = false;

if(
		isset($_REQUEST[$requestUsernameParameter])
	&&	isset($_REQUEST[$requestPasswordParameter])
) {
	$snowman->isLoginOK(
		$_REQUEST[$requestUsernameParameter],
		$_REQUEST[$requestPasswordParameter]
	);
} else {
	$snowman->isLoginOK();
}

if(isset($_SESSION[$sessionIsLoginOkParameter])) {
	if($_SESSION[$sessionIsLoginOkParameter]) {
		$isloginok = true;
	}
}

//$JSONResquest = JSON_decode(file_get_contents("php://input"));
$jsonp = false;
$jsonpCallback = "";
if(isset($_REQUEST[$requestJsonpParameter])) {
	$jsonp = $_REQUEST[$requestJsonpParameter];
	//compatibility for php < 5.4:
	if(get_magic_quotes_gpc()) {
		$jsonp = stripslashes($jsonp);
	}
	if(isset($_REQUEST[$requestCallbackParameter])) {
		$jsonpCallback = $_REQUEST[$requestCallbackParameter];
	}
}

