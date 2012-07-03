<?php
/**
 * Arkain Tour
 * (c) 2012 NAS Technology (http://nasbg.com)
 */

// MAGIC constant
define('PROGRAM', 1);
define('SITE', 'client');

// Required classes and libraries
chdir(dirname(__FILE__));
require_once('./location.php');
require_once(BASE_PATH.'common.php'); // bootstrap

$page = GetPageTree($uri);

// load menu, suggestions, sidebar
LoadPagesData();

// handle
HandlePages(array(
	__('търсене') => 'search.php',
	__('карта-на-сайта') => 'sitemap.php',
	'ajax' => 'ajax.php'
));



// generate new token
$token = GenToken();
$_SESSION['token'] = $token;
$skin->assign('TOKEN', $token);

// save user session
$_SESSION['user'] = $user;

// referrer (back button)
if (isset($_SERVER["HTTP_REFERER"])) {
	if (stripos($_SERVER["HTTP_REFERER"], BASE_URL) === 0) {
		$skin->assign('BACK', $_SERVER['HTTP_REFERER']);
	}
}

// output
$skin->display('index.html');
