<?php
if (!defined('PROGRAM') || PROGRAM !== 1) exit;

function main($id, &$title)
{
	global $db, $skin, $uri, $page;
	$skin->assign('PAGE', 'article');

	$sql = "SELECT
			`title`,
			`show_title`,
			`keywords`,
			`content`
		FROM `".TABLE_ARTICLES."`
		WHERE `id` = '".intval($id)."'
		LIMIT 1";
	if (!$db->query($sql) || !$db->getCount()) {
		$skin->assign('PAGE', 'notfound');
		return;
	}

	$article = $db->getAssoc();

	$skin->assign('KEYWORDS', trim($article['keywords'], " ,"));
	$skin->assign('CONTENT', $article['content']);

	if ($article['show_title']) {
		$title .= ' – ' . $article['title'];
	}

}
