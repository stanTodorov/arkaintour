<?php
if (!defined('PROGRAM') || PROGRAM !== 1) exit;

$ml = array();

$ml['L_ERROR_DB_CONNECT'] = __('Грешка при връзка с базата от данни!');
$ml['L_ERROR_DB_QUERY'] = __('Грешка при заявката към базата от данни!');
$ml['L_ERROR_LOGIN'] = __('Грешна парола или потребителско име!');
$ml['L_ERROR_LOGIN_REQUIRE'] = __('Не сте въвели парола или потребителско име!');
$ml['L_ERROR_BAD_ID'] = __('Грешен идентификатор! Моля, опитайте отново!');
$ml['L_ERROR_INPUT'] = __('Въведените данни съдържат грешки, които трябва да бъдат остранени!');
$ml['L_ERROR_INPUT_DATETIME'] = __('Полето съдържа невалидна дата или час!');
$ml['L_ERROR_NOT_FOUND'] = __('Няма намерени резултати!');
$ml['L_ERROR_PERMS_DENIED'] = __('Нямате права за тази операция!');
$ml['L_ERROR_UPLOAD'] = __('Възникна грешка с изпратения файл.');
$ml['L_ERROR_IMG'] = __('Снимката е повредена или е невалиден формат.');
$ml['L_ERROR_SEND_MAIL'] = __('Възникна грешка при изпращане на съобщението! Моля, опитайте отново!');

$ml['L_FORM_MIN'] = __('Минималната дължина е %s символа!');
$ml['L_FORM_MAX'] = __('Максималната дължина е %s символа!');
$ml['L_FORM_REQ'] = __('Полето е задължително!');
$ml['L_FORM_REGEX'] = __('Невалидни символи!');
$ml['L_FORM_BETWEEN'] = __('Стойността може да бъде между %s и %s!');
$ml['L_FORM_REGEX_ALLOWED'] = __('Позволени символи: %s!');

$ml['L_DATETIME_ALL'] = __('l j F Y год. H:i:s', 'dateformat');
$ml['L_DATETIME_ALL_S'] = __('D j M Y H:i', 'dateformat');
$ml['L_DATETIME_DATE'] = __('d F Y', 'dateformat');
$ml['L_DATETIME_DATE_S'] = __('d.m.Y', 'dateformat');

$ml['L_MONTHS'] = array();
$ml['L_MONTHS'][] = __('януари', 'months');
$ml['L_MONTHS'][] = __('февруари', 'months');
$ml['L_MONTHS'][] = __('март', 'months');
$ml['L_MONTHS'][] = __('април', 'months');
$ml['L_MONTHS'][] = __('май', 'months');
$ml['L_MONTHS'][] = __('юни', 'months');
$ml['L_MONTHS'][] = __('юли', 'months');
$ml['L_MONTHS'][] = __('август', 'months');
$ml['L_MONTHS'][] = __('септември', 'months');
$ml['L_MONTHS'][] = __('октомври', 'months');
$ml['L_MONTHS'][] = __('ноември', 'months');
$ml['L_MONTHS'][] = __('декември', 'months');

$ml['L_MONTHS_ABBR'] = array();
$ml['L_MONTHS_ABBR'][] = __('яну', 'months-abbr');
$ml['L_MONTHS_ABBR'][] = __('фев', 'months-abbr');
$ml['L_MONTHS_ABBR'][] = __('мар', 'months-abbr');
$ml['L_MONTHS_ABBR'][] = __('апр', 'months-abbr');
$ml['L_MONTHS_ABBR'][] = __('май', 'months-abbr');
$ml['L_MONTHS_ABBR'][] = __('юни', 'months-abbr');
$ml['L_MONTHS_ABBR'][] = __('юли', 'months-abbr');
$ml['L_MONTHS_ABBR'][] = __('авг', 'months-abbr');
$ml['L_MONTHS_ABBR'][] = __('сеп', 'months-abbr');
$ml['L_MONTHS_ABBR'][] = __('окт', 'months-abbr');
$ml['L_MONTHS_ABBR'][] = __('ное', 'months-abbr');
$ml['L_MONTHS_ABBR'][] = __('дек', 'months-abbr');

$ml['L_DAYS'] = array();
$ml['L_DAYS'][] = __('понеделник', 'days');
$ml['L_DAYS'][] = __('вторник', 'days');
$ml['L_DAYS'][] = __('сряда', 'days');
$ml['L_DAYS'][] = __('четвъртък', 'days');
$ml['L_DAYS'][] = __('петък', 'days');
$ml['L_DAYS'][] = __('събота', 'days');
$ml['L_DAYS'][] = __('неделя', 'days');

$ml['L_DAYS_ABBR'] = array();
$ml['L_DAYS_ABBR'][] = __('пн', 'days-abbr');
$ml['L_DAYS_ABBR'][] = __('вт', 'days-abbr');
$ml['L_DAYS_ABBR'][] = __('ср', 'days-abbr');
$ml['L_DAYS_ABBR'][] = __('чт', 'days-abbr');
$ml['L_DAYS_ABBR'][] = __('пт', 'days-abbr');
$ml['L_DAYS_ABBR'][] = __('сб', 'days-abbr');
$ml['L_DAYS_ABBR'][] = __('нд', 'days-abbr');

$captcha = array();
