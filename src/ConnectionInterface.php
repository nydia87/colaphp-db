<?php
/**
 * @contact  nydia87 <349196713@qq.com>
 * @license  http://www.apache.org/licenses/LICENSE-2.0
 */
namespace Colaphp\Db;

use Closure;

interface ConnectionInterface
{
	/**
	 * Run a select statement and return a single result.
	 *
	 * @param string $query
	 * @param array $bindings
	 * @return mixed
	 */
	public function selectOne($query, $bindings = []);

	/**
	 * Run a select statement against the database.
	 *
	 * @param string $query
	 * @param array $bindings
	 * @return array
	 */
	public function select($query, $bindings = []);

	/**
	 * Run an insert statement against the database.
	 *
	 * @param string $query
	 * @param array $bindings
	 * @return bool
	 */
	public function insert($query, $bindings = []);

	/**
	 * Run an update statement against the database.
	 *
	 * @param string $query
	 * @param array $bindings
	 * @return int
	 */
	public function update($query, $bindings = []);

	/**
	 * Run a delete statement against the database.
	 *
	 * @param string $query
	 * @param array $bindings
	 * @return int
	 */
	public function delete($query, $bindings = []);

	/**
	 * Execute an SQL statement and return the boolean result.
	 *
	 * @param string $query
	 * @param array $bindings
	 * @return bool
	 */
	public function statement($query, $bindings = []);

	/**
	 * Run an SQL statement and get the number of rows affected.
	 *
	 * @param string $query
	 * @param array $bindings
	 * @return int
	 */
	public function affectingStatement($query, $bindings = []);

	/**
	 * Run a raw, unprepared query against the PDO connection.
	 *
	 * @param string $query
	 * @return bool
	 */
	public function unprepared($query);

	/**
	 * Prepare the query bindings for execution.
	 *
	 * @return array
	 */
	public function prepareBindings(array $bindings);

	/**
	 * Execute a Closure within a transaction.
	 *
	 * @throws \Exception
	 * @return mixed
	 */
	public function transaction(Closure $callback);

	/**
	 * Start a new database transaction.
	 */
	public function beginTransaction();

	/**
	 * Commit the active database transaction.
	 */
	public function commit();

	/**
	 * Rollback the active database transaction.
	 */
	public function rollBack();

	/**
	 * Get the number of active transactions.
	 *
	 * @return int
	 */
	public function transactionLevel();

	/**
	 * Execute the given callback in "dry run" mode.
	 *
	 * @return array
	 */
	public function pretend(Closure $callback);
}
