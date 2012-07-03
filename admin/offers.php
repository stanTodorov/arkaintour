<?php
if (!defined('PROGRAM') && PROGRAM !== 1) exit;

function Main()
{
	global $skin, $title;
	$skin->assign('PAGE', 'offers');
	$title .= ' – Оферти';

	$action = isset($_GET['action']) ? $_GET['action'] : '';
	switch($action) {
	case 'add':
		AddOffer();
		break;
	case 'edit':
		EditOffer();
		break;
	case 'delete':
		DeleteOffer();
		break;
	case 'list':
	default:
		ListOffers();
	}

}

function AddOffer()
{
	global $db, $skin, $user, $ml, $page;
	$skin->assign('ACTION', 'add');

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

	// get all articles with offers
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
		ORDER BY a.`order` ASC";
	if ($db->query($sql) && $db->getCount()) {
		$pages = array();
		$ids = array();
		while ($row = $db->getAssoc()) {
			$ids[] = $row['id'];
			$pages[$row['id']] = array(
				'title' => $row['title']
			);
		}

		$ids = "'" . implode("', '", $ids) . "'";

		// get all categories of found articles
		$sql = "SELECT
				`order`,
				`id`, `title`,
				`article_id` AS 'aid'
			FROM `".TABLE_CATEGORIES."`
			WHERE `article_id` IN (".$ids.")
			ORDER BY `order` ASC
		";
		if ($db->query($sql) && $db->getCount()) {
			while ($row = $db->getAssoc()) {
				$pages[$row['aid']]['list'][] = $row;
			}
		}

		$pages = array_values($pages);
		$skin->assign('CATEGORIES', $pages);
	}

	$skin->assign('RESULT', array('lang' => $lang));

	if (!isset($_POST['submit'])) return;

	$errors = FormValidate($_POST, $data, array(
		'lang' => array('req' => true, 'is' => 'int'),
		'category' => array('req' => true, 'is' => 'int'),
		'name' => array('req' => true, 'min' => 1, 'max' => 256),
		'route' => array('min' => 1, 'max' => 256),
		'transport' => array('min' => 1, 'max' => 256),
		'duration' => array('min' => 1, 'max' => 256),
		'price' => array('min' => 1, 'max' => 256),
		'date' => array(),
		'vip_offer' => array('is' => 'int', 'default' => '0'),
		'content' => array('req' => true, 'min' => 1, 'max' => 65535)
	));

	// format date ranges
	foreach ($data['date'] as $key => $date) {
		if ($date == '') continue;
		if (!preg_match('/^[\d]{2}\.[\d]{2}\.[\d]{4}$/', $date)) {
			$errors['dates'] = 'Една или повече дати са с невалиден формат!';
			break;
		}
	}


	$resizes = GetOffersDirs();
	reset($resizes);
	$basePath = current($resizes);
	$basePath = $basePath['path'];
	$files = array();
	$count = 0;

	if (isset($_FILES['pictures']) && count($_FILES['pictures'])) {
		foreach ($_FILES['pictures']['error'] as $file => $error) {
			if ($error !== UPLOAD_ERR_OK) {
				continue;
			}

			$count++;

			$uploaded = $_FILES['pictures']['tmp_name'][$file];
			$name = $_FILES['pictures']['name'][$file];
			$filename = GetValidFilename($basePath, $name);
			$filename = $filename['filename'];

			try {
				if (!@move_uploaded_file($uploaded, $basePath . $filename)) {
					throw new Exception("Can't move uploaded file!");
				}

				$img = new ImageResize($basePath . $filename);

				$paths = array();

				foreach ($resizes as $type => $image) {
					if ($type == 'normal') {
						$img->resize($image['size'], $image['size'], 'auto', ImageResize::OPT_RESIZE_INCREASE, '#fff');
					} else {
						$img->resize($image['size'], $image['size'], 'crop', ImageResize::OPT_RESIZE_ALWAYS, '#fff');
					}
					$img->save($image['path'] . $filename, $image['quality']);
					$paths[$image['name']] = $image['location'];
				}

				$files[] = array (
					'filename' => $filename,
					'path' => $paths
				);

			} catch(Exception $e) {
				@unlink($basePath . $filename);
				@unlink($uploaded);
			}
		}
	}

	if ($count && !count($files)) {
		$errors['pictures'] = 'Възникна грешка с всички качени снимки!';
	}

	if (count($errors) > 0) {
		$skin->assign('ERROR', $ml['L_ERROR_INPUT']);
		$skin->assign('RESULT', $data);
		$skin->assign('ERRORS', $errors);

		if (count($files)) {
			foreach ($files as $file) {
				foreach ($file['path'] as $path) {
					@unlink(BASE_PATH . $path . $file['filename']);
					DeleteEmptyTree(BASE_PATH . $path);
				}
			}
		}
		return;
	}

	// create new category and get id
	$sql = "INSERT INTO `".TABLE_OFFERS."` (
			`id`,
			`lang_id`,
			`category_id`,
			`vip_offer`,
			`name`,
			`content`,
			`route`,
			`duration`,
			`transport`,
			`price`,
			`added`,
			`added_by`,
			`modified`,
			`modified_by`
		) VALUE (
			NULL,
			'".$data['lang']."',
			'".$data['category']."',
			'".$data['vip_offer']."',
			'".$db->escapeString($data['name'])."',
			'".$db->escapeString($data['content'])."',
			'".$db->escapeString($data['route'])."',
			'".$db->escapeString($data['duration'])."',
			'".$db->escapeString($data['transport'])."',
			'".$db->escapeString($data['price'])."',
			NOW(),
			'".( (int) $user['id'])."',
			NOW(),
			'".( (int) $user['id'])."'
		)";
	if (!$db->query($sql) || !$db->getCount()) {
		$skin->assign('ERROR', $ml['L_ERROR_DB_QUERY']);
		$skin->assign('RESULT', $data);

		if (count($files)) {
			foreach ($files as $file) {
				foreach ($file['path'] as $path) {
					@unlink(BASE_PATH . $path . $file['filename']);
					DeleteEmptyTree(BASE_PATH . $path);
				}
			}
		}
		return;
	}

	$id = $db->getLastId();

	if (count($data['date'])) {
		$values = '';

		foreach ($data['date'] as $date) {
			if (!($date = ParseInputDate($date))) continue;

			$values .= "(NULL, '".$id."', '";
			$values .= $db->escapeString(date("Y-m-d", $date)) . "'), ";
		}

		$values = rtrim($values, ", ");

		$sql = "INSERT INTO `".TABLE_OFFERS_PERIODS."` (
				`id`,
				`offer_id`,
				`date`
			) VALUES ".$values;
		$db->query($sql);

	}

	if (count($files)) {
		$values = '';

		foreach ($files as $file) {
			$values .= "(NULL, '".$id."', '";
			$values .= $db->escapeString($file['filename']) . "', ";
			$values .= 'NOW()), ';
		}

		$values = rtrim($values, ", ");

		$sql = "INSERT INTO `".TABLE_OFFERS_PICTURES."` (
				`id`,
				`offer_id`,
				`filename`,
				`date`
			) VALUES ".$values;
		$db->query($sql);
	}

	$_SESSION['lang'] = $data['lang'];

	MsgPush('success', 'Успешно добавена оферта!');
	RedirectSite('admin/?page='.$page);
}

function EditOffer()
{
	global $db, $skin, $user, $ml, $page;
	$skin->assign('ACTION', 'edit');

	$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
	if ($id <= 0) {
		MsgPush('error', $ml['L_ERROR_NOT_FOUND']);
		RedirectSite('admin/?page='.$page);
	}

	$skin->assign('ID', $id);

	// get offer data if exists
	$sql = "SELECT
			`category_id` AS 'category',
			`vip_offer`,
			`name`,
			`content`,
			`route`,
			`duration`,
			`transport`,
			`price`,
			`lang_id` as 'lang'
		FROM `".TABLE_OFFERS."`
		WHERE `id` = '".$id."'
		LIMIT 1";
	if (!$db->query($sql) || !$db->getCount()) {
		MsgPush('error', $ml['L_ERROR_NOT_FOUND']);
		RedirectSite('admin/?page='.$page);
	}

	$result = $db->getAssoc();
	$lang = $result['lang'];

	// get dates if exists
	$sql = "SELECT UNIX_TIMESTAMP(`date`) AS 'date'
		FROM `".TABLE_OFFERS_PERIODS."`
		WHERE `offer_id` = '".$id."'";
	if ($db->query($sql) && $db->getCount()) {
		$result['date'] = array();

		while ($row = $db->getAssoc()) {
			// Do not edit date format!
			$result['date'][] = date('d.m.Y', $row['date']);
		}
	}

	// remove selected images?
	$remove = isset($_POST['remove']) ? $_POST['remove'] : array();
	if (is_array($remove) && count($remove)) {
		$ids = array();

		foreach ($remove as $pic => $tmp) {
			$ids[] = (int) $pic;
		}

		$ids = "'" . implode("', '", $ids) . "'";

		$sql = "SELECT `filename`
			FROM `".TABLE_OFFERS_PICTURES."`
			WHERE `id` IN (".$ids.")";
		if ($db->query($sql) && $db->getCount()) {
			$resizes = GetOffersDirs();

			while ($row = $db->getAssoc()) {
				foreach ($resizes as $image) {
					@unlink($image['path'] . $row['filename']);
					DeleteEmptyTree($image['path']);
				}
			}

			$sql = "DELETE FROM `".TABLE_OFFERS_PICTURES."`
				WHERE `id` IN (".$ids.")";
			$db->query($sql);
		}
	}

	// get images if exists
	$sql = "SELECT `id`, `filename`
		FROM `".TABLE_OFFERS_PICTURES."`
		WHERE `offer_id` = '".$id."'";
	if ($db->query($sql) && $db->getCount()) {

		$resize = GetOffersDirs();

		$result['images'] = array();

		while ($row = $db->getAssoc()) {
			$result['images'][] = array(
				'id' => $row['id'],
				'image' => $resize['normal']['url'] . $row['filename'],
				'thumb' => $resize['small']['url'] . $row['filename']
			);
		}
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

	// get all articles with offers
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
		ORDER BY a.`order` ASC";
	if ($db->query($sql) && $db->getCount()) {
		$pages = array();
		$ids = array();
		while ($row = $db->getAssoc()) {
			$ids[] = $row['id'];
			$pages[$row['id']] = array(
				'title' => $row['title']
			);
		}

		$ids = "'" . implode("', '", $ids) . "'";

		// get all categories of found articles
		$sql = "SELECT
				`order`,
				`id`, `title`,
				`article_id` AS 'aid'
			FROM `".TABLE_CATEGORIES."`
			WHERE `article_id` IN (".$ids.")
			ORDER BY `order` ASC
		";
		if ($db->query($sql) && $db->getCount()) {
			while ($row = $db->getAssoc()) {
				$pages[$row['aid']]['list'][] = $row;
			}
		}

		$pages = array_values($pages);
		$skin->assign('CATEGORIES', $pages);
	}

	$result['lang'] = $lang;
	$skin->assign('RESULT', $result);

	if (!isset($_POST['submit'])) return;

	$errors = FormValidate($_POST, $data, array(
		'lang' => array('req' => true, 'is' => 'int'),
		'category' => array('req' => true, 'is' => 'int'),
		'name' => array('req' => true, 'min' => 1, 'max' => 256),
		'route' => array('min' => 1, 'max' => 256),
		'transport' => array('min' => 1, 'max' => 256),
		'duration' => array('min' => 1, 'max' => 256),
		'price' => array('min' => 1, 'max' => 256),
		'date' => array(),
		'vip_offer' => array('is' => 'int', 'default' => '0'),
		'content' => array('req' => true, 'min' => 1, 'max' => 65535)
	));

	$data['images'] = isset($result['images']) ? $result['images'] : array();

	// format date ranges
	foreach ($data['date'] as $key => $date) {
		if ($date == '') continue;
		if (!preg_match('/^[\d]{2}\.[\d]{2}\.[\d]{4}$/', $date)) {
			$errors['dates'] = 'Една или повече дати са с невалиден формат!';
			break;
		}
	}

	$resizes = GetOffersDirs();
	reset($resizes);
	$basePath = current($resizes);
	$basePath = $basePath['path'];
	$files = array();
	$count = 0;

	if (isset($_FILES['pictures']) && count($_FILES['pictures'])) {
		foreach ($_FILES['pictures']['error'] as $file => $error) {
			if ($error !== UPLOAD_ERR_OK) {
				continue;
			}

			$count++;

			$uploaded = $_FILES['pictures']['tmp_name'][$file];
			$name = $_FILES['pictures']['name'][$file];
			$filename = GetValidFilename($basePath, $name);
			$filename = $filename['filename'];

			try {
				if (!@move_uploaded_file($uploaded, $basePath . $filename)) {
					throw new Exception("Can't move uploaded file!");
				}

				$img = new ImageResize($basePath . $filename);

				$paths = array();

				foreach ($resizes as $type => $image) {
					if ($type == 'normal') {
						$img->resize($image['size'], $image['size'], 'auto', ImageResize::OPT_RESIZE_INCREASE, '#fff');
					} else {
						$img->resize($image['size'], $image['size'], 'crop', ImageResize::OPT_RESIZE_ALWAYS, '#fff');
					}
					$img->save($image['path'] . $filename, $image['quality']);

					$paths[$image['name']] = $image['location'];
				}

				$files[] = array (
					'filename' => $filename,
					'path' => $paths
				);

			} catch(Exception $e) {
				@unlink($basePath . $filename);
				@unlink($uploaded);
			}
		}
	}

	if ($count && !count($files)) {
		$errors['pictures'] = 'Възникна грешка с всички качени снимки!';
	}

	if (count($errors) > 0) {
		$skin->assign('ERROR', $ml['L_ERROR_INPUT']);
		$skin->assign('RESULT', $data);
		$skin->assign('ERRORS', $errors);

		if (count($files)) {
			foreach ($files as $file) {
				foreach ($file['path'] as $path) {
					@unlink(BASE_PATH . $path . $file['filename']);
					DeleteEmptyTree(BASE_PATH . $path);
				}
			}
		}
		return;
	}

	// create new category and get id
	$sql = "UPDATE `".TABLE_OFFERS."`
		SET
			`category_id` = '".$data['category']."',
			`vip_offer` = '".$data['vip_offer']."',
			`name` = '".$db->escapeString($data['name'])."',
			`content` = '".$db->escapeString($data['content'])."',
			`route` = '".$db->escapeString($data['route'])."',
			`duration` = '".$db->escapeString($data['duration'])."',
			`transport` = '".$db->escapeString($data['transport'])."',
			`price` = '".$db->escapeString($data['price'])."',
			`modified` = NOW(),
			`modified_by` = '".( (int) $user['id'])."'
		WHERE `id` = '".$id."'";
	if (!$db->query($sql) || !$db->getCount()) {
		$skin->assign('ERROR', $ml['L_ERROR_DB_QUERY']);
		$skin->assign('RESULT', $data);

		if (count($files)) {
			foreach ($files as $file) {
				foreach ($file['path'] as $path) {
					@unlink(BASE_PATH . $path . $file['filename']);
					DeleteEmptyTree(BASE_PATH . $path);
				}
			}
		}
		return;
	}

	// delete old dates
	$sql = "DELETE FROM `".TABLE_OFFERS_PERIODS."`
		WHERE `offer_id` = '".$id."'";
	$db->query($sql);

	if (count($data['date'])) {
		$values = '';

		foreach ($data['date'] as $date) {
			if (!($date = ParseInputDate($date))) continue;

			$values .= "(NULL, '".$id."', '";
			$values .= $db->escapeString(date("Y-m-d", $date)) . "'), ";
		}

		$values = rtrim($values, ", ");

		$sql = "INSERT INTO `".TABLE_OFFERS_PERIODS."` (
				`id`,
				`offer_id`,
				`date`
			) VALUES ".$values;
		$db->query($sql);
	}

	if (count($files)) {
		$values = '';

		foreach ($files as $file) {
			$values .= "(NULL, '".$id."', '";
			$values .= $db->escapeString($file['filename']) . "', ";
			$values .= 'NOW()), ';
		}

		$values = rtrim($values, ", ");

		$sql = "INSERT INTO `".TABLE_OFFERS_PICTURES."` (
				`id`,
				`offer_id`,
				`filename`,
				`date`
			) VALUES ".$values;
		$db->query($sql);
	}


	MsgPush('success', 'Успешно редактирана оферта!');
	RedirectSite('admin/?page='.$page . '&action=edit&id=' . $id);
}

function DeleteOffer()
{
	global $db, $skin, $user, $ml, $page;


	$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
	if ($id <= 0) {
		MsgPush('error', $ml['L_ERROR_NOT_FOUND']);
		RedirectSite('admin/?page=' . $page);
	}

	// delete offer's periods
	$sql = "DELETE FROM `".TABLE_OFFERS_PERIODS."`
		WHERE `offer_id` = '".$id."'";
	if (!$db->query($sql)) {
		MsgPush('error', $ml['L_ERROR_DB_QUERY']);
		RedirectSite('admin/?page=' . $page);
	}

	// delete images
	$sql = "SELECT `filename`
		FROM `".TABLE_OFFERS_PICTURES."`
		WHERE `offer_id` = '".$id."'";
	if ($db->query($sql) && $db->getCount()) {
		$resizes = GetOffersDirs();

		while ($row = $db->getAssoc()) {
			foreach ($resizes as $dir) {
				@unlink($dir['path'] . $row['filename']);
				DeleteEmptyTree($dir['path']);
			}
		}

		$sql = "DELETE FROM `".TABLE_OFFERS_PICTURES."`
			WHERE `offer_id` = '".$id."'";
		$db->query($sql);
	}

	// delete offer
	$sql = "DELETE FROM `".TABLE_OFFERS."`
		WHERE `id` = '".$id."'
		LIMIT 1";
	if (!$db->query($sql) || !$db->getCount()) {
		MsgPush('error', $ml['L_ERROR_DB_QUERY'].$sql);
		RedirectSite('admin/?page=' . $page);
	}

	MsgPush('success', 'Успешно изтрита оферта!');
	RedirectSite('admin/?page=' . $page);
}

function ListOffers()
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
		array("o.`id`", "o.`content`", "o.`name`", "o.`price`", "c.`title`", "a.`title`"),
		array('/[^a-zа-я\-\.\s\d\_@]+/iu', '/[\s]+/'),
		array('', ' ')
	);

	if ($where == '') {
		$where = "WHERE o.`lang_id` = '".$lang."'";
	} else {
		$where .= " AND o.`lang_id` = '".$lang."'";
	}

	$order = OrderByField($skin, $page, 'sort', 'order', 'delorder', array(
		'offer' => array('field' => 'o.`name`', 'name' => 'Оферта'),
		'category' => array('field' => 'c.`title`', 'name' => 'Категория'),
		'article' => array('field' => 'a.`title`', 'name' => 'Страница'),
		'vip' => array('field' => 'o.`vip_offer`', 'name' => 'Топ оферта'),
		'added' => array('field' => 'o.`added`', 'name' => 'Добавено'),
	));

	if ($order == '') {
		$order = 'ORDER BY o.`added` DESC';
	}

	$count = 0;
	$sql = "SELECT
			COUNT(*) AS 'count'
		FROM `".TABLE_OFFERS."` o

		LEFT JOIN `".TABLE_CATEGORIES."` c ON
			c.`id` = o.`category_id`

		LEFT JOIN `".TABLE_ARTICLES."` a ON
			a.`id` = c.`article_id`
		".$where."
		".$order."
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
			o.`id`,
			o.`name`,
			o.`price`,
			o.`vip_offer`,
			UNIX_TIMESTAMP(o.`added`) AS 'added',
			c.`title` AS 'category',
			a.`title` AS 'article',
			p.`filename` AS 'picture'
		FROM `".TABLE_OFFERS."` o

		LEFT JOIN `".TABLE_CATEGORIES."` c ON
			c.`id` = o.`category_id`

		LEFT JOIN `".TABLE_ARTICLES."` a ON
			a.`id` = c.`article_id`

		LEFT JOIN `".TABLE_OFFERS_PICTURES."` p ON
			o.`id` = p.`offer_id`

		".$where."
		GROUP BY o.`id`
		".$order."
	".$paging->GetMysqlLimits();

	if (!$db->query($sql)) {
		$skin->assign('ERROR', $ml['L_ERROR_DB_QUERY']);
		return;
	}

	$resize = GetOffersDirs();

	$result = array();
	for ($x = 1, $odd = 0; $row = $db->getAssoc(); $x++, $odd ^= 1) {
		$row['nr'] = $x;
		$row['odd'] = $odd;

		if ($row['picture']) {
			$row['image'] = $resize['normal']['url'] . $row['picture'];
			$row['thumb'] = $resize['small']['url'] . $row['picture'];
		}

		$row['added'] = LocaleDate('d.m.Y H:i', $row['added']);

		$result[] = $row;
	}

	$skin->assign('RESULT', $result);
}
