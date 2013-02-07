<?php
if (!defined('PROGRAM') || PROGRAM !== 1) exit;

function Main()
{
	global $skin, $title;
	$skin->assign('PAGE', 'categories');
	$title .= ' – Категории';

	$action = isset($_GET['action']) ? $_GET['action'] : '';
	switch($action) {
	case 'add':
		AddCategory();
		break;
	case 'edit':
		EditCategory();
		break;
	case 'delete':
		DeleteCategory();
		break;
	case 'sort':
		SortCategories();
		break;
	case 'list':
	default:
		ListCategories();
	}
}

function AddCategory()
{
	global $db, $skin, $user, $ml, $page;
	$skin->assign('ACTION', 'add');

	$lang = isset($_SESSION['lang']) ? (int) $_SESSION['lang'] : 0;
	$lang = isset($_POST['lang']) ? (int) $_POST['lang'] : $lang;

	$baseUrl = BASE_URL . CFG('upload.dir.icons') . DS;

	// get icons
	$icons = array();
	$sql = "SELECT
			`id`,
			`filename`
		FROM `".TABLE_ICONS."`
		ORDER BY `added` DESC";
	if ($db->query($sql) && $db->getCount()) {
		while ($row = $db->getAssoc()) {
			$row['image'] = $baseUrl . $row['filename'];
			$icons[] = array(
				'id' => $row['id'],
				'image' => $row['image']
			);
		}
	}
	$skin->assign('ICONS', $icons);

	// get All languages
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

	// get pages list
	$sql = "SELECT
			a.`id`,
			a.`title`
		FROM
			`".TABLE_ARTICLES."` a,
			`".TABLE_SCRIPTS."` s,
			`".TABLE_LANGUAGES."` l
		WHERE
			a.`script_id` = s.`id`
			AND s.`has_offers` = '1'
			AND a.`lang_id` = l.`id`
			AND l.`id` = '".$lang."'
			AND l.`active` = '1'
		ORDER BY a.`title` ASC";
	if (!$db->query($sql)) {
		$skin->assign('ERROR', $ml['L_ERROR_DB_QUERY']);
		return;
	}

	$pages = array();
	while ($row = $db->getAssoc()) {
		$pages[] = array(
			'id' => $row['id'],
			'name' => $row['title']
		);
	}

	$skin->assign('PAGES', $pages);
	$skin->assign('RESULT', array('lang' => $lang));

	if (!isset($_POST['submit'])) return;

	$errors = FormValidate($_POST, $data, array(
		'page' => array('req' => true, 'is' => 'int'),
		'lang' => array('req' => true, 'is' => 'int'),
		'icon' => array('req' => true, 'is' => 'int'),
		'name' => array('req' => true, 'min' => 1, 'max' => 256),
		'visible' => array('between' => array(0, 1), 'default' => '0', 'is' => 'int')
	));

	if (count($errors) > 0) {
		$skin->assign('ERROR', $ml['L_ERROR_INPUT']);
		$skin->assign('RESULT', $data);
		$skin->assign('ERRORS', $errors);
		return;
	}

	$order = 1;
	$sql = "SELECT `order`
		FROM `".TABLE_CATEGORIES."`
		WHERE
			`lang_id` = '".$data['lang']."'
			AND `article_id` = '".$data['page']."'
		ORDER BY `order` DESC
		LIMIT 1";
	if ($db->query($sql) && $db->getCount()) {
		$order = $db->getAssoc();
		$order = $order['order'];
		$order++;
	}

	// create new category and get id
	$sql = "INSERT INTO `".TABLE_CATEGORIES."` (
			`id`,
			`lang_id`,
			`article_id`,
			`icon_id`,
			`title`,
			`visible`,
			`order`,
			`added`,
			`added_by`,
			`modified`,
			`modified_by`
		) VALUE (
			NULL,
			'".$data['lang']."',
			'".$data['page']."',
			'".$data['icon']."',
			'".$db->escapeString($data['name'])."',
			'".$data['visible']."',
			'".$order."',
			NOW(),
			'".$user['id']."',
			NOW(),
			'".$user['id']."'
		)";
	if (!$db->query($sql) || !$db->getCount()) {
		$skin->assign('ERROR', $ml['L_ERROR_DB_QUERY']);
		$skin->assign('RESULT', $data);
		return;
	}

	$_SESSION['lang'] = $data['lang'];

	MsgPush('success', 'Успешно добавена категория!');
	RedirectSite('admin/?page='.$page);
}

function EditCategory()
{
	global $db, $skin, $user, $ml, $page;
	$skin->assign('ACTION', 'edit');

	$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
	if ($id <= 0) {
		MsgPush('error', $ml['L_ERROR_NOT_FOUND']);
		RedirectSite('admin/?page=' . $page);
	}

	$baseUrl = BASE_URL . CFG('upload.dir.icons') . DS;

	// get icons
	$icons = array();
	$sql = "SELECT
			`id`,
			`filename`
		FROM `".TABLE_ICONS."`
		ORDER BY `added` DESC";
	if ($db->query($sql) && $db->getCount()) {
		while ($row = $db->getAssoc()) {
			$row['image'] = $baseUrl . $row['filename'];
			$icons[] = array(
				'id' => $row['id'],
				'image' => $row['image']
			);
		}
	}
	$skin->assign('ICONS', $icons);

	// get All languages
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

	// check for category
	$sql = "SELECT
			`icon_id` AS 'icon',
			`article_id` AS 'page',
			`title` AS 'name',
			`lang_id` AS 'lang',
			`order`,
			`visible`
		FROM
			`".TABLE_CATEGORIES."`
		WHERE `id` = '".$id."'
		LIMIT 1
	";
	if (!$db->query($sql) || !$db->getCount()) {
		MsgPush('error', $ml['L_ERROR_NOT_FOUND']);
		RedirectSite('admin/?page=' . $page);
	}

	$result = $db->getAssoc();
	$skin->assign('RESULT', $result);
	$skin->assign('ID', $id);

	$lang = $result['lang'];
	$lang = isset($_POST['lang']) ? (int) $_POST['lang'] : $lang;

	// get pages list
	$sql = "SELECT
			a.`id`,
			a.`title`
		FROM
			`".TABLE_ARTICLES."` a,
			`".TABLE_SCRIPTS."` s,
			`".TABLE_LANGUAGES."` l
		WHERE
			a.`script_id` = s.`id`
			AND s.`has_offers` = '1'
			AND a.`lang_id` = l.`id`
			AND l.`id` = '".$lang."'
			AND l.`active` = '1'
		ORDER BY a.`title` ASC";
	if (!$db->query($sql)) {
		$skin->assign('ERROR', $ml['L_ERROR_DB_QUERY']);
		return;
	}

	$pages = array();
	while ($row = $db->getAssoc()) {
		$pages[] = array(
			'id' => $row['id'],
			'name' => $row['title']
		);
	}

	$skin->assign('PAGES', $pages);

	if (!isset($_POST['submit'])) return;

	$errors = FormValidate($_POST, $data, array(
		'page' => array('req' => true, 'is' => 'int'),
		'icon' => array('req' => true, 'is' => 'int'),
		'name' => array('req' => true, 'min' => 1, 'max' => 256),
		'lang' => array('req' => true, 'is' => 'int'),
		'visible' => array('between' => array(0, 1), 'default' => '0', 'is' => 'int')
	));

	if (count($errors) > 0) {
		$skin->assign('ERROR', $ml['L_ERROR_INPUT']);
		$skin->assign('RESULT', $data);
		$skin->assign('ERRORS', $errors);
		return;
	}

	$isSwapped = false;
	$order = $result['order'];
	if ($result['lang'] !== $data['lang'] || $result['page'] !== $data['page']) {
		$sql = "SELECT `order`
			FROM `".TABLE_CATEGORIES."`
			WHERE
				`lang_id` = '".$data['lang']."'
				AND `article_id` = '".$data['page']."'
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

	// edit category
	$sql = "UPDATE `".TABLE_CATEGORIES."`
		SET
			`icon_id` = '".$data['icon']."',
			`lang_id` = '".$data['lang']."',
			`article_id` = '".$data['page']."',
			`title` = '".$db->escapeString($data['name'])."',
			`order` = '".$order."',
			`visible` = '".$data['visible']."',
			`modified` = NOW(),
			`modified_by` = '".$user['id']."'
		WHERE `id` = '".$id."'";
	if (!$db->query($sql) || !$db->getCount()) {
		$skin->assign('ERROR', $ml['L_ERROR_DB_QUERY']);
		$skin->assign('RESULT', $data);
		return;
	}

	// update ordering of previous used chain
	if ($isSwapped) {
		$sql = "UPDATE `".TABLE_CATEGORIES."`
			SET `order` = `order` - '1'
			WHERE
				`order` > '".$result['order']."'
				AND `lang_id` = '".$result['lang']."'
				AND `article_id` = '".$result['page']."'
		";
		if (!$db->query($sql)) {
			MsgPush('error', $ml['L_ERROR_DB_QUERY']);
			RedirectSite('admin/?page='.$page);
		}
	}

	$_SESSION['lang'] = $data['lang'];

	MsgPush('success', 'Успешно променена категория!');
	RedirectSite('admin/?page='.$page);
}

function DeleteCategory()
{
	global $db, $skin, $user, $ml, $page;

	$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
	if ($id <= 0) {
		MsgPush('error', $ml['L_ERROR_NOT_FOUND']);
		RedirectSite('admin/?page=' . $page);
	}

	// check availability
	$sql = "SELECT `lang_id`, `article_id`, `order`
		FROM `".TABLE_CATEGORIES."`
		WHERE `id` = '".$id."'
		LIMIT 1";
	if (!$db->query($sql) || !$db->getCount()) {
		MsgPush('error', $ml['L_ERROR_DB_QUERY']);
		RedirectSite('admin/?page=' . $page);
	}

	$order = $db->getAssoc();

	// delete category
	$sql = "DELETE FROM `".TABLE_CATEGORIES."`
		WHERE `id` = '".$id."'
		LIMIT 1";
	if (!$db->query($sql) || !$db->getCount()) {
		MsgPush('error', $ml['L_ERROR_DB_QUERY']);
		RedirectSite('admin/?page=' . $page);
	}

	// update ordering of all others
	$sql = "UPDATE `".TABLE_CATEGORIES."`
		SET `order` = `order` - '1'
		WHERE
			`order` > '".$order['order']."'
			AND `lang_id` = '".$order['lang_id']."'
			AND `article_id` = '".$order['article_id']."'
	";
	if (!$db->query($sql)) {
		MsgPush('error', $ml['L_ERROR_DB_QUERY']);
		RedirectSite('admin/?page='.$page);
	}

	MsgPush('success', 'Успешно изтрита категория!');
	RedirectSite('admin/?page=' . $page);
}

function SortCategories()
{
	global $db, $skin, $user, $ml;
	$skin->assign('ACTION', 'sort');

	$lang = isset($_SESSION['lang']) ? (int) $_SESSION['lang'] : 0;

	// get page
	$page = isset($_SESSION['article_id']) ? (int) $_SESSION['article_id'] : 0;
	$page = isset($_POST['page']) ? (int) $_POST['page'] : $page;

	$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
	$dir = isset($_GET['dir']) ? $_GET['dir'] : '';

	if ($id > 0 && ($dir === 'up' || $dir === 'down')) {
		$sql = "SELECT `order`
			FROM `".TABLE_CATEGORIES."`
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
				FROM `".TABLE_CATEGORIES."`
				WHERE
					`order` < '".$order."'
					AND `lang_id` = '".$lang."'
					AND `article_id` = '".$page."'
				ORDER BY `order` DESC
				LIMIT 1";
			break;
		case 'down':
			$sql = "SELECT `order`, `id`
				FROM `".TABLE_CATEGORIES."`
				WHERE
					`order` > '".$order."'
					AND `lang_id` = '".$lang."'
					AND `article_id` = '".$page."'
				ORDER BY `order` ASC
				LIMIT 1";
			break;
		}

		// if row found, swap ordering
		if ($db->query($sql) && $db->getCount()) {
			$swap = $db->getAssoc();

			$sql = "UPDATE `".TABLE_CATEGORIES."`
				SET `order` = '".$order."'
				WHERE `id` = '".$swap['id']."'";
			$db->query($sql);

			$sql = "UPDATE `".TABLE_CATEGORIES."`
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

				$sql = "UPDATE `".TABLE_CATEGORIES."`
					SET `order` = '".($order + 1)."'
					WHERE
						`id` = '".$id."'
						AND `lang_id` = '".$lang."'
						AND `article_id` = '".$page."'
				";
				$db->query($sql);
			}
		} else if ($count && $count !== count(array_unique($orders))) {
			$skin->assign('ERROR', 'Възникна грешка с някоя от стойностите за поредност!');
		}
	}

	$sql = "SELECT
			a.`id`,
			a.`title` AS 'name'
		FROM
			`".TABLE_ARTICLES."` a,
			`".TABLE_SCRIPTS."` s
		WHERE
			a.`lang_id` = '".$lang."'
			AND s.`id` = a.`script_id`
			AND s.`has_offers` = '1'
	";
	if (!$db->query($sql) || !$db->getCount()) {
		return;
	}

	$pages = array();
	$pagesID = array();
	for ($i = 0; $row = $db->getAssoc(); $i++) {
		$pagesID[$row['id']] = $i;
		$pages[$i] = $row;
	}

	if ($page <= 0 || !isset($pagesID[$page])) {
		$page = $pages[0]['id'];
	}

	$pages[$pagesID[$page]]['selected'] = true;
	$_SESSION['article_id'] = $page;

	$skin->assign('PAGES', $pages);

	// get all articles (no pagination limit!)
	$sql = "SELECT `id`, `title`, `order`
		FROM `".TABLE_CATEGORIES."`
		WHERE
			`lang_id` = '".$lang."'
			AND `article_id` = '".$page."'
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

function ListCategories()
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
		array("c.`id`", "c.`title`", "a.`title`"),
		array('/[^a-zа-я\-\.\s\d\_@]+/iu', '/[\s]+/'),
		array('', ' ')
	);

	if ($where == '') {
		$where = "WHERE c.`lang_id` = '".$lang."'";
	} else {
		$where .= " AND c.`lang_id` = '".$lang."'";
	}


	$order = OrderByField($skin, $page, 'sort', 'order', 'delorder', array(
		'name' => array('field' => 'c.`title`', 'name' => 'Име'),
		'article' => array('field' => 'a.`title`', 'name' => 'Страница'),
		'added' => array('field' => 'c.`added`', 'name' => 'Добавено'),
		'visible' => array('field' => 'c.`visible`', 'name' => 'Видимост')
	));

	if ($order == '') {
		$order = 'ORDER BY c.`order` ASC';
	} else {
		$order .= ', c.`order` ASC';
	}

	$count = 0;
	$sql = "SELECT
			COUNT(*) AS 'count'
		FROM `".TABLE_CATEGORIES."` c

		LEFT JOIN `".TABLE_ARTICLES."` a ON
			a.`id` = c.`article_id`
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

	$baseURL = BASE_URL . CFG('upload.dir.icons') . DS;

	$sql = "SELECT
			c.`id`,
			c.`title` AS 'name',
			c.`visible`,
			a.`title` AS 'article',
			UNIX_TIMESTAMP(c.`added`) AS 'added',
			UNIX_TIMESTAMP(c.`modified`) AS 'modified',
			i.`filename`
		FROM `".TABLE_CATEGORIES."` c

		LEFT JOIN `".TABLE_ICONS."` i ON
			i.`id` = c.`icon_id`

		LEFT JOIN `".TABLE_ARTICLES."` a ON
			a.`id` = c.`article_id`
		".$where."
		".$order."
	".$paging->GetMysqlLimits();
	if (!$db->query($sql)) {
		$skin->assign('ERROR', $ml['L_ERROR_DB_QUERY']);
		return;
	}

	$result = array();
	for ($x = 1, $odd = 0; $row = $db->getAssoc(); $x++, $odd ^= 1) {
		$row['nr'] = $x;
		$row['odd'] = $odd;

		$row['modified'] = LocaleDate('d.m.Y H:i', $row['modified']);
		$row['added'] = LocaleDate('d.m.Y H:i', $row['added']);

		$row['image'] = false;
		if ($row['filename']) {
			$row['image'] = $baseURL . $row['filename'];
		}

		$result[] = $row;
	}

	$skin->assign('RESULT', $result);
}
