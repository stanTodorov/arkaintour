<?php
if (!defined('PROGRAM') || PROGRAM !== 1) exit;

function main(&$title)
{
	global $db, $skin, $uri, $ml;
	$skin->assign('PAGE', 'sitemap');

	// get all articles
	$sql = "SELECT
			a.`id`,
			a.`url`,
			a.`title` AS 'name'
		FROM
			`".TABLE_ARTICLES."` a,
			`".TABLE_LANGUAGES."` l
		WHERE
			a.`lang_id` = l.`id`
			AND l.`locale` = '".$db->escapeString(CFG('locale'))."'
		ORDER BY
			a.`default` DESC,
			a.`order` ASC,
			a.`title` ASC
		";
	if (!$db->query($sql) || !$db->getCount()) {
		$skin->assign('ERROR', $ml['L_ERROR_DB_QUERY']);
		return;
	}

	$articles = array();
	$result = array();
	while ($row = $db->getAssoc()) {
		$row['url'] = BASE_URL . CFG('locale') . '/' . $row['url'];
		$result[$row['id']] = $row;
		$articles = $row['id'];
	}

	// get all categories
	$sql = "SELECT
			c.`id`,
			c.`article_id` AS 'aid',
			c.`title` AS 'name'
		FROM
			`".TABLE_CATEGORIES."` c,
			`".TABLE_ARTICLES."` a,
			`".TABLE_LANGUAGES."` l
		WHERE
			a.`lang_id` = l.`id`
			AND l.`locale` = '".$db->escapeString(CFG('locale'))."'
			AND c.`article_id` = a.`id`
		ORDER BY c.`title` ASC";
	if ($db->query($sql) && $db->getCount()) {
		while ($row = $db->getAssoc()) {
			$row['url'] = $result[$row['aid']]['url'] . '/' . $row['id'] . '/';
			$result[$row['aid']]['categories'][] = $row;
		}
	}

	$result = array_values($result);

	$skin->assign("RESULT", $result);
}
