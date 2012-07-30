<?php
if (!defined('PROGRAM') || PROGRAM !== 1) exit;

function main($id, &$title)
{
	global $db, $skin, $uri;
	$skin->assign('PAGE', 'home');

	$sql = "SELECT
			`title`,
			`show_title`,
			`url`,
			`keywords`,
			`script_id`,
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

	if ($article['show_title']) {
		$title .= ' – ' . $article['title'];
	}

	$content = $article['content'];
	$content .= GetTopOffers();

	$skin->assign('CONTENT', $content);
}


function GetTopOffers()
{
	global $db, $skin, $uri, $page;


	$count = 0;
	$sql = "SELECT
			COUNT(*) AS 'count'
		FROM (
			`".TABLE_OFFERS."` o,
			`".TABLE_CATEGORIES."` c,
			`".TABLE_ARTICLES."` a,
			`".TABLE_LANGUAGES."` l
		)
		LEFT JOIN `".TABLE_OFFERS_PICTURES."` p ON
			p.`offer_id` = o.`id`
		WHERE
			o.`vip_offer` = '1'
			AND c.`id` = o.`category_id`
			AND a.`id` = c.`article_id`
			AND o.`lang_id` = l.`id`
			AND l.`locale` = '".$db->escapeString(CFG('locale'))."'
		GROUP BY o.`id`";
	if (!$db->query($sql) || !($count = $db->getCount())) {
		return;
	}

	unset($_GET['locale']);
	unset($_GET['page']);
	$url = BASE_URL . CFG('locale') . '/';
	$paging = new Paging($count, CFG('paging.count.vip'), $url, "pg", true, true, true);
	$paging->grouping = CFG('paging.groups');
	$skin->assign('PAGING', $paging->ShowNavigation());
	$skin->assign('COUNT', $count);

	$sql = "SELECT
			c.`id` AS 'category',
			a.`url`,
			o.`id`,
			o.`name`,
			SUBSTR(o.`content`, 1, 255) AS 'content',
			o.`price`,
			p.`filename`
		FROM (
			`".TABLE_OFFERS."` o,
			`".TABLE_CATEGORIES."` c,
			`".TABLE_ARTICLES."` a,
			`".TABLE_LANGUAGES."` l
		)
		LEFT JOIN `".TABLE_OFFERS_PICTURES."` p ON
			p.`offer_id` = o.`id`
		WHERE
			o.`vip_offer` = '1'
			AND c.`id` = o.`category_id`
			AND a.`id` = c.`article_id`
			AND o.`lang_id` = l.`id`
			AND l.`locale` = '".$db->escapeString(CFG('locale'))."'
		GROUP BY o.`id`
		ORDER BY o.`added` DESC
	".$paging->GetMysqlLimits();
	if (!$db->query($sql) || !$db->getCount()) {
		return '';
	}

	$resizes = GetOffersDirs();

	$result = array();
	while ($row = $db->getAssoc()) {
		if ($row['filename']) {
			$row['image'] = $resizes['small']['url'] . $row['filename'];
		}
		$row['url'] = BASE_URL . CFG('locale') . '/' . $row['url'] . '/' . $row['category'] . '/' . $row['id'];
		$row['content'] = ShortText($row['content'], CFG('short.text'), true, true);

		$result[] = $row;
	}

	$skin->assign('RESULT', $result);
}
