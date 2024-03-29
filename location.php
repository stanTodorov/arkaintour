<?php
/**
 * Get Website root location (URL and absolute paths) on server
 *
 * <pre>
 * STEPS:
 *  Place this file to ROOT directory of the web site;
 *  Include in .php files that needs path/url of site;
 *  Use defined constants;
 *
 * DEFINED CONSTANTS:
 *  BASE_URL - current URL of page (with relative path); slash ended
 *  BASE_URL_HTTP - current URL of page with http protocol; slash ended
 *  BASE_URL_HTTPS - current URL of page with https protocol; slash ended
 *  path - absolute path of this file, slash ended
 *  IS_HTTPS - true if https, otherwise - false (http)
 *  COOKIE_DOMAIN - domain (localhost => null)
 *  COOKIE_PATH - subpath for cookies
 *
 * KNOW PROBLEMS:
 *  Non-latin paths on Windows host (apache) not working;
 *
 * CHANGES:
 *  2011-08-26: Remove port for cookie domain
 *  2011-05-05: Fix subdirs
 *  2011-04-29: Fix /home/~user/ and /var/www/other/site/path bugs
 * </pre>
 *
 * @author Mikhail Kyosev (mikhail.kyosev@gmail.com)
 * @version 1.4
 * @license MIT (http://www.opensource.org/licenses/mit-license.php)
 */

/*
**
*/
$path = str_replace(array("\\", "//"), '/',  dirname(__FILE__)) . '/';

// In cronjob/cli mode, there is no web server
if (!isset($_SERVER['HTTP_HOST']) || !isset($_SERVER['DOCUMENT_ROOT'])) {

	// cli/cgi-mode?
	define('BASE_PATH', $path);

	// set default values (avoid warnings)
	define('BASE_URL', 'http://localhost/');
	define('BASE_URL_HTTPS', 'http://localhost/');
	define('BASE_URL_HTTP', 'http://localhost/');
	define('IS_HTTPS', false);
	define('COOKIE_DOMAIN', '');
	define('COOKIE_PATH', '/');

	chdir($path);
}
else {

	// https or simple http
	$isSecure = false;
	if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') $isSecure = true;

	$protocol = $isSecure == true ? 'https://' : 'http://';
	$hostname = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';

	// current url
	$url = dirname($_SERVER['SCRIPT_NAME']);

	// if server htdocs path != url path (mod_userdir or different subdirectory)
	if (stristr($url, $_SERVER['DOCUMENT_ROOT']) === false
	    || stristr($path, $_SERVER['DOCUMENT_ROOT']) === false)
	{
		$base_url = '';
		$paths = array_values(array_filter(explode('/', $path)));

		// get last subdir
		$last_path = $paths[count($paths)-1];

		// get pach to this script
		$url = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
		$url = array_values(array_filter(explode('/', $url)));

		$is_sub_dir = false;
		if (in_array($last_path, $url)) {
			foreach($url as $key => $val) {
				$base_url .= '/'.$val;

				if ($last_path == $val) {
					$is_sub_dir = true;
					$base_url .= '/'; // end slash
					break;
				}
			}
		}
		else {
		//	$url = array();
		}

		// maybe this file location is /~USER/ ?
		if (!$is_sub_dir && count($url) > 0) {
			$base_url = '/'.$url[0].'/';
		}
		else if (count($url) == 0) {
			$base_url = '/';
		}

		$base_url = str_replace(array('//', '\\'), '/', $base_url);

		unset($last_path, $paths, $is_sub_dir);
	}
	else {
		// in case that path is \ or /
		if (mb_strlen(rtrim($_SERVER['DOCUMENT_ROOT'], '/')) > 1) {
			// absolute path => relative web path
			$doc_root = rtrim($_SERVER['DOCUMENT_ROOT'], '/');
			$base_url = str_replace($doc_root, '', $path);
			unset($doc_root);
		}
		else {
			$base_url = $path;
		}
	}

	$domain = (mb_strtolower($hostname) === 'localhost') ? "" : $hostname;

	// remove port number from domain
	$cookie_domain = explode(":", $domain);
	$cookie_domain = $cookie_domain[0];

	define('IS_HTTPS', $isSecure);
	define('BASE_PATH', $path);
	define('BASE_URL', $protocol.$hostname.$base_url);
	define('BASE_URL_HTTPS', 'https://'.$hostname.$base_url);
	define('BASE_URL_HTTP', 'http://'.$hostname.$base_url);
	define('COOKIE_DOMAIN', $cookie_domain);
	define('COOKIE_PATH', $base_url);

	unset($document_root, $protocol, $hostname, $base_url, $isSecure);
	unset($path, $domain, $url, $cookie_domain);
}



//debug:
//	echo '<hr /><pre>Path: '.BASE_PATH,'<br />URL:  '.BASE_URL.'</pre><hr />';
