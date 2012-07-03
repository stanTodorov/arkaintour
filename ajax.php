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
	case 'more-offers':
		$result = MoreOffers();
		break;
	}


	if (!is_array($result) || !count($result)) {
		echo json_encode($default);
		exit;
	}

	echo json_encode(array_merge($default, $result));
	exit;
}

function MoreOffers()
{
	global $db, $skin, $ml;
	$skin->assign('ACTION', 'options');

	$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
	if ($id <= 0) return array();

	// get up to 50 rows of this offer
	$sql = "SELECT
			o.`id`,
			o.`name`,
			o.`price`,
			a.`url` AS 'article'
		FROM
			`".TABLE_OFFERS."` o,
			`".TABLE_CATEGORIES."` c,
			`".TABLE_ARTICLES."` a
		WHERE
			c.`id` = o.`category_id`
			AND c.`article_id` = a.`id`
			AND c.`id` = '".intval($id)."'
			AND o.`top_offer` = '0'
		GROUP BY o.`id`
		ORDER BY
			o.`top_offer` DESC,
			o.`added` DESC,
			c.`name` ASC,
			o.`name` ASC
		LIMIT 50";
	if (!$db->query($sql) || !$db->getCount()) {
		return array();
	}

	$content = '';
	while ($row = $db->getAssoc()) {
		$row['url'] = BASE_URL . $row['article'] . '/' . $row['id'];
		$content .= '<li>';
		$content .= '<a href="'.$row['url'].'">'.$row['name'].', ';
		$content .= '<strong>'.$row['price'].'</strong></a></li>';
	}

	return array(
		'status' => 'success',
		'node' => array(
			'where' => '#offer-list-' . $id,
			'inside' => true,
			'content' => $content,
			'remove' => '#more-offers-' . $id
		),

	);
}
