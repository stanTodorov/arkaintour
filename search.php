<?php
if (!defined('PROGRAM') || PROGRAM !== 1) exit;

function main(&$title)
{
	global $db, $skin, $uri, $page;
	$skin->assign('PAGE', 'search');

	if (isset($_POST['q'])) {
		$_POST['q'] = preg_replace('/[\/]+/', '', $_POST['q']);
		RedirectSite(__('търсене') . '/' . urlencode($_POST['q']));
	}

	$q = isset($uri[0]) ? $uri[0] : '';

	if (mb_strlen($q) === 0) {
		$skin->assign('L_SEARCH', __('Резултати от търсенето'));
		return;
	}

	$q = preg_replace('/[^\w -_\p{L}]+/iu', '', $q);

	$search = $q;
	$skin->assign('SEARCH', $search);

	$skin->assign('L_SEARCH', sprintf(__('Резултати от търсенето на „%s“'), $q));

	// plus sign: AND instead of OR
	$q = explode(' ', $q);
	$q = implode($q, '* +');
	$q = '+' . $q;

	// search in articles
	$sql = "SELECT
			a.`url`,
			a.`title` AS 'name'
		FROM
			`".TABLE_ARTICLES."` a,
			`".TABLE_LANGUAGES."` l
		WHERE
			MATCH(`content`, `title`, `keywords`)
			AGAINST('".$db->escapeString($q)."*' IN BOOLEAN MODE)

			AND a.`lang_id` = l.`id`
			AND l.`locale` = '".$db->escapeString(CFG('locale'))."'
		LIMIT 25
	";
	$result = array();
	if ($db->query($sql) && $db->getCount()) {
		while ($row = $db->getAssoc()) {
			$row['url'] = BASE_URL . CFG('locale') . '/' . $row['url'];
			$result[] = $row;
		}
	}

	$skin->assign('RESULT', $result);

	// search in offers
	$today = mktime(0, 0, 0, intval(date("n")), 1, intval(date("Y")));
	$dateStart = date("Y-m-d H:i:s", $today);
	$dateEnd = date("Y-m-d H:i:s", strtotime("+2 year", $today));
	$offers = array();


	$count = 0;
	$sql = "SELECT
			COUNT(*) AS 'count'
		FROM (
			`".TABLE_OFFERS."` o,
			`".TABLE_CATEGORIES."` c,
			`".TABLE_ARTICLES."` a,
			`".TABLE_LANGUAGES."` l
		)

		LEFT JOIN `".TABLE_OFFERS_PERIODS."` hasOp ON
			hasOp.`offer_id` = o.`id`

		LEFT JOIN `".TABLE_OFFERS_PERIODS."` op ON
			op.`offer_id` = o.`id`
			AND op.`date` BETWEEN '".$dateStart."' AND '".$dateEnd."'

		LEFT JOIN `".TABLE_OFFERS_PICTURES."` p ON
			p.`offer_id` = o.`id`

		WHERE
			c.`id` = o.`category_id`
			AND c.`article_id` = a.`id`
			AND MATCH(o.`name`, o.`route`, o.`content`, o.`transport`, o.`price`, c.`title`)
			    AGAINST('".$db->EscapeString($q)."* ' IN BOOLEAN MODE)
			AND (
				hasOp.`id` IS NULL
				OR op.`id` IS NOT NULL
			)
			AND a.`lang_id` = l.`id`
			AND l.`locale` = '".$db->escapeString(CFG('locale'))."'
		GROUP BY o.`id`
		ORDER BY o.`vip_offer` DESC, a.`order` ASC
	";
	if (!$db->query($sql) || !($count = $db->getCount())) {
		return;
	}

	unset($_GET['locale']);
	unset($_GET['page']);
	$url = BASE_URL . $page . '/' . $search . '/';
	$paging = new Paging($count, CFG('paging.count.search'), $url, "pg", true, true, true);
	$paging->grouping = CFG('paging.groups');
	$skin->assign('PAGING', $paging->ShowNavigation());
	$skin->assign('COUNT', $count);

	$sql = "SELECT
			o.`id`,
			o.`name`,
			SUBSTR(o.`content`, 1, 320) AS 'content',
			o.`vip_offer` AS 'vip_offer',
			p.`filename`,
			a.`url` AS 'article',
			c.`id` AS 'category'
		FROM (
			`".TABLE_OFFERS."` o,
			`".TABLE_CATEGORIES."` c,
			`".TABLE_ARTICLES."` a,
			`".TABLE_LANGUAGES."` l
		)

		LEFT JOIN `".TABLE_OFFERS_PERIODS."` hasOp ON
			hasOp.`offer_id` = o.`id`

		LEFT JOIN `".TABLE_OFFERS_PERIODS."` op ON
			op.`offer_id` = o.`id`
			AND op.`date` BETWEEN '".$dateStart."' AND '".$dateEnd."'

		LEFT JOIN `".TABLE_OFFERS_PICTURES."` p ON
			p.`offer_id` = o.`id`

		WHERE
			c.`id` = o.`category_id`
			AND c.`article_id` = a.`id`
			AND MATCH(o.`name`, o.`route`, o.`content`, o.`transport`, o.`price`, c.`title`)
			    AGAINST('".$db->EscapeString($q)."* ' IN BOOLEAN MODE)
			AND (
				hasOp.`id` IS NULL
				OR op.`id` IS NOT NULL
			)
			AND a.`lang_id` = l.`id`
			AND l.`locale` = '".$db->escapeString(CFG('locale'))."'
		GROUP BY o.`id`
		ORDER BY
			o.`vip_offer` DESC,
			a.`order` ASC
	".$paging->GetMysqlLimits();
	if ($db->query($sql) && $db->getCount()) {
		$resizes = GetOffersDirs();

		while ($row = $db->getAssoc()) {
			if ($row['filename']) {
				$row['image'] = $resizes['small']['url'] . $row['filename'];
			}
			$row['content'] = str_replace("&nbsp;", ' ', $row['content']);
			$row['content'] = trim(strip_tags(html_entity_decode($row['content'])));
			$row['content'] = ShortText($row['content'], CFG('short.text'), true, true);
			$row['url'] = BASE_URL . CFG('locale') . '/' . $row['article'] . '/' . $row['category'] . '/' . $row['id'];
			$offers[] = $row;
		}
	}

	$skin->assign('OFFERS', $offers);
}
