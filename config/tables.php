<?php
/**
 * Списък на таблиците, които се използват в БД
 */
if (!defined('PROGRAM') || PROGRAM !== 1) exit;

$prefix = $DB_CONN['PREFIX'];

/******************************************************************************/
define('TABLE_ARTICLES',                $prefix.'articles'                    );
define('TABLE_CATEGORIES',              $prefix.'categories'                  );
define('TABLE_ICONS',                   $prefix.'icons'                       );
define('TABLE_OFFERS',                  $prefix.'offers'                      );
define('TABLE_OFFERS_FILES',            $prefix.'offers_files'                );
define('TABLE_OFFERS_PERIODS',          $prefix.'offers_periods'              );
define('TABLE_OFFERS_PICTURES',         $prefix.'offers_pictures'             );
define('TABLE_LANGUAGES',               $prefix.'languages'                   );
define('TABLE_SCRIPTS',                 $prefix.'scripts'                     );
define('TABLE_SETTINGS',                $prefix.'settings'                    );
define('TABLE_USERS',                   $prefix.'users'                       );
/******************************************************************************/

unset($prefix);
