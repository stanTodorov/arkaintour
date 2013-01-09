<?php
if (!defined('PROGRAM') || PROGRAM !== 1) exit;

function Main()
{
	global $skin, $title, $user, $db, $ml;
	$skin->assign('PAGE', 'profile');

	$title .= ' – Профил';

	$sql = "SELECT
			`name`,
			`username`,
			`salt`,
			`password`
		FROM `".TABLE_USERS."`
		WHERE `id` = '".$user['id']."'
		LIMIT 1";
	if (!$db->query($sql) || !$db->getCount()) {
		$skin->assign('RESULT', $ml['L_ERROR_DB_QUERY']);
		return;
	}

	$result = $db->getAssoc();
	$skin->assign('RESULT', array(
		'name' => $result['name'],
		'username' => $result['username'],

	));

	if (!isset($_POST['submit'])) return;

	$errors = FormValidate($_POST, $data, array(
		'password' => array('min' => 4, 'max' => 256, 'req' => true),
		'password2' => array('min' => 4, 'max' => 256, 'req' => true),
		'oldpassword' => array('min' => 4, 'max' => 256, 'req' => true)
	));

	$oldpassword = SaltPassword($data['oldpassword'], $result['salt'], CFG('login.salt'));

	if (!isset($errors['oldpassword']) && $result['password'] !== $oldpassword) {
		$errors['oldpassword'] = 'Текущата парола е грешна!';
	}

	if ( !isset($errors['oldpassword'])
	     && ( $data['password'] === $data['oldpassword']
	          || $data['password'] == $result['username']
		)
	) {
		$errors['password'] = 'Изберете друга парола!';
	}

	if ($data['password'] !== $data['password2']) {
		$errors['password2'] = 'Паролата не съвпада!';
	}

	if (count($errors) !== 0) {
		$skin->assign('ERROR', $ml['L_ERROR_INPUT']);
		$skin->assign('ERRORS', $errors);
		return;
	}

	$salt = GenSalt(CFG('login.saltsize'));
	$newpassword = SaltPassword($data['password'], $salt, CFG('login.salt'));

	$sql = "UPDATE `".TABLE_USERS."` SET
			`password` = '".$db->EscapeString($newpassword)."',
			`salt` = '".$db->EscapeString($salt)."',
			`session_id` = ''
		WHERE
			`id` = '".$user['id']."'";
	if (!$db->Query($sql) || !$db->GetCount()) {
		$skin->assign('ERROR', $ml['L_ERROR_DB_QUERY']);
		return;
	}

	$skin->assign('SUCCESS', 'Успешно сменена парола!');
}
