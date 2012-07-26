<?php
if (!defined('PROGRAM') || PROGRAM !== 1) exit;

function HumanSize($bytes, $precision = 0, $si = false)
{
	$units = array( 'B', 'kB', 'MB', 'GB', 'TB', 'PB' );
	$amount = 1024.0;

	if ($si === true) {
		$units = array( 'B', 'kiB', 'MiB', 'GiB', 'TiB', 'PiB' );
		$amount = 1000.0;
	}

	for ($unit = 0; $bytes > $amount; $unit++) {
		$bytes = round($bytes / $amount, $precision);
	}

	return $bytes . ' ' . $units[$unit];
}

/**
 * Split filename to base name and file extension
 *
 * @param string $file Filename
 * @param bool $lowerExt True to convert extension in lower case, default
 */
function SplitFileExt($file, $lowerExt = true)
{
	$file = explode('.', $file);
	$ext = mb_strtolower(end($file));
	unset($file[count($file) - 1]);
	$file = implode('.', $file);

	if ($lowerExt === true) {
		$ext = mb_strtolower($ext);
	}

	return array($file, $ext);
}


/**
 * Get Valid not exists filename at specific path
 *
 * @param stirng $path Path to checking
 * @param string $filename Filename suggestion with extension
 * @param string $suffix Suffix name of filename, before file extension (up to 32 chars)
 * @param integer $limit Limit filename length (<= 255, default 180)
 * @return array
 */
function GetValidFilename($path, $filename, $suffix = '', $limit = 180)
{
	// cut suffix
	$suffix = mb_substr($suffix, 0, 32);

	$filename = preg_replace("/[^a-z0-9_\-\.]+/i", "", $filename);

	$fileParts = SplitFileExt($filename, true);

	if (mb_strlen($fileParts[0]) == 0) {
		$fileParts[0] = substr(md5(time() + mt_rand()), 0, 8);
	}

	$filename = $fileParts[0] . $suffix . '.' . $fileParts[1];

	clearstatcache();
	if (!file_exists($path . $filename) && mb_strlen($filename) <= $limit) {
		return array(
			'filename' => $filename,
			'name' => $fileParts[0],
			'ext' => $fileParts[1]
		);
	}

	// cut filename to $limit chars (dot(1) + ext(~3) + md5sum(8) + suffix)
	$length = $limit - (1 + mb_strlen($fileParts[1]) + 9 + mb_strlen($suffix));
	$fileParts[0] = mb_substr($fileParts[0], 0, $length);

	do {
		$random = '_' . substr(md5(time() + mt_rand()), 0, 8);
		$name = $fileParts[0] . $random . $suffix;
		$filename = $name . '.' . $fileParts[1];
		clearstatcache();
	} while (file_exists($path . $filename));

	return array(
		'filename' => $filename,
		'name' => $name,
		'ext' => $fileParts[1]
	);
}


/**
 * Fix path name
 *  - Replace C:\\path\\to\\files with C:/path/to/files
 *  - Replace any double slashes, i.e. /home/user/www//gallery//new with /home/user/www/gallery/new
 *  - Add extra / to ending
 *
 * @param string $path Pathname
 * @return string
 */
function FixPathName($path)
{
	$path = str_replace(array('//', '\\'), '/', $path);
	$path = preg_replace('/\/+/', '/', $path);
	$path = rtrim($path, "/") . "/";
	return $path;
}

/**
 * Create directory path (ie all subdirs) if not exist on disk
 *
 * @param string $path
 * @param bool True if exists or created successfully, false otherwise
 */
function CreateDirPath($path)
{
	$dirsOk = true;
	clearstatcache();
	if (!file_exists($path)) {
		$dirsOk = @mkdir($path, 0755, true);
		$dirsOk = @chmod($path, 0755);
	}
	return $dirsOk;
}

/**
 * Get Catalog directories and url addresses
 *
 * @return array
 */
function GetOffersDirs()
{
	$location = FixPathName(CFG('upload.dir.offers'));
	$basePath = FixPathName(BASE_PATH . $location);
	$baseURL = FixPathName($location);

	$names = explode(',', CFG('upload.img.names'));
	$dirs = explode(',', CFG('upload.img.dir'));
	$sizes = explode(',', CFG('upload.img.size'));
	$qualities = explode(',', CFG('upload.img.quality'));

	$result = array();
	foreach ($names as $i => $name) {
		// default 100px size
		$size = isset($sizes[$i]) ? $sizes[$i] : 100;

		// default 100% quality (JPEG) or 9 level compression (PNG)
		$quality = isset($qualities[$i]) ? $qualities[$i] : 100;

		// default to base path
		$dir = isset($dirs[$i]) ? $dirs[$i] : '';

		$path = FixPathName($basePath . $dir);
		$url = BASE_URL . FixPathName($baseURL . $dir);
		$location = FixPathName($location . $dir);

		// create directory if not exists
		CreateDirPath($path);

		$result[$name] = array(
			'name' => $name,
			'location' => $location,
			'path' => $path,
			'url' => $url,
			'size' => $size,
			'quality' => $quality
		);
	}
	return $result;
}

function MsgPush($type = 'info', $message = '')
{
	$type = mb_strtolower($type);

	if (!in_array($type, array('error', 'success', 'info', 'log'))) $type = 'info';

	if ($type === 'log') $message = print_r($message, true);

	$_SESSION['sys.messages'][] = array(
		'type' => $type,
		'message' => $message
	);
}

function MsgPop()
{
	global $skin;

	if (!isset($_SESSION['sys.messages']) || count($_SESSION['sys.messages']) == 0) return;

	foreach ($_SESSION['sys.messages'] as $item => $data) {
		$skin->assign(mb_strtoupper($data['type']), $data['message']);
		unset($_SESSION['sys.messages'][$item]);
	}
}

function RedirectSite($address)
{
	header('Location: '.BASE_URL.$address);
	exit;
}

function DeleteEmptyTree($path, $depth = 1)
{
	$tree = explode('/', trim($path, '/'));

	for ($i = count($tree) - 1; $i > 0 && $depth >= 0; $i--, $depth--) {
		$node = $tree[$i];
		unset($tree[$i]);

		$dir = implode('/', $tree).'/'.$node.'/';
		$files = @scandir($dir);

		// directory is not empty or error occurrence
		if (count($files) > 2 || !@rmdir($dir)) break;
	}
}

function GetPageTree(&$tree = array())
{
	global $skin;
	$page = isset($_GET['page']) ? $_GET['page'] : '';
	$uri = explode('/', trim($page, '/'));
	$tree = array_slice($uri, 1);
	return mb_strtolower($uri[0]);
}

function HandlePages($customPages)
{
	global $db, $skin, $page, $ml;

	$lang = CFG('locale');

	$title = __('Аркаин Тур');

	if (isset($customPages[$page])) {
		require_once($customPages[$page]);
		main($title);
	}
	else {
		if (mb_strlen($page) == 0) {
			// get default page or first found result
			$sql = "SELECT
					a.`id`,
					s.`name` AS 'script'
				FROM
					`".TABLE_ARTICLES."` a,
					`".TABLE_SCRIPTS."` s,
					`".TABLE_LANGUAGES."` l
				WHERE
					s.`id` = a.`script_id`
					AND l.`id` = a.`lang_id`
					AND l.`locale` = '".$db->escapeString($lang)."'
				ORDER BY a.`default` DESC, a.`order` ASC
				LIMIT 1
			";
		}
		else {
			// search for page by using SEO URL
			$sql = "SELECT
					a.`id`,
					s.`name` AS 'script'
				FROM
					`".TABLE_ARTICLES."` a,
					`".TABLE_SCRIPTS."` s,
					`".TABLE_LANGUAGES."` l
				WHERE
					s.`id` = a.`script_id`
					AND a.`url` = '".$db->escapeString($page)."'
					AND l.`id` = a.`lang_id`
					AND l.`locale` = '".$db->escapeString($lang)."'
				LIMIT 1
			";
		}

		if (!$db->query($sql)) {
			$skin->assign('ERROR', $ml['L_ERROR_DB_QUERY']);
			return;
		}

		if (!$db->getCount()) {
			$skin->assign('TITLE', trim($title . ' – ' . __('Страницата не е намерена!')));
			$skin->assign('PAGE', 'notfound');
			return;
		}

		$article = $db->getAssoc();
		require_once($article['script']);
		main($article['id'], $title);
	}

	// show title
	$skin->assign('TITLE', trim($title));

	// show previous generated messages (errors)
	MsgPop();
}

function ErrorLog($msg, $logFilename, $path = null)
{
	if ($path === null) $path = BASE_PATH . 'cache/';

	$error = date('Y-m-d H:i:s: ') . strip_tags(trim($msg)) . "\n";
	error_log($error, 3, $path . $logFilename);
}

/**
 * Get Current Language (POSIX Locale pairs) from:
 *   0. Get default code from db (or constant)
 *   1. HTTP Accept Language
 *   2. User Cookie
 *   3. Default Language from DB
 *   4. POST/GET Variable
 */
function InitLanguages()
{
	global $db, $skin;

	$current = array();
	$currentIndex = 0;

	$langList = array();
	$sql = "SELECT
			`id`,
			`lang_code`,
			`country_code`,
			`native` as 'name',
			`locale` as 'code'
		FROM `".TABLE_LANGUAGES."`
		WHERE `active` = '1'
		ORDER BY `default` DESC";
	if (!$db->query($sql) || !$db->getCount()) {
		return $langList;
	}

	$langList = $db->getAssocArray();

	// 1. set default
	$current = $langList[$currentIndex];

	// 2. check for http access language
	$acceptLangs = HttpAcceptLanguage();
	if (count($acceptLangs)) {
		$done = false;
		foreach ($acceptLangs as $code => $priority) {

			if ($done) break;

			foreach ($langList as $i => $value) {
				if (mb_strlen($code) == 2 && $code === $value['lang_code']) {
					$current = $langList[$i];
					$currentIndex = $i;
					$done = true;
					break;
				} else if (mb_strlen($code) == 5 && $code === $value['code']) {
					$current = $langList[$i];
					$currentIndex = $i;
					$done = true;
					break;
				}
			}
		}
	}

	// 3. User cookie
	if (isset($_COOKIE['locale'])) {
		$code = $_COOKIE['locale'];

		foreach ($langList as $i => $value) {
			if (mb_strlen($code) == 5 && $code === $value['code']) {
				$current = $langList[$i];
				$currentIndex = $i;
				break;
			}
		}
	}

	// 4. POST/GET variable
	$request = array_merge($_GET, $_POST);
	if (isset($request['locale'])) {
		$code = $request['locale'];

		foreach ($langList as $i => $value) {
			if (mb_strlen($code) == 5 && $code === $value['code']) {
				$current = $langList[$i];
				$currentIndex = $i;
				break;
			}
		}
	}

	// set cookie to save user choice
	$expire = time() + 3600 * 24 * 30; // 1 month
	setcookie('locale', $current['code'], $expire, COOKIE_PATH);

	CFG('locale', $current['code']);
	CFG('language', $current['lang_code']);
	CFG('country', $current['country_code']);

	// Init Gettext library
	InitLocale(CFG('locale'), CFG('locales.domain'), BASE_PATH . CFG('locales.dir'));

	$langList[$currentIndex]['selected'] = true;
	return $langList;
}

function HttpAcceptLanguage()
{
	$langs = array();
	$regex = '/([a-z]{1,8}(-[a-z]{1,8})?)\s*(;\s*q\s*=\s*(1|0\.[0-9]+))?/i';

	$acceptLang = isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])
		? $_SERVER['HTTP_ACCEPT_LANGUAGE']
		: '';

	preg_match_all($regex, $acceptLang, $found);

	if (!count($found[1])) {
		return $langs;
	}

	foreach ($found[1] as $lang => $code) {
		$code = explode('-', $code);

		$code[0] = mb_strtolower($code[0]);
		$code[1] = isset($code[1]) ? mb_strtoupper($code[1]) : '';

		$code = array_filter($code);

		if (count($code)) {
			$code = implode('_', $code);
		}

		$found[1][$lang] = $code;
	}

	$langs = array_combine($found[1], $found[4]);

	foreach ($langs as $lang => $val) {
		if ($val === '') $langs[$lang] = 1;
	}

	arsort($langs, SORT_NUMERIC);

	return $langs;
}

/**
 * Initialize location and select default domain (.mo file)
 *
 * @param string $locale Language/Localization pair (i.e. en_US, fr_CA)
 * @param string $domain Name of default .mo file
 * @param string $path Where to search translation files
 */
function InitLocale($locale, $domain, $path)
{
	setlocale(LC_ALL, $locale . '.UTF-8');
	setlocale(LC_TIME, $locale . '.UTF-8');
	putenv('LANG=' . $locale . '.UTF-8');
	bindtextdomain($domain, $path);
	bind_textdomain_codeset($domain, 'UTF-8');
	textdomain($domain);
}

// Implement gettext context
if (!function_exists('pgettext')) {
	/**
	 * [Gettext] gettext() implementation w/ context support
	 *
	 * @param string $context Context of message
	 * @param string $msgid Message to translate
	 * @return string translated message
	 */
	function pgettext($context, $msgid) {
		$contextString = $context . "\004" . $msgid;
		$translation = gettext($contextString);
		if ($translation === $contextString) return $msgid;
		return $translation;
	}
}

if (!function_exists('npgettext')) {
	/**
	 * [Gettext] ngettext() implementation w/ context support
	 *
	 * @param string $context Context of message
	 * @param string $string1 Normal message
	 * @param string $string2 Plural form message
	 * @param integer $num Checking for plural form
	 * @return string translated message
	 */
	function npgettext($context, $msgid1, $msgid2, $num) {
		$contextString = $context . "\004" . $msgid1;
		$contextStringp = $context . "\004" . $msgid2;
		$translation = ngettext($contextString, $contextStringp, $num);
		if ($translation === $contextString || $translation === $contextStringp) return $msgid1;
		return $translation;
	}
}

/**
 * [Gettext] gettext() alternative w/ optional context
 *
 * @param string $string Message to translate
 * @param string $context Context of message
 * @return string translated message
 */
function __($string, $context = '')
{
	return (mb_strlen($context)
		? pgettext($context, $string)
		: gettext($string)
	);
}

/**
 * [Gettext] ngettext() alternative w/ optional context
 *
 * @param string $string1 Normal message
 * @param string $string2 Plural form message
 * @param integer $num Checking for plural form
 * @param string $context Context of message
 * @return string translated message
 */
function _n($string1, $string2, $num, $context = '')
{
	return (mb_strlen($context)
		? npgettext($context, $string1, $string2, $num)
		: ngettext($string1, $string2, $num)
	);
}

/**
 * Gettext translation Callback for Smarty {tr}text{/tr} plugin
 *
 * @param array $params Parameters within {tr} Smarty template plugin
 * @param string $content Text between {tr} and {/tr}
 * @param object $template Smarty Template
 * @param integer $repeat
 * @return string translated message
 */
function TranslateSmarty($params, $content, $template, &$repeat)
{
	if (isset($content)) {
		$context = isset($params['context']) ? $params['context'] : '';
		return __($content, $context);
	}
}

function LoadMainMenu()
{
	global $db;

	// get menu items
	$sql = "SELECT
			a.`title`,
			a.`url`
		FROM
			`".TABLE_ARTICLES."` a,
			`".TABLE_LANGUAGES."` l
		WHERE
			l.`id` = a.`lang_id`
			AND l.`locale` = '".$db->escapeString(CFG('locale'))."'
			AND a.`url` <> ''
		ORDER BY a.`order` ASC
	";
	if (!$db->query($sql) || !$db->getCount()) {
		return array();
	}

	return $db->getAssocArray();
}

function LoadSideBarItems()
{
	global $db;

	$sidebar = array();

	$baseURL = BASE_URL . CFG('upload.dir.icons') . DS;

	// get all subcategories
	$sql = "SELECT
			a.`url`,
			c.`id`,
			c.`title`,
			i.`filename`
		FROM (
			`".TABLE_ARTICLES."` a,
			`".TABLE_CATEGORIES."` c,
			`".TABLE_LANGUAGES."` l
		)

		LEFT JOIN `".TABLE_ICONS."` i ON
			i.`id` = c.`icon_id`

		WHERE
			a.`id` = c.`article_id`
			AND c.`lang_id` = l.`id`
			AND l.`locale` = '".$db->escapeString(CFG('locale'))."'
			AND a.`url` <> ''
		ORDER BY c.`order` ASC, a.`order` ASC";
	if ($db->query($sql) && $db->getCount()) {
		while ($row = $db->getAssoc()) {
			$row['url'] = BASE_URL . CFG('locale') . '/' . $row['url'] . '/' . $row['id'];

			$row['image'] = false;
			if ($row['filename']) {
				$row['image'] = $baseURL . $row['filename'];
			}

			$sidebar[] = array(
				'url' => $row['url'],
				'name' => $row['title'],
				'icon' => $row['image']
			);
		}
	}

	return $sidebar;
}

function LoadSuggestionsOffers()
{
	global $db;

	$result = array();

	// get random offers (suggestions)
	// get offers pages (limit to 2)
	$sql = "SELECT
			a.`id`,
			a.`url`,
			a.`title`
		FROM
			`".TABLE_ARTICLES."` a,
			`".TABLE_SCRIPTS."` s,
			`".TABLE_LANGUAGES."` l
		WHERE
			a.`url` <> ''
			AND a.`lang_id` = l.`id`
			AND l.`locale` = '".$db->escapeString(CFG('locale'))."'
			AND s.`id` = a.`script_id`
			AND s.`has_offers` = '1'
		ORDER BY a.`order` ASC";
	if (!$db->query($sql) || !$db->getCount()) {
		return $result;
	}

	$ids = array();
	$articles = array();

	while ($row = $db->getAssoc()) {
		$articles[$row['id']] = $row;
		$ids[] = $row['id'];
	}

	$ids = "'" . implode("', '", $ids) . "'";

	// get offers count by article id
	$sql = "SELECT
			c.`article_id` AS 'id',
			COUNT(*) AS 'count'
		FROM
			`".TABLE_OFFERS."` o,
			`".TABLE_CATEGORIES."` c
		WHERE
			c.`id` = o.`category_id`
			AND c.`article_id` IN (".$ids.")
		GROUP BY c.`article_id`
	";
	if (!$db->query($sql) || !$db->getCount()) {
		return $result;
	}

	$sql = array();

	while ($row = $db->getAssoc()) {
		if (!$row['count']) continue;

		$max = $row['count'] - 2;
		$offset = mt_rand(0, (($max > 0) ? $max : 0) );

		$sql[] = "(
			SELECT
				o.`id`,
				o.`name`,
				SUBSTR(o.`content`, 1, 255) AS 'content',
				o.`price`,
				c.`id` AS 'cat_id',
				c.`article_id` AS 'a_id',
				p.`filename`
			FROM (
				`".TABLE_OFFERS."` o,
				`".TABLE_CATEGORIES."` c
			)
			LEFT JOIN `".TABLE_OFFERS_PICTURES."` p ON
				p.`offer_id` = o.`id`
			WHERE
				c.`article_id` = '".$row['id']."'
				AND o.`category_id` = c.`id`
			GROUP BY o.`id`
			LIMIT ".$offset.", 2
		)";
	}

	if (!count($sql)) {
		return $result;
	}

	$sql = implode(" UNION ", $sql);
	if (!$db->query($sql) || !$db->getCount()) {
		return $result;
	}

	$resizes = GetOffersDirs();

	while ($row = $db->getAssoc()) {
		if ($row['filename']) {
			$row['image'] = $resizes['small']['url'] . $row['filename'];
		}

		$url = $articles[$row['a_id']]['url'];
		$row['url'] = BASE_URL . CFG('locale') . '/' . $url . '/' . $row['cat_id'] . '/' . $row['id'];
		$row['content'] = ShortText($row['content'], CFG('short.text'), true, true);

		$articles[$row['a_id']]['offers'][] = $row;
	}

	$articles = array_values($articles);
	$articles['left'] = $articles[0];
	$articles['right'] = $articles[1];
	unset($articles[0], $articles[1]);

	return $articles;
}

function LoadPagesData()
{
	global $skin;

	$skin->assign('MENU', LoadMainMenu());
	$skin->assign('SIDEBAR', LoadSideBarItems());
	$skin->assign('SUGGESTIONS', LoadSuggestionsOffers());

}



function GetMaxUploadLimits()
{
	$filesize = array('size' => PHP_INT_MAX, 'qty' => 'G');

	$maxsize = array(
		@ini_get('upload_max_filesize'),
		@ini_get('post_max_size'),
		@ini_get('memory_limit')
	);

	foreach ($maxsize as $index => $val) {
		$qty = preg_replace('/[^a-z]+/i', '', $val);

		if ((int) $val < $filesize['size'] || $qty !== $filesize['qty']) {
			$filesize['size'] = (int) $val;
			$filesize['qty'] = $qty;
		}
	}

	switch (mb_strtolower($filesize['qty'])) {
	case 'm': $filesize['qty'] = 'MB'; break;
	case 'g': $filesize['qty'] = 'GB'; break;
	case 'k': $filesize['qty'] = 'kB'; break;
	default: $filesize['qty'] = 'B'; break;
	}

	return $filesize['size'] . ' ' . $filesize['qty'];
}
