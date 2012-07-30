<?php
if (!defined('PROGRAM') && PROGRAM !== 1) exit;

function Main()
{
	global $skin, $title;
	$skin->assign('PAGE', 'icons');
	$title .= ' – Страници';

	$action = isset($_GET['action']) ? $_GET['action'] : '';
	switch($action) {
	case 'add':
		AddIcons();
		break;
	case 'remove':
		DeleteIcons();
		break;
	case 'list':
	default:
		ListIcons();
	}
}

function AddIcons()
{
	global $db, $skin, $user, $ml, $page;
	$skin->assign('ACTION', 'add');

	$skin->assign('MAX_UPLOAD_SIZE', GetMaxUploadLimits());

	if (!isset($_POST['submit'])) return;

	// Move to gallery and resize all uploaded pictures
	if (!isset($_FILES['pictures']) || !count($_FILES['pictures'])) {
		MsgPush('error', 'Възникна грешка при качване на иконите!');
		RedirectSite('admin/?page=' . $page);
	}

	$basePath = BASE_PATH . CFG('upload.dir.icons') . DS;
	$pictures = array();
	$picturesCount = 0;

	CreateDirPath($basePath);

	foreach ($_FILES['pictures']['error'] as $file => $error) {
		if ($error !== UPLOAD_ERR_OK) {
			continue;
		}

		$picturesCount++;

		$uploaded = $_FILES['pictures']['tmp_name'][$file];
		$name = $_FILES['pictures']['name'][$file];
		$filename = GetValidFilename($basePath, $name);
		$filename = $filename['filename'];

		try {
			if (!@move_uploaded_file($uploaded, $basePath . $filename)) {
				throw new Exception("Can't move uploaded file!");
			}

			$img = new ImageResize($basePath . $filename);

			list($width, $height) = $img->getDimensions();

			if ($width !== 32 || $height !== 32) {
				throw new Exception('The icon is not 32x32!');
			}

			$pictures[] = $filename;

		} catch(Exception $e) {
			@unlink($basePath . $filename);
			@unlink($uploaded);
		}
	}

	if ($picturesCount && !count($pictures)) {
		if (count($pictures)) {
			// delete uploaded images
			foreach ($pictures as $file) {
				@unlink($basePath . $file);
				DeleteEmptyTree($basePath);
			}
		}

		MsgPush('error', 'Възникна грешка при качване на иконите!');
		RedirectSite('admin/?page=' . $page);
	}
	else if (!$picturesCount) {
		MsgPush('error', 'Възникна грешка при качване на иконите!');
		RedirectSite('admin/?page=' . $page);
	}

	$values = '';

	foreach ($pictures as $file) {
		$values .= "(NULL, '";
		$values .= $db->escapeString($file) . "', ";
		$values .= 'NOW()), ';
	}

	$values = rtrim($values, ", ");

	$sql = "INSERT INTO `".TABLE_ICONS."` (
			`id`,
			`filename`,
			`added`
		) VALUES ".$values;
	if (!$db->query($sql)) {
		if (count($pictures)) {
			// delete uploaded images
			foreach ($pictures as $file) {
				@unlink($basePath . $file);
				DeleteEmptyTree($basePath);
			}
		}

		MsgPush('error', 'Възникна грешка при качване на иконите!');
		RedirectSite('admin/?page=' . $page);
	}

	MsgPush('success', 'Успешно добавени икони!');
	RedirectSite('admin/?page='.$page);

}

function DeleteIcons()
{
	global $db, $skin, $user, $ml, $page;

	$basePath = BASE_PATH . CFG('upload.dir.icons') . DS;

	$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
	if ($id <= 0) {
		MsgPush('error', 'Няма намерени резултати!');
		RedirectSite('admin/?page=' . $page);
	}

	// delete from disk
	$sql = "SELECT `filename`
		FROM `".TABLE_ICONS."`
		WHERE `id` = '".$id."'
		LIMIT 1";
	if (!$db->query($sql) || !$db->getCount()) {
		MsgPush('error', 'Няма намерени резултати!');
		RedirectSite('admin/?page=' . $page);
	}

	$result = $db->getAssoc();


	// delete from disk
	@unlink($basePath . $result['filename']);

	// delete from DB
	$sql = "DELETE FROM `".TABLE_ICONS."`
		WHERE `id` = '".$id."'
		LIMIT 1";
	$db->query($sql);

	MsgPush('success', 'Успешно изтрита снимка!');
	RedirectSite('admin/?page=' . $page);
}

function ListIcons()
{
	global $db, $skin, $user, $ml, $page;
	$skin->assign('ACTION', 'list');

	$url = BASE_URL.CFG('upload.dir.icons').DS;

	// get album's pictures
	$sql = "SELECT
			`id`,
			`filename`,
			UNIX_TIMESTAMP(`added`) AS 'date'
		FROM `".TABLE_ICONS."`
		ORDER BY `added` DESC
	";
	if (!$db->query($sql) || !$db->getCount()) {
		return;
	}

	$result = array();
	while ($row = $db->getAssoc()) {
		$row['image'] = $url . $row['filename'];
		$row['date'] = date("d.m.Y H:i", $row['date']);

		$result[] = array(
			'id' => $row['id'],
			'image' => $row['image'],
			'date' => $row['date']
		);
	}

	$skin->assign('RESULT', $result);
	$skin->assign('COUNT', count($result));
}
