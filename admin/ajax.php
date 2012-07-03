<?php
if (!defined('PROGRAM') || PROGRAM !== 1) exit;

function Main()
{
	global $db, $ml, $skin, $user, $categories;

	$result = array();
	$action = isset($_GET['action']) ? $_GET['action'] : '';

	$default = array(
		'status' => 'error',
		'message' => ''
	);

	switch($action) {
	case 'setting':
		$result = Setting();
		break;
	case 'options':
		$result = Options();
		break;
	case 'languages':
		$result = Languages();
		break;
	}


	if (!is_array($result) || !count($result)) {
		echo json_encode($default);
		exit;
	}

	echo json_encode(array_merge($default, $result));
	exit;
}


function Languages()
{
	global $db, $skin, $ml;


	$lang = isset($_POST['lang']) ? (int) $_POST['lang'] : 0;

	switch((isset($_POST['which']) ? $_POST['which'] : '')) {
	case 'categories':
		$skin->assign('ACTION', 'languages');
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

		if (!$db->query($sql) || !$db->getCount()) {
			return array(
				'status' => 'error',
				'node' => array(
					'where' => '#pages',
					'content' => '',
					'inside' => true
				)
			);
		}

		$skin->assign('RESULT', $db->getAssocArray());

		return array(
			'status' => 'success',
			'node' => array(
				'where' => '#pages',
				'content' => $skin->fetch('ajax.html'),
				'inside' => true
			)
		);
		break;
	case 'offers':
		$skin->assign('ACTION', 'categories');

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

		if (!$db->query($sql) || !$db->getCount()) {
			return array();
		}

		$result = array();
		$ids = array();
		while ($row = $db->getAssoc()) {
			$ids[] = $row['id'];
			$result[$row['id']] = array(
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
		if (!$db->query($sql) || !$db->getCount()) {
			return array();
		}

		while ($row = $db->getAssoc()) {
			$result[$row['aid']]['list'][] = $row;
		}

		$result = array_values($result);
		$skin->assign('RESULT', $result);

		return array(
			'status' => 'success',
			'node' => array(
				'where' => '#pages',
				'content' => $skin->fetch('ajax.html'),
				'inside' => true
			)
		);
		break;
	}
}

function Options()
{
	global $db, $skin, $ml;
	$skin->assign('ACTION', 'options');

	$what = isset($_POST['what']) ? $_POST['what'] : '';

	switch ($what) {
	case 'categories':
		$article = isset($_POST['article']) ? intval($_POST['article']) : 0;

		$sql = "SELECT `id`, `name`
			FROM `".TABLE_CATEGORIES."`
			WHERE `article_id` = '".$article."'
		";

		if (!$db->query($sql)) {
			$skin->assign('ERROR', $ml['L_ERROR_DB_QUERY']);
			return;
		}

		$skin->assign('RESULT', $db->getAssocArray());

		return array(
			'status' => 'success',
			'node' => array(
				'where' => '#categories',
				'inside' => true,
				'content' => $skin->fetch('ajax.html')
			)
		);
	}
}

function Setting()
{
	global $db, $skin, $ml;

	// get setting and value
	$name  = isset($_POST['name'])  ? $_POST['name']  : '';
	$value = isset($_POST['value']) ? $_POST['value'] : '';

	// get variable type if setting exists
	$sql = "SELECT `type`, `value`
		FROM `".TABLE_SETTINGS."`
		WHERE `name` = '".$db->escapeString($name)."'
		LIMIT 1";
	if (!$db->query($sql) || !$db->getCount()) {
		return array();
	}

	$setting = $db->getAssoc();

	// parse value by type
	switch($setting['type']) {
	case 'integer':
		if (!preg_match('/^[\-]*[\d]+$/', $value)) {
			return array(
				'status' => 'error',
				'message' => 'Невалидна целочислена стойност!',
				'input' => array(
					'element' => 'input[name="' . $name . '"]',
					'value' => preg_replace('/[^0-9\-]/', '', $value)
				)
			);
		}

		$value = intval($value);
		$field = $value;
		break;
	case 'string':
		// all symbols are accepted; length is to 255 chars
		if (mb_strlen($value) > 255) {
			return array(
				'status' => 'error',
				'message' => 'Стойността надвишава 255 символа!',
				'input' => array(
					'element' => 'input[name="' . $name . '"]',
					'value' => mb_substr($value, 0, 255)
				)
			);
		}

		$field = $value;
		break;
	case 'boolean':
		// true or false are possibles values
		if ($value !== 'false' && $value !== 'true') {
			return array(
				'status' => 'error',
				'message' => 'Стойността на полето не е от булев тип (true/false)!'
			);
		}

		if ($value === 'true') {
			$field = true;
			break;
		}

		$field = false;
	}

	// save new value
	$sql = "UPDATE `".TABLE_SETTINGS."`
		SET `value` = '".$db->escapeString($value)."'
		WHERE `name` = '".$db->escapeString($name)."'
		LIMIT 1";
	if (!$db->query($sql)) {
		return array(
			'status' => 'error',
			'message' => $ml['L_ERROR_DB_QUERY']
		);
	}

	return array(
		'status' => 'success',
		'input' => array(
			'element' => 'input[name="' . $name . '"]',
			'value' => $field
		)
	);
}
