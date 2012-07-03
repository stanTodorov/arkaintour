<?php
if (!defined('PROGRAM') || PROGRAM !== 1) exit;

require_once(BASE_PATH.'libraries/securimage/securimage.php');

function main($id, &$title)
{
	global $db, $skin, $ml, $page;

	$skin->assign('PAGE', 'contacts');

	$sql = "SELECT
			`title`,
			`show_title`,
			`keywords`,
			`content`,
			`url`
		FROM `".TABLE_ARTICLES."`
		WHERE `id` = '".intval($id)."'
		LIMIT 1";
	if (!$db->query($sql) || !$db->getCount()) {
		$skin->assign('PAGE', 'notfound');
		return;
	}

	$article = $db->getAssoc();

	$skin->assign('META', array('keywords' => $article['keywords']));
	$skin->assign('CONTENT', $article['content']);

	if ($article['show_title']) {
		$title .= ' – ' . $article['title'];
	}

	$skin->assign('ARTICLE', $article);

	if (!isset($_POST['submit'])) return;

	$securimage = new Securimage();
	$securimage->session_name = session_name();

	$errors = FormValidate($_POST, $data, array(
		'name'    => array(
			'min' => 2,
			'max' => 32,
			'req' => true
		),
		'email'   => array(
			'min' => 3,
			'max' => 255,
			'req' => true,
			'is' => 'email'
		),
		'message' => array(
			'min' => 2,
			'max' => 32768,
			'req' => true
		),
		'captcha' => array(
			'min' => 1,
			'max' => 10,
			'req' => true
		)
	));

	if (IsCSRF()) {
		$skin->assign('ERROR', $ml['L_ERROR_BAD_ID']);
		$skin->assign('RESULT', $data);
		return;
	}

	if (!isset($errors['captcha']) &&  !$securimage->check($data['captcha'])) {
		$errors['captcha'] = __('Въведеният код е невалиден!');
	}

	if (count($errors) > 0) {
		$skin->assign('ERROR', $ml['L_ERROR_INPUT']);
		$skin->assign('ERRORS', $errors);
		$skin->assign('RESULT', $data);
		return;
	}

	// mail body
	$message = strip_tags($data['message']);

	// mail subject
	$subject = str_replace(array("\n", "\r", "\t"), ' ', $message);
	$subject = mb_substr($subject, 0, 64);
	$subject = ShortText($subject, 48, true, true);

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
		$mail->IsHTML(false);

		$mail->From       = $data['email'];
		$mail->FromName   = $data['name'];
		$mail->Subject    = $subject;
		$mail->Body       = $message;
		$mail->AddReplyTo($data['email'], $data['name']);
		$mail->AddAddress(CFG('mail.contact'), CFG('mail.contact.name'));

		// send mail
		$mail->Send();

		MsgPush('success', __('Благодарим Ви!'));
		RedirectSite($page);

	} catch (phpmailerException $e) {
		$skin->assign('ERROR', $ml['L_ERROR_SEND_MAIL']);
		$skin->assign('RESULT', $data);

		// save to log file
		ErrorLog($e->errorMessage(), 'contacts.log');
	} catch (Exception $e) {
		$skin->assign('ERROR', $ml['L_ERROR_SEND_MAIL']);
		$skin->assign('RESULT', $data);
	}
}
