<?php
if (!defined('PROGRAM') || PROGRAM !== 1) exit;

function Main()
{
	global $skin, $title;
	$skin->assign('PAGE', 'articles');
	$title .= ' – Страници';

	$action = isset($_GET['action']) ? $_GET['action'] : '';
	switch($action) {
	case 'add':
		AddArticle();
		break;
	case 'edit':
		EditArticle();
		break;
	case 'delete':
		DeleteArticle();
		break;
	case 'sort':
		SortArticles();
		break;
	case 'list':
	default:
		ListArticles();
	}
}

function AddArticle()
{
	global $db, $skin, $user, $ml, $page;
	$skin->assign('ACTION', 'add');
	$skin->assign('TINY_MCE', true);

	$lang = isset($_SESSION['lang'])
		? (int) $_SESSION['lang']
		: 0;

	// get all languages
	$sql = "SELECT
			`id`,
			`name`,
			`native`
		FROM `".TABLE_LANGUAGES."`
		WHERE `active` = '1'
		ORDER BY `default` DESC";
	if (!$db->query($sql) || !$db->getCount()) {
		MsgPush('error', $ml['L_ERROR_NOT_FOUND']);
		RedirectSite('admin/?page='.$page);
	}

	$langsID = array();
	$langs = array();
	for ($i = 0; $row = $db->getAssoc(); $i++) {
		$langsID[$row['id']] = $i;
		$langs[] = $row;
	}
	$skin->assign('LANGS', $langs);

	if ($lang <= 0 || !isset($langsID[$lang])) {
		$lang = $langs[0]['id'];
	}

	// get page types list
	$sql = "SELECT `id`, `title` AS 'name'
		FROM `".TABLE_SCRIPTS."`
		ORDER BY `title` ASC";
	if (!$db->query($sql) || !$db->getCount()) {
		MsgPush('error', $ml['L_ERROR_NOT_FOUND']);
		RedirectSite('admin/?page='.$page);
	}

	$skin->assign('SCRIPTS', $db->getAssocArray());
	$skin->assign('RESULT', array('lang' => $lang));

	if (!isset($_POST['submit'])) return;

	$errors = FormValidate($_POST, $data, array(
		'script' => array(
			'is' => 'int',
			'req' => true
		),
		'show_title' => array(
			'is' => 'int',
			'between' => array(0, 1),
			'default' => 0
		),
		'title' => array(
			'req' => true,
			'min' => 2,
			'max' => 255
		),
		'content' => array(
			'req' => false,
			'min' => 1,
			'max' => 65535
		),
		'url' => array(
			'req' => false,
			'min' => 1,
			'max' => 256
		),
		'keywords' => array(
			'req' => false,
			'min' => 1,
			'max' => 256
		),
		'lang' => array(
			'req' => true,
			'is' => 'int'
		)
    	));

	if (count($errors) > 0) {
		$skin->assign('ERROR', $ml['L_ERROR_INPUT']);
		$skin->assign('RESULT', $data);
		$skin->assign('ERRORS', $errors);
		return;
	}

	$order = 1;
	$sql = "SELECT `order`
		FROM `".TABLE_ARTICLES."`
		WHERE `lang_id` = '".$data['lang']."'
		ORDER BY `order` DESC
		LIMIT 1";
	if ($db->query($sql) && $db->getCount()) {
		$order = $db->getAssoc();
		$order = $order['order'];
		$order++;
	}

	$url = (mb_strlen($data['url']) < 3)
		? mb_strtolower($data['title'])
		: mb_strtolower($data['url']);
	$url = preg_replace('/[\s]+/iu', '-', $url);
	$url = preg_replace('/[^а-яa-z0-9\-]+/iu', '', $url);

	// Add Article
	$sql = "INSERT INTO `".TABLE_ARTICLES."` (
			`id`, `lang_id`, `title`,
			`content`, `url`, `keywords`,
			`default`, `script_id`, `show_title`,
			`order`, `added`, `added_by`,
			`modified`, `modified_by`
		) VALUES (
			NULL,
			'".$data['lang']."',
			'".$db->escapeString($data['title'])."',
			'".$db->escapeString($data['content'])."',
			'".$db->escapeString($url)."',
			'".$db->escapeString($data['keywords'])."',
			'0',
			'".$data['script']."',
			'".$data['show_title']."',
			'".$order."',
			NOW(),
			'".$user['id']."',
			NOW(),
			'".$user['id']."'
		)";
	if (!$db->query($sql)) {
		$skin->assign('ERROR', $ml['L_ERROR_DB_QUERY']);
		$skin->assign('RESULT', $data);
		return;
	}

	$_SESSION['lang'] = $data['lang'];

	MsgPush('success', 'Успешно редактирана страница!');
	RedirectSite('admin/?page='.$page);
}

function EditArticle()
{
	global $db, $skin, $user, $ml, $page;
	$skin->assign('ACTION', 'edit');
	$skin->assign('TINY_MCE', true);

	$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
	if ($id <= 0) {
		MsgPush('error', $ml['L_ERROR_NOT_FOUND']);
		RedirectSite('admin/?page='.$page);
	}

	// get all languages
	$sql = "SELECT
			`id`,
			`name`,
			`native`
		FROM `".TABLE_LANGUAGES."`
		WHERE `active` = '1'
		ORDER BY `default` DESC";
	if (!$db->query($sql) || !$db->getCount()) {
		MsgPush('error', $ml['L_ERROR_NOT_FOUND']);
		RedirectSite('admin/?page='.$page);
	}

	$langs = $db->getAssocArray();
	$skin->assign('LANGS', $langs);

	// check for article exists
	$sql = "SELECT
			`title`,
			`content`,
			`url`,
			`keywords`,
			`lang_id` AS 'lang',
			`script_id` AS 'script',
			`show_title`,
			`order`
		FROM `".TABLE_ARTICLES."`
		WHERE `id` = '".$id."'";
	if (!$db->query($sql) || !$db->getCount()) {
		MsgPush('error', $ml['L_ERROR_NOT_FOUND']);
		RedirectSite('admin/?page' . $page);
	}

	$result = $db->getAssoc();

	$skin->assign('RESULT', $result);
	$skin->assign('ID', $id); // valid ID

	// get page types list
	$sql = "SELECT `id`, `title` AS 'name'
		FROM `".TABLE_SCRIPTS."`
		ORDER BY `title` ASC";
	if (!$db->query($sql) || !$db->getCount()) {
		MsgPush('error', $ml['L_ERROR_NOT_FOUND']);
		RedirectSite('admin/?page='.$page);
	}

	$skin->assign('SCRIPTS', $db->getAssocArray());

	if (!isset($_POST['submit'])) return;

	$errors = FormValidate($_POST, $data, array(
		'script' => array(
			'is' => 'int',
			'req' => true
		),
		'show_title' => array(
			'is' => 'int',
			'between' => array(0, 1),
			'default' => 0
		),
		'title' => array(
			'req' => true,
			'min' => 2,
			'max' => 255
		),
		'content' => array(
			'req' => false,
			'min' => 1,
			'max' => 65535
		),
		'url' => array(
			'req' => false,
			'min' => 1,
			'max' => 256
		),
		'keywords' => array(
			'req' => false,
			'min' => 1,
			'max' => 256
		),
		'lang' => array(
			'req' => true,
			'is' => 'int'
		)
    	));

	if (count($errors) > 0) {
		$skin->assign('ERROR', $ml['L_ERROR_INPUT']);
		$skin->assign('RESULT', $data);
		$skin->assign('ERRORS', $errors);
		return;
	}

	$url = (mb_strlen($data['url']) < 3)
		? mb_strtolower($data['title'])
		: mb_strtolower($data['url']);
	$url = preg_replace('/[\s]+/iu', '-', $url);
	$url = preg_replace('/[^а-яa-z0-9\-]+/iu', '', $url);

	$isSwapped = false;
	$order = $result['order'];
	if ($result['lang'] !== $data['lang']) {
		$sql = "SELECT `order`
			FROM `".TABLE_ARTICLES."`
			WHERE `lang_id` = '".$data['lang']."'
			ORDER BY `order` DESC
			LIMIT 1";
		if ($db->query($sql) && $db->getCount()) {
			$order = $db->getAssoc();
			$order = $order['order'];
			$order++;
		} else {
			$order = 1;
		}

		$isSwapped = true;
	}

	// Update article
	$sql = "UPDATE `".TABLE_ARTICLES."` SET
			`lang_id` = '".$data['lang']."',
			`title` = '".$db->escapeString($data['title'])."',
			`content` = '".$db->escapeString($data['content'])."',
			`url` = '".$db->escapeString($url)."',
			`keywords` = '".$db->escapeString($data['keywords'])."',
			`script_id` = '".$data['script']."',
			`show_title` = '".$data['show_title']."',
			`order` = '".$order."',
			`modified` = NOW(),
			`modified_by` = '".$user['id']."'
		WHERE `id` = '".$id."'";
	if (!$db->query($sql)) {
		$skin->assign('ERROR', $ml['L_ERROR_DB_QUERY']);
		$skin->assign('RESULT', $data);
		return;
	}

	// update ordering of previous used chain
	if ($isSwapped) {
		$sql = "UPDATE `".TABLE_ARTICLES."`
			SET `order` = `order` - '1'
			WHERE
				`order` > '".$result['order']."'
				AND `lang_id` = '".$result['lang']."'
		";
		if (!$db->query($sql)) {
			MsgPush('error', $ml['L_ERROR_DB_QUERY']);
			RedirectSite('admin/?page='.$page);
		}
	}

	$_SESSION['lang'] = $data['lang'];

	MsgPush('success', 'Успешно редактирана страница!');
	RedirectSite('admin/?page='.$page);
}

function DeleteArticle()
{
	global $db, $skin, $user, $ml, $page;

	$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
	if ($id <= 0) {
		MsgPush('error', $ml['L_ERROR_NOT_FOUND']);
		RedirectSite('admin/?page='.$page);
	}

	// check available
	$sql = "SELECT `order`, `lang_id`
		FROM `".TABLE_ARTICLES."`
		WHERE `id` = '".$id."'";
	if (!$db->query($sql) || !$db->getCount()) {
		MsgPush('error', $ml['L_ERROR_DB_QUERY'] . mysql_error());
		RedirectSite('admin/?page='.$page);
	}

	$order = $db->getAssoc();

	// delete article
	$sql = "DELETE FROM `".TABLE_ARTICLES."`
		WHERE `id` = '".$id."'
		LIMIT 1";
	if (!$db->query($sql)) {
		MsgPush('error', $ml['L_ERROR_DB_QUERY'] . mysql_error());
		RedirectSite('admin/?page='.$page);
	}

	// update ordering of all others
	$sql = "UPDATE `".TABLE_ARTICLES."`
		SET `order` = `order` - '1'
		WHERE `order` > '".$order['order']."'
			AND `lang_id` = '".$order['lang_id']."'
	";
	if (!$db->query($sql)) {
		MsgPush('error', $ml['L_ERROR_DB_QUERY'] . mysql_error());
		RedirectSite('admin/?page='.$page);
	}

	MsgPush('success', 'Успешно изтрита страница!');
	RedirectSite('admin/?page='.$page);
}

function SortArticles()
{
	global $db, $skin, $user, $ml, $page;
	$skin->assign('ACTION', 'sort');

	$lang = isset($_SESSION['lang']) ? (int) $_SESSION['lang'] : 0;

	$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
	$dir = isset($_GET['dir']) ? $_GET['dir'] : '';

	if ($id > 0 && ($dir === 'up' || $dir === 'down')) {
		$sql = "SELECT `order`
			FROM `".TABLE_ARTICLES."`
			WHERE `id` = '".$id."' AND `lang_id` = '".$lang."'";
		if (!$db->query($sql)) {
			$skin->assign('ERROR', $ml['L_ERROR_DB_QUERY']);
			return;
		}

		$order = $db->getAssoc();
		$order = $order['order'];

		// get order of neighboring row
		switch ($dir) {
		case 'up':
			$sql = "SELECT `order`, `id`
				FROM `".TABLE_ARTICLES."`
				WHERE
					`order` < '".$order."'
					AND `lang_id` = '".$lang."'
				ORDER BY `order` DESC
				LIMIT 1";
			break;
		case 'down':
			$sql = "SELECT `order`, `id`
				FROM `".TABLE_ARTICLES."`
				WHERE
					`order` > '".$order."'
					AND `lang_id` = '".$lang."'
				ORDER BY `order` ASC
				LIMIT 1";
			break;
		}

		// if row found, swap ordering
		if ($db->query($sql) && $db->getCount()) {
			$swap = $db->getAssoc();

			$sql = "UPDATE `".TABLE_ARTICLES."`
				SET `order` = '".$order."'
				WHERE `id` = '".$swap['id']."'";
			$db->query($sql);

			$sql = "UPDATE `".TABLE_ARTICLES."`
				SET `order` = '".$swap['order']."'
				WHERE `id` = '".$id."'";
			$db->query($sql);
		}
	}

	if (isset($_POST['submit'])) {

		$orders = isset($_POST['orders']) ? $_POST['orders'] : array();

		$count = count($orders);

		if ($count && $count === count(array_unique($orders))) {
			$orders = array_flip($orders);
			ksort($orders);
			$orders = array_values($orders);

			foreach ($orders as $order => $id) {
				$id = (int) $id;

				$sql = "UPDATE `".TABLE_ARTICLES."`
					SET `order` = '".($order + 1)."'
					WHERE
						`id` = '".$id."'
						AND `lang_id` = '".$lang."'
				";
				$db->query($sql);
			}
		} else if ($count && $count !== count(array_unique($orders))) {
			$skin->assign('ERROR', 'Възникна грешка с някоя от стойностите за поредност!');
		}
	}

	// get all articles (no pagination limit!)
	$sql = "SELECT `id`, `title`, `order`
		FROM `".TABLE_ARTICLES."`
		WHERE `lang_id` = '".$lang."'
		ORDER BY `order` ASC";
	if (!$db->query($sql)) {
		$skin->assign('ERROR', $ml['L_ERROR_DB_QUERY']);
		return;
	}

	$result = array();
	for ($odd = 1; $row = $db->getAssoc(); $odd ^= 1) {
		$row['odd'] = $odd;
		$result[] = $row;
	}

	$skin->assign('RESULT', $result);
}

function ListArticles()
{
	global $db, $skin, $user, $ml, $page;
	$skin->assign('ACTION', 'list');

	// load old lang id or from form
	$lang = isset($_SESSION['lang']) ? (int) $_SESSION['lang'] : 0;
	$lang = isset($_POST['lang']) ? (int) $_POST['lang'] : $lang;

	// get all languages
	$sql = "SELECT
			`id`,
			`name`,
			`native`
		FROM `".TABLE_LANGUAGES."`
		WHERE `active` = '1'
		ORDER BY `default` DESC";
	if (!$db->query($sql) || !$db->getCount()) {
		MsgPush('error', $ml['L_ERROR_NOT_FOUND']);
		RedirectSite('admin/?page='.$page);
	}

	for ($x = 0, $langsID = array(), $langs = array(); $row = $db->getAssoc(); $x++) {
		$langsID[$row['id']] = $x;
		$langs[$x] = $row;
	}

	if ($lang <= 0 || !isset($langsID[$lang])) {
		$lang = $langs[0]['id'];
	}
	$langs[$langsID[$lang]]['selected'] = true;
	$skin->assign('LANGS', $langs);
	$_SESSION['lang'] = $lang;

	$where = FilterSearch($skin, 'SEARCH', 'search',
		array("a.`id`", "a.`title`", "s.`title`"),
		array('/[^a-zа-я\-\.\s\d\_@]+/iu', '/[\s]+/'),
		array('', ' ')
	);

	if ($where == '') {
		$where = " WHERE s.`id` = a.`script_id` AND a.`lang_id` = '".$lang."' ";
	}
	else {
		$where .= " AND s.`id` = a.`script_id` AND a.`lang_id` = '".$lang."' ";
	}

	$order = OrderByField($skin, $page, 'sort', 'order', 'delorder', array(
		'name' => array('field' => 'a.`title`', 'name' => 'Заглавие'),
		'url' => array('field' => 'a.`url`', 'name' => 'URL адрес'),
		'type' => array('field' => 's.`title`', 'name' => 'Тип')
	));

	if ($order == '') {
		$order = 'ORDER BY a.`order` ASC';
	}

	$count = 0;
	$sql = "SELECT
			COUNT(*) AS 'count'
		FROM
			`".TABLE_ARTICLES."` a,
			`".TABLE_SCRIPTS."` s
		".$where."
	";
	if ($db->query($sql) && $db->getCount()) {
		$count = $db->getAssoc();
		$count = $count['count'];
	}

	if ($count == 0) return false;
	$paging = new Paging($count, CFG('paging.count'), BASE_URL.'admin/', "pg", true, true, true);
	$paging->grouping = CFG('paging.groups');
	$skin->assign('PAGING', $paging->ShowNavigation());
	$skin->assign('COUNT', $count);

	$sql = "SELECT
			a.`id`,
			a.`title`,
			s.`title` AS 'type',
			a.`added`,
			a.`modified`,
			a.`url`
		FROM
			`".TABLE_ARTICLES."` a,
			`".TABLE_SCRIPTS."` s
		".$where."
		".$order."
	".$paging->GetMysqlLimits();

	if (!$db->query($sql)) {
		$skin->assign('ERROR', $ml['L_ERROR_DB_QUERY']);
		return;
	}

	$result = array();
	for ($x = 1, $odd = 1; $row = $db->getAssoc(); $x++, $odd ^= 1) {
		$row['nr'] = $x;
		$row['odd'] = $odd;

		$row['added'] = LocaleDate('d.m.Y H:i', $row['added']);
		$row['modified'] = LocaleDate('d.m.Y H:i', $row['modified']);

		$result[] = $row;
	}
	$skin->assign('RESULT', $result);
}
