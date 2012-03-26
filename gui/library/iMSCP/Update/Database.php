<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2012 by i-MSCP team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @category	iMSCP
 * @package		iMSCP_Update
 * @subpackage	Database
 * @copyright	2010-2012 by i-MSCP team
 * @author		Daniel Andreca <sci2tech@gmail.com>
 * @author		Laurent Declercq <l.declercq@nuxwin.com>
 * @link		http://www.i-mscp.net i-MSCP Home Site
 * @license		http://www.gnu.org/licenses/gpl-2.0.txt GPL v2
 */

/** @see iMSCP_Update */
require_once 'iMSCP/Update.php';

/**
 * Update Database class.
 *
 * Class to handled database updates for i-MSCP.
 *
 * @category	iMSCP
 * @package		iMSCP_Update
 * @subpackage	Database
 * @author		Daniel Andreca <sci2tech@gmail.com>
 * @author		Laurent Declercq <l.declercq@nuxwin.com>
 * @version		0.0.3
 */
class iMSCP_Update_Database extends iMSCP_Update
{
	/**
	 * @var iMSCP_Update
	 */
	protected static $_instance;

	/**
	 * Database name being updated.
	 *
	 * @var string
	 */
	protected $_databaseName;

	/**
	 * Tells whether or not a request must be send to the i-MSCP daemon after that
	 * all database updates were applied.
	 *
	 * @var bool
	 */
	protected $_daemonRequest = false;

	/**
	 * Singleton - Make new unavailable.
	 */
	protected function __construct()
	{
		if (isset(iMSCP_Registry::get('config')->DATABASE_NAME)) {
			$this->_databaseName = iMSCP_Registry::get('config')->DATABASE_NAME;
		} else {
			throw new iMSCP_Update_Exception('Database name not found.');
		}
	}

	/**
	 * Singleton - Make clone unavailable.
	 *
	 * @return void
	 */
	protected function __clone()
	{

	}

	/**
	 * Implements Singleton design pattern.
	 *
	 * @return iMSCP_Update_Database
	 */
	public static function getInstance()
	{
		if (null === self::$_instance) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Checks for available database update.
	 *
	 * @author Laurent Declercq <l.declercq@i-mscp.net>
	 * @return bool TRUE if a database update is available, FALSE otherwise
	 */
	public function isAvailableUpdate()
	{
		if ($this->_getLastAppliedUpdate() < $this->_getNextUpdate()) {
			return true;
		}

		return false;
	}

	/**
	 * Apply all available database updates.
	 *
	 * @author Laurent Declercq <l.declercq@i-mscp.net>
	 * @return bool TRUE on success, FALSE otherwise
	 */
	public function applyUpdates()
	{
		/** @var $dbConfig iMSCP_Config_Handler_Db */
		$dbConfig = iMSCP_Registry::get('dbConfig');

		/** @var $pdo PDO */
		$pdo = iMSCP_Database::getRawInstance();

		while ($this->isAvailableUpdate()) {
			$databaseUpdateRevision = $this->_getNextUpdate();

			// Get the database update method name
			$databaseUpdateMethod = '_databaseUpdate_' . $databaseUpdateRevision;

			// Gets the querie(s) from the database update method
			// A database update method can return void, an array (stack of SQL
			// statements) or a string (SQL statement)
			$queryStack = $this->$databaseUpdateMethod();

			if (!empty($queryStack)) {
				try {
					// One transaction per database update
					// If a query from a database update fail, all $queries from it
					// are canceled. It's only valable for database updates that are
					// free of any statements causing an implicit commit
					$pdo->beginTransaction();

					foreach ((array)$queryStack as $query) {
						if (!empty($query)) {
							$pdo->query($query);
						}
					}

					$dbConfig->set('DATABASE_REVISION', $databaseUpdateRevision);

					$pdo->commit();

				} catch (Exception $e) {

					$pdo->rollBack();

					// Prepare error message
					$errorMessage = sprintf(
						'Database update %s failed.', $databaseUpdateRevision);

					// Extended error message
					$errorMessage .=
						'<br /><br /><strong>Exception message was:</strong><br />' .
						$e->getMessage() . (isset($query)
							? "<br /><strong>Query was:</strong><br />$query" : '');

					if (PHP_SAPI == 'cli') {
						$errorMessage = str_replace(
							array('<br />', '<strong>', '</strong>'),
							array("\n", '', ''), $errorMessage);
					}

					$this->_lastError = $errorMessage;

					return false;
				}
			} else {
				$dbConfig->set('DATABASE_REVISION', $databaseUpdateRevision);
			}
		}

		// We must never run the backend scripts from the CLI update script
		if (PHP_SAPI != 'cli' && $this->_daemonRequest) {
			send_request();
		}

		return true;
	}

	/**
	 * Returns database update(s) details.
	 *
	 * @author Laurent Declercq <l.declercq@i-mscp.net>
	 * @return array
	 */
	public function getDatabaseUpdatesDetails()
	{
		$reflectionStart = $this->_getNextUpdate();

		$reflection = new ReflectionClass(__CLASS__);
		$databaseUpdatesDetails = array();

		/** @var $method ReflectionMethod */
		foreach ($reflection->getMethods() as $method) {
			$methodName = $method->name;

			if (strpos($methodName, '_databaseUpdate_') !== false) {
				$revision = (int)substr($methodName, strrpos($methodName, '_') + 1);

				if ($revision >= $reflectionStart) {
					$details = explode("\n", $method->getDocComment());

					$normalizedDetails = '';
					array_shift($details);

					foreach ($details as $detail) {
						if (preg_match('/^(?: |\t)*\*(?: |\t)+([^@]*)$/', $detail, $matches)) {
							if (empty($normalizedDetails)) {
								$normalizedDetails = $matches[1];
							} else {
								$normalizedDetails .= '<br />' . $matches[1];
							}
						} else {
							break;
						}
					}

					$databaseUpdatesDetails[$revision] = $normalizedDetails;
				}
			}
		}

		return $databaseUpdatesDetails;
	}

	/**
	 * Return next database update revision.
	 *
	 * @author Laurent Declercq <l.declercq@i-mscp.net>
	 * @return int 0 if no update is available
	 */
	protected function _getNextUpdate()
	{
		$lastAvailableUpdateRevision = $this->_getLastAvailableUpdateRevision();
		$nextUpdateRevision = $this->_getLastAppliedUpdate();

		if ($nextUpdateRevision < $lastAvailableUpdateRevision) {
			return $nextUpdateRevision + 1;
		}

		return 0;
	}

	/**
	 * Returns last database update revision number.
	 *
	 * Note: For performances reasons, the revision is retrieved once per process.
	 *
	 * @author Laurent Declercq <l.declercq@i-mscp.net>
	 * @return int Last database update revision number
	 */
	protected function _getLastAvailableUpdateRevision()
	{
		static $lastAvailableUpdateRevision = null;

		if (null === $lastAvailableUpdateRevision) {
			$reflection = new ReflectionClass(__CLASS__);
			$databaseUpdateMethods = array();

			foreach ($reflection->getMethods() as $method) {
				if (strpos($method->name, '_databaseUpdate_') !== false) {
					$databaseUpdateMethods[] = $method->name;
				}
			}

			$databaseUpdateMethod = (string)end($databaseUpdateMethods);
			$lastAvailableUpdateRevision = (int)substr(
				$databaseUpdateMethod, strrpos($databaseUpdateMethod, '_') + 1);
		}

		return $lastAvailableUpdateRevision;
	}

	/**
	 * Returns the revision number of the last applied database update.
	 *
	 * @author Laurent Declercq <l.declercq@i-mscp.net>
	 * @return int Revision number of the last applied database update
	 */
	protected function _getLastAppliedUpdate()
	{
		/** @var $dbConfig iMSCP_Config_Handler_Db */
		$dbConfig = iMSCP_Registry::get('dbConfig');

		if (!isset($dbConfig->DATABASE_REVISION)) {
			$dbConfig->DATABASE_REVISION = 1;
		}

		return (int)$dbConfig->DATABASE_REVISION;
	}

	/**
	 * Checks if a column exists in a database table and if not, return query to add it.
	 *
	 * @author Daniel Andreca <sci2tech@gmail.com>
	 * @since r4509
	 * @param string $table Database table name to operate on
	 * @param string $column Column to be added in the database table
	 * @param string $columnDefinition Column definition including the optional
	 *									(but recommended) positional statement
	 *									([FIRST | AFTER col_name ]
	 * @return string Query to be executed
	 */
	protected function _addColumn($table, $column, $columnDefinition)
	{
		$query = "
			SELECT
				COLUMN_NAME
			FROM
				`information_schema`.`COLUMNS`
			WHERE
				COLUMN_NAME = ?
			AND
				TABLE_NAME = ?
			AND
				`TABLE_SCHEMA` = ?
		";
		$stmt = exec_query($query, array($column, $table, $this->_databaseName));

		if ($stmt->rowCount() == 0) {
			return "ALTER TABLE `$table` ADD `$column` $columnDefinition;";
		} else {
			return '';
		}
	}

	/**
	 * Checks if a column exists in a database table and if yes, return a query to drop it.
	 *
	 * @author Daniel Andreca <sci2tech@gmail.com>
	 * @since r4509
	 * @param string $table Database table from where the column must be dropped
	 * @param string $column Column to be dropped from $table
	 * @return string Query to be executed
	 */
	protected function _dropColumn($table, $column)
	{
		$query = "
			SELECT
				`COLUMN_NAME`
			FROM
				`information_schema`.`COLUMNS`
			WHERE
				`COLUMN_NAME` = ?
			AND
				`TABLE_NAME` = ?
			AND
				`TABLE_SCHEMA` = ?
		";
		$stmt = exec_query($query, array($column, $table, $this->_databaseName));

		if ($stmt->rowCount()) {
			return "ALTER TABLE `$table` DROP column `$column`";
		} else {
			return '';
		}
	}

	/**
	 * Checks if a database table have an index and if yes, return a query to drop it.
	 *
	 * @author Daniel Andreca <sci2tech@gmail.com>
	 * @param string $table Database table from where the column must be dropped
	 * @param string $indexName Index name
	 * @param string $columnName Column to which index belong to
	 * @return string Query to be executed
	 */
	protected function _dropIndex($table, $indexName = 'PRIMARY', $columnName = null)
	{
		if(is_null($columnName)){
			$columnName = $indexName;
		}

		$query = "
			SHOW INDEX FROM
				`$this->_databaseName`.`$table`
			WHERE
				`KEY_NAME` = ?
			AND
				`COLUMN_NAME` = ?
		";
		$stmt = exec_query($query, array($indexName, $columnName));

		if ($stmt->rowCount()) {
			return "ALTER IGNORE TABLE `$this->_databaseName`.`$table` DROP INDEX `$indexName`";
		} else {
			return '';
		}
	}

	/**
	 * Checks if a database table have an index and if no, return a query to add it.
	 *
	 * @author Daniel Andreca <sci2tech@gmail.com>
	 * @param string $table Database table from where the column must be dropped
	 * @param string $columnName Column to which index belong to
	 * @param string $indexType Index type (Primary Unique)
	 * @param string $indexName Index name
	 * @return string Query to be executed
	 */
	protected function _addIndex($table, $columnName, $indexType = 'PRIMARY KEY',
		$indexName = null
	){
		if(is_null($indexName)){
			$indexName = $indexType == 'PRIMARY KEY' ? 'PRIMARY' : $columnName;
		}

		$query = "
			SHOW INDEX FROM
				`$this->_databaseName`.`$table`
			WHERE
				`KEY_NAME` = ?
			AND
				`COLUMN_NAME` = ?
		";
		$stmt = exec_query($query, array($indexName, $columnName));

		if ($stmt->rowCount()) {
			return '';
		} else {
			return "
				ALTER IGNORE TABLE
					`$this->_databaseName`.`$table`
				ADD
					$indexType ".($indexType == 'PRIMARY KEY' ? '' : $indexName)." (`$columnName`)
				";
		}
	}

	/**
	 * Catch any database updates that were removed.
	 *
	 * @param  string $updateMethod Database update method name
	 * @param  array $param
	 * @return void
	 */
	public function __call($updateMethod, $param)
	{
		if (strpos($updateMethod, '_databaseUpdate') === false) {
			throw new iMSCP_Update_Exception(
				sprintf('%s is not a valid database update method', $updateMethod));
		}
	}

	/**
	 * Please, add all the database update methods below. Don't forgot to add the doc
	 * and revision (@since rxxx). Also, when you add a ticket reference in a
	 * databaseUpdate_XX method, place it at begin to allow link generation on GUI.
	 */


}
