<?php
if (!defined('PROGRAM') || PROGRAM !== 1) exit;

$cfg = array();

// common
$cfg['debug'] = true;
$cfg['cache.version'] = '1';
$cfg['copyright'] = '2012';
$cfg['language'] = 'bg';
$cfg['country'] = 'BG';
$cfg['locale'] = 'bg_BG';
$cfg['locales.dir'] = 'locales/';
$cfg['locales.domain'] = 'messages';
$cfg['short.text'] = 70;

// login and security related
$cfg['login.salt'] = '!$#5m#@1SD31v3)(8s2340!@#sdflkmas@$)(`1!@#19s*fs!@#-`0VXCv11_s{}4';
$cfg['login.saltsize'] = 12;
$cfg['login.timeout'] = 2700; // 45 min.
$cfg['login.lostpass.timeout'] = 86400; // 24 h

// paging
$cfg['paging.count'] = 25;
$cfg['paging.groups'] = 7;
$cfg['paging.count.vip'] = 6;
$cfg['paging.count.categories'] = 8;
$cfg['paging.count.search'] = 8;

// smarty template engine
$cfg['smarty.skin'] = '';
$cfg['smarty.templates'] = 'template'; // html dir
$cfg['smarty.compile'] = 'cache';

// file upload
$cfg['upload.dir'] = 'uploads';
$cfg['upload.dir.offers'] = 'uploads/offers';
$cfg['upload.dir.attachments'] = 'uploads/downloads';
$cfg['upload.dir.icons'] = 'uploads/icons';
$cfg['upload.img.names'] = 'normal,small';
$cfg['upload.img.dir'] = ',96x96';
$cfg['upload.img.size'] = '1600,96';
$cfg['upload.img.quality'] = '100,86';

// smtp mail settings
$cfg['smtp.hostname'] = 'nasbg.com';
$cfg['smtp.hostport'] = 26;
$cfg['smtp.is_auth'] = true;
$cfg['smtp.is_html'] = true;
$cfg['smtp.authtype'] = 'tls';
$cfg['smtp.username'] = 'noreply@nasbg.com';
$cfg['smtp.password'] = 'zuy!dDwMmS}B';
$cfg['smtp.codepage'] = 'UTF-8';

$cfg['mail.contact'] = 'mialygk@gmail.com';
$cfg['mail.contact.name'] = '';
$cfg['mail.offers'] = 'mialygk@gmail.com';
$cfg['mail.offers.name'] = '';
