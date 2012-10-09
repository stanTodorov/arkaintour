<?php
if (!defined('PROGRAM') || PROGRAM !== 1) exit;

function main($id, &$title)
{
	global $db, $skin, $uri;
	$skin->assign('PAGE', 'article');
	$id = (int) $id;

	$baseURL = BASE_URL . CFG('upload.dir.icons') . DS;

	$sql = "SELECT
			`title`,
			`show_title`,
			`url`,
			`keywords`,
			`script_id`,
			`content`
		FROM `".TABLE_ARTICLES."`
		WHERE `id` = '".$id."'
		LIMIT 1";
	if (!$db->query($sql) || !$db->getCount()) {
		$skin->assign('PAGE', 'notfound');
		return;
	}

	$article = $db->getAssoc();
	$skin->assign('SECTION', $article['title']);
	$skin->assign('KEYWORDS', trim($article['keywords'], " ,"));

	if ($article['show_title']) {
		$title .= ' – ' . $article['title'];
	}

	// get subcategories
	$sql = "SELECT
			c.`id`,
			c.`title`,
			i.`filename`
		FROM `".TABLE_CATEGORIES."` c
		LEFT JOIN `".TABLE_ICONS."` i ON
			i.`id` = c.`icon_id`
		WHERE c.`article_id` = '".$id."'
		ORDER BY c.`order` ASC";
	if ($db->query($sql) && $db->getCount()) {
		$sidebar = array();
		while ($row = $db->getAssoc()) {
			$row['url'] = BASE_URL . CFG('locale') . '/' . $article['url'] . '/' . $row['id'];

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

		$skin->assign('SIDEBAR', $sidebar);
	}

	// new year banners
	if (isset($uri[0]) && ((int) $uri[0]) === 31) {
		$skin->assign('NEW_YEAR', true);
	}

	// get offer
	if (count($uri) > 1 && intval($uri[1]) > 0) {
		$title .= ' – ' . ShowOffer($id, $uri[1]);
		return;
	}

	$category = isset($uri[0]) ? (int) $uri[0] : 0;

	if ($heading = ShowOffers($id, $category, $article['url'])) {
		$title .= ' – ' . $heading;
	}

	$skin->assign('CONTENT', $article['content']);
}


function ShowOffers($articleId, $category = 0, $url = '')
{
	global $db, $skin, $page;

	$articleId = (int) $articleId;
	$category = (int) $category;

	$resizes = GetOffersDirs();

	$skin->assign('OFFERS', true);

	if (!$category) {
		$count = 0;
		$sql = "SELECT COUNT(*) AS 'count'
			FROM (
				`".TABLE_OFFERS."` o,
				`".TABLE_CATEGORIES."` c,
				`".TABLE_ARTICLES."` a,
				`".TABLE_LANGUAGES."` l
			)
			LEFT JOIN `".TABLE_OFFERS_PICTURES."` p ON
				p.`offer_id` = o.`id`
			WHERE
				c.`id` = o.`category_id`
				AND a.`id` = c.`article_id`
				AND a.`id` = '".$articleId."'
				AND o.`lang_id` = l.`id`
				AND l.`locale` = '".$db->escapeString(CFG('locale'))."'
			GROUP BY o.`id`
			ORDER BY o.`vip_offer` DESC, o.`added` ASC";
		if (!$db->query($sql) || !($count = $db->getCount())) {
			return;
		}

		unset($_GET['locale']);
		unset($_GET['page']);
		$url = BASE_URL . CFG('locale') . '/' . $page . '/';
		$paging = new Paging($count, CFG('paging.count.categories'), $url, "pg", true, true, true);
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
				c.`id` = o.`category_id`
				AND a.`id` = c.`article_id`
				AND a.`id` = '".$articleId."'
				AND o.`lang_id` = l.`id`
				AND l.`locale` = '".$db->escapeString(CFG('locale'))."'
			GROUP BY o.`id`
			ORDER BY o.`vip_offer` DESC, o.`added` ASC
		".$paging->GetMysqlLimits();
		if (!$db->query($sql) || !$db->getCount()) {
			return '';
		}

		while ($row = $db->getAssoc()) {
			if ($row['filename']) {
				$row['image'] = $resizes['small']['url'] . $row['filename'];
			}
			$row['content'] = ShortText($row['content'], CFG('short.text'), true, true);
			$row['url'] = BASE_URL . CFG('locale') . '/' . $row['url'] . '/' . $row['category'] . '/' . $row['id'];
			$result[] = $row;
		}
		$skin->assign('RESULT', $result);
		return '';
	}

	$count = 0;
	$sql = "SELECT COUNT(*) AS 'count'
		FROM (
			`".TABLE_OFFERS."` o,
			`".TABLE_CATEGORIES."` c,
			`".TABLE_ARTICLES."` a,
			`".TABLE_LANGUAGES."` l
		)
		LEFT JOIN `".TABLE_OFFERS_PICTURES."` p ON
			p.`offer_id` = o.`id`
		WHERE
			c.`id` = o.`category_id`
			AND c.`id` = '".$category."'
			AND a.`id` = c.`article_id`
			AND a.`id` = '".$articleId."'
			AND o.`lang_id` = l.`id`
			AND l.`locale` = '".$db->escapeString(CFG('locale'))."'

		GROUP BY o.`id`";
	if (!$db->query($sql) || !($count = $db->getCount())) {
		return;
	}

	unset($_GET['locale']);
	unset($_GET['page']);
	$url = BASE_URL . CFG('locale') . '/' . $page . '/' . $category . '/' ;
	$paging = new Paging($count, CFG('paging.count.categories'), $url, "pg", true, true, true);
	$paging->grouping = CFG('paging.groups');
	$skin->assign('PAGING', $paging->ShowNavigation());
	$skin->assign('COUNT', $count);

	$sql = "SELECT
			c.`id` AS 'category',
			a.`url`,
			c.`title`,
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
			c.`id` = o.`category_id`
			AND c.`id` = '".$category."'
			AND a.`id` = c.`article_id`
			AND a.`id` = '".$articleId."'
			AND o.`lang_id` = l.`id`
			AND l.`locale` = '".$db->escapeString(CFG('locale'))."'

		GROUP BY o.`id`
		ORDER BY o.`vip_offer` DESC, o.`added` ASC
	".$paging->GetMysqlLimits();

	if (!$db->query($sql) || !$db->getCount()) {
		return '';
	}

	$result = array();
	$title = '';
	while ($row = $db->getAssoc()) {
		$title = $row['title'];
		if ($row['filename']) {
			$row['image'] = $resizes['small']['url'] . $row['filename'];
		}
		$row['content'] = ShortText($row['content'], CFG('short.text'), true, true);

		$row['url'] = BASE_URL . CFG('locale') . '/' . $row['url'] . '/' . $row['category'] . '/' . $row['id'];

		$result[] = $row;
	}

	$skin->assign('RESULT', $result);
	$skin->assign('SECTION', $title);
	return $title;
}

function ShowOffer($articleId, $offerId)
{
	global $db, $skin, $ml;

	// get offer content
	$sql = "SELECT
			a.`url`,
			c.`id` AS 'cat_id',
			c.`title` AS 'category',
			o.`name`,
			o.`content`,
			o.`route`,
			o.`price`,
			o.`vip_offer`,
			o.`duration`,
			o.`transport`
		FROM
			`".TABLE_OFFERS."` o,
			`".TABLE_ARTICLES."` a,
			`".TABLE_CATEGORIES."` c
		WHERE
			o.`id` = '".intval($offerId)."'
			AND c.`id` = o.`category_id`
			AND c.`article_id` = a.`id`
			AND a.`id` = '".intval($articleId)."'
		LIMIT 1";
	if (!$db->query($sql) || !$db->getCount()) {
		return;
	}

	$result = $db->getAssoc();
	$result['url'] = BASE_URL . CFG('locale') . '/' . $result['url'] . '/' . $result['cat_id'] . '/' . $offerId;

	// get periods of offer (if exists)
	$sql = "SELECT UNIX_TIMESTAMP(`date`) AS 'date'
		FROM `".TABLE_OFFERS_PERIODS."`
		WHERE `offer_id` = '".intval($offerId)."'";
	if ($db->query($sql) && $db->getCount()) {
		$result['dates'] = array();

		while ($row = $db->getAssoc()) {
			$result['dates'][] = date($ml['L_DATETIME_DATE_S'], $row['date']);
		}
	}

	// get images
	$sql = "SELECT `filename`
		FROM `".TABLE_OFFERS_PICTURES."`
		WHERE `offer_id` = '".intval($offerId)."'";
	if ($db->query($sql) && $db->getCount()) {
		$resizes = GetOffersDirs();

		$result['pictures'] = array();

		while ($row = $db->getAssoc()) {
			$result['pictures'][] = array(
				'image' => $resizes['normal']['url'] . $row['filename'],
				'thumb' => $resizes['small']['url'] . $row['filename']
			);
		}
	}

	// get attachments
	$sql = "SELECT `filename`, `size`
		FROM `".TABLE_OFFERS_FILES."`
		WHERE `offer_id` = '".intval($offerId)."'";
	if ($db->query($sql) && $db->getCount()) {
		$url = BASE_URL . CFG('upload.dir.attachments') . '/';

		$result['attachments'] = array();

		while ($row = $db->getAssoc()) {
			$result['attachments'][] = array(
				'filename' => $row['filename'],
				'download' => $url . $row['filename'],
				'size' => HumanSize($row['size'], 2, true)
			);
		}
	}

	$skin->assign('OFFER', $result);

	QueryForm($result);
	SendToFriend($result);

	return $result['name'];
}

function QueryForm($offer)
{
	global $db, $skin, $ml, $uri, $page;

	$URI = $page . '/';

	if (is_array($uri)) {
		$URI .= implode('/', $uri);
	} else if (is_string($uri)) {
		$URI .= $url;
	}

	$skin->assign('URI', $URI);

	if (!isset($_POST['query'])) return;

	$skin->assign('FORM', array('query' => true));

	$errors = FormValidate($_POST, $data, array(
		'name'    => array(
			'min' => 1,
			'max' => 64,
			'req' => true
		),
		'email' => array(
			'min' => 3,
			'max' => 255,
			'req' => true,
			'is' => 'email'
		),
		'phone' => array(
			'min' => 3,
			'max' => 255,
			'req' => false
		),
		'message' => array(
			'min' => 1,
			'max' => 32768,
			'req' => true
		)
	));

	if (IsCSRF()) {
		$skin->assign("ERROR", $ml['L_ERROR_BAD_ID']);
		$skin->assign('RESULT', $data);
		return;
	}

	if (count($errors) > 0) {
		$skin->assign('ERRORS', $errors);
		$skin->assign('ERROR', $ml['L_ERROR_INPUT']);
		$skin->assign('RESULT', $data);
		return;
	}

	$link = BASE_URL . $URI;

	$data['message'] = nl2br($data['message']);

	$offerName = $offer['name'];
	if ($offer['top_offer']) {
		$offerName = '(ВИП Оферта) ' . $offerName;
	}


	$message = <<<HTML
<table>
<tr><th style="text-align: left; padding: 2px 10px; vertical-align: top">Оферта:</th><td>       {$offerName}</td></tr>
<tr><th style="text-align: left; padding: 2px 10px; vertical-align: top">Адрес:</th><td>        <a href="{$link}">{$link}</a></td></tr>
<tr><th style="text-align: left; padding: 2px 10px; vertical-align: top">Телефон:</th><td>      {$data['phone']}</td></tr>
<tr><th style="text-align: left; padding: 2px 10px; vertical-align: top">Запитване:</th>
<td>{$data['message']}</td></tr>
</table>
HTML;

	$subject = ShortText("Запитване за оферта: " . $offerName, 74, true, true);

	// setup phpMailer
	$mail = new PHPMailer(true);

	try {
		$mail->IsSMTP();
		$mail->SMTPDebug  = 0;
		$mail->Host       = CFG('smtp.hostname');
		$mail->Port       = CFG('smtp.hostport');
		$mail->SMTPSecure = CFG('smtp.authtype');
		$mail->SMTPAuth   = CFG('smtp.is_auth' );
		$mail->Username   = CFG('smtp.username');
		$mail->Password   = CFG('smtp.password');
		$mail->CharSet    = CFG('smtp.codepage');
		$mail->SetLanguage(CFG('language'));
		$mail->IsHTML(true);

		$mail->From       = $data['email'];
		$mail->FromName   = $data['name'];
		$mail->Subject    = $subject;
		$mail->Body       = $message;
		$mail->AltBody    = strip_tags($message);

		$mail->AddReplyTo($data['email'], $data['name']);
		$mail->AddAddress(CFG('mail.offers'), CFG('mail.offers.name'));

		// send mail
		$mail->Send();

		MsgPush('success', __('Благодарим Ви!'));
		RedirectSite($URI);
	} catch (phpmailerException $e) {
		$skin->assign('ERROR', $ml['L_ERROR_SEND_MAIL']);
		$skin->assign('RESULT', $data);

		// save to log file
		ErrorLog($e->errorMessage(), 'offers.log');
	} catch (Exception $e) {
		$skin->assign('ERROR', $ml['L_ERROR_SEND_MAIL']);
		$skin->assign('RESULT', $data);
	}
}

function SendToFriend($offer)
{
	global $db, $skin, $ml, $uri, $page;

	$URI = $page . '/';

	if (is_array($uri)) {
		$URI .= implode('/', $uri);
	} else if (is_string($uri)) {
		$URI .= $uri;
	}

	$skin->assign('URI', $URI);

	if (!isset($_POST['send2friend'])) return;

	$skin->assign('FORM', array('send2friend' => true));

	$errors = FormValidate($_POST, $data, array(
		'fromName'    => array(
			'min' => 1,
			'max' => 64,
			'req' => true
		),
		'fromEmail' => array(
			'min' => 3,
			'max' => 255,
			'req' => true,
			'is' => 'email'
		),
		'toEmail' => array(
			'min' => 3,
			'max' => 255,
			'req' => true,
			'is' => 'email'
		),
	));

	if (IsCSRF()) {
		$skin->assign("ERROR", $ml['L_ERROR_BAD_ID']);
		$skin->assign('RESULT', $data);
		return;
	}

	if (count($errors) > 0) {
		$skin->assign('ERRORS', $errors);
		$skin->assign('ERROR', $ml['L_ERROR_INPUT']);
		$skin->assign('RESULT', $data);
		return;
	}

	$link = BASE_URL . $URI;

	$subject = ShortText(__("Аркаин Тур: ") . $offer['name'], 74, true, true);

	$dates = '';
	if (isset($offer['dates']) && count($offer['dates'])) {
		$dates .= '<div><h3>' . __('Дати на отпътуване') . '</h3>';
		$dates .= '<ul>';

		foreach ($offer['dates'] as $date) {
			$dates .= '<li>' . $date . '</li>';
		}

		$dates .= '</ul>';
		$dates .= '</div>';
	}

	$url = BASE_URL;
	$lang = CFG('language');

	$lables = array();
	$lables['sender'] = sprintf(__('%s Ви изпрати оферта:'), $data['fromName']);
	$lables['company'] = __('Аркаин Тур');
	$lables['moreinfo'] = __('Повече информация');
	$lables['more'] = __('Подробности');
	$lables['offer'] = __('Оферта:');

	$message = <<<HTML
<!DOCTYPE html>
<html lang="{$lang}">
<head>
	<meta charset="utf-8" />
	<title>{$offer['name']}</title>
</head>
<body>
{$lables['sender']}<br /><br />
<table style="width: 100%">
<tr><td>
<h1><a href="{$url}">{$lables['company']}</a>: {$offer['name']} (<a href="{$link}">{$lables['more']}</a>)</h1>
<h2>{$offer['slogan']}</h2>
<h3>{$offer['price']}</h3>
</td><td>
<div style="margin: 10px auto; text-align: center">
	<a href="{$offer['image']}"><img src="{$offer['thumb']}" alt="" /></a>
</div>
</td></tr>
<tr><td colspan="2">{$dates}</td></tr>
</table>

<hr />

{$offer['content']}

<hr />
<p>{$lables['offer']} <a href="{$link}">{$lables['moreinfo']}</a><br />
<a href="{$url}">{$lables['company']}</a><br />
</p>
</body>
</html>

HTML;

	// setup phpMailer
	$mail = new PHPMailer(true);

	try {
		$mail->IsSMTP();
		$mail->SMTPDebug  = 0;
		$mail->Host       = CFG('smtp.hostname');
		$mail->Port       = CFG('smtp.hostport');
		$mail->SMTPSecure = CFG('smtp.authtype');
		$mail->SMTPAuth   = CFG('smtp.is_auth' );
		$mail->Username   = CFG('smtp.username');
		$mail->Password   = CFG('smtp.password');
		$mail->CharSet    = CFG('smtp.codepage');
		$mail->SetLanguage(CFG('language'));
		$mail->IsHTML(true);

		$mail->From       = $data['fromEmail'];
		$mail->FromName   = $data['fromName'];
		$mail->Subject    = $subject;
		$mail->Body       = $message;
		$mail->AltBody    = __('Превключете на HTML версията на писмото!');

		$mail->AddReplyTo($data['fromEmail'], $data['fromName']);
		$mail->AddAddress($data['toEmail']);

		// send mail
		$mail->Send();

		MsgPush('success', __('Благодарим Ви!'));
		RedirectSite($URI);
	} catch (phpmailerException $e) {
		$skin->assign('ERROR', $ml['L_ERROR_SEND_MAIL']);
		$skin->assign('RESULT', $data);

		// save to log file
		ErrorLog($e->errorMessage(), 'send2friends.log');
	} catch (Exception $e) {
		$skin->assign('ERROR', $ml['L_ERROR_SEND_MAIL']);
		$skin->assign('RESULT', $data);
	}
}