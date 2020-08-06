<?php

use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Uri\Uri;

define('JOOMLA_MINIMUM_PHP', '5.3.10');

if (version_compare(PHP_VERSION, JOOMLA_MINIMUM_PHP, '<')) {
	die('Your host needs to use PHP ' . JOOMLA_MINIMUM_PHP . ' or higher to run this version of Joomla!');
}

// Saves the start time and memory usage.
$startTime = microtime(1);
$startMem  = memory_get_usage();

/**
 * Constant that is checked in included files to prevent direct access.
 * define() is used in the installation folder rather than "const" to not error for PHP 5.2 and lower
 */
define('_JEXEC', 1);

if (file_exists(__DIR__ . '/defines.php')) {
	include_once __DIR__ . '/defines.php';
}

if (!defined('_JDEFINES')) {
	define('JPATH_BASE', __DIR__);
	require_once JPATH_BASE . '/includes/defines.php';
}

require_once JPATH_BASE . '/includes/framework.php';

backup_tables();

/* backup the db OR just a table */
function backup_tables()
{
	$input = Factory::getApplication('site')->input;
	$tbl = $input->getString('tbl', '');
	$config = Factory::getConfig();
	$prefix = $config->get('dbprefix');
	$dbName = $config->get('db');
	$db = Factory::getDbo();

	//get all of the tables
	if ($tbl) {
		$tables = array_map(function ($str) use ($prefix) {
			return $prefix . trim($str);
		}, explode(',', $tbl));
	} else {
		$query = "SELECT TABLE_NAME
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = '$dbName'
            AND TABLE_NAME LIKE '$prefix%'
			ORDER BY `TABLE_NAME` ASC";
			
		$tables = $db->setQuery($query)->loadColumn();
	}

	$return = '';
	//cycle through
	foreach ($tables as $table) {
		$data = $db->setQuery("SELECT * FROM $table")->loadObjectList();
		$create = $db->setQuery("SHOW CREATE TABLE $table")->loadObject();

		$return .= 'DROP TABLE IF EXISTS ' . $db->qn($table) . ';';
		$return .= "\n" . $create->{'Create Table'} . ";";

		foreach ($data as $item) {
			$values = $db->q(array_values((array) $item));
			$keys = $db->qn(array_keys((array) $item));
			$query = $db->getQuery(true)
				->insert($db->qn($table))
				->columns($keys)
				->values(implode(',', $values));

			$return .= (string) $query . ';';
		}
	}

	//save file
	$file = 'db-backup-' . time() . '-' . (md5(implode(',', $tables))) . '.sql';
	if (File::write(JPATH_ROOT . '/' . $file, $return)) {
		echo '<div>success</div>';
		echo '<div><a href="' . Uri::root() . $file.'">'.$file.'</a></div>';
	} else {
		echo 'export error';
	}
}
