<?php

declare(strict_types=1);
/**
 * @contact  nydia87 <349196713@qq.com>
 * @license  http://www.apache.org/licenses/LICENSE-2.0
 */
namespace Colaphp\Db;

use Closure;
use DateTime;
use Exception;
use LogicException;
use PDO;
use RuntimeException;

class Connection implements ConnectionInterface
{
	/**
	 * PDO|连接句柄.
	 *
	 * @var PDO
	 */
	protected $pdo;

	/**
	 *  PDO|连接句柄|只读.
	 *
	 * @var PDO
	 */
	protected $readPdo;

	/**
	 * The reconnector instance for the connection.
	 *
	 * @var callable
	 */
	protected $reconnector;

	/**
	 * 默认查找项.
	 *
	 * @var int
	 */
	protected $fetchMode = PDO::FETCH_ASSOC;

	/**
	 * 事务数.
	 *
	 * @var int
	 */
	protected $transactions = 0;

	/**
	 * 日志.
	 *
	 * @var array
	 */
	protected $queryLog = [];

	/**
	 * 是否记录日志.
	 *
	 * @var bool
	 */
	protected $loggingQueries = false;

	/**
	 * Indicates if the connection is in a "dry run".
	 *
	 * @var bool
	 */
	protected $pretending = false;

	/**
	 * 配置.
	 *
	 * @var array
	 */
	protected $config = [];

	/**
	 * 初始化.
	 *
	 * @param string $database
	 * @param string $tablePrefix
	 */
	public function __construct(PDO $pdo, $database = '', $tablePrefix = '', array $config = [])
	{
		$this->pdo = $pdo;
		$this->database = $database;
		$this->tablePrefix = $tablePrefix;
		$this->config = $config;
	}

	/**
	 * 查询单条记录.
	 *
	 * @param string $query
	 * @param array $bindings
	 * @return mixed
	 */
	public function selectOne($query, $bindings = [])
	{
		$records = $this->select($query, $bindings);

		return count($records) > 0 ? reset($records) : null;
	}

	/**
	 * Run a select statement against the database.
	 *
	 * @param string $query
	 * @param array $bindings
	 * @return array
	 */
	public function selectFromWriteConnection($query, $bindings = [])
	{
		return $this->select($query, $bindings, false);
	}

	/**
	 * 查询多条记录|直接操作.
	 *
	 * @param string $query
	 * @param array $bindings
	 * @param bool $useReadPdo
	 * @return array
	 */
	public function select($query, $bindings = [], $useReadPdo = true)
	{
		return $this->run($query, $bindings, function ($me, $query, $bindings) use ($useReadPdo) {
			if ($me->pretending()) {
				return [];
			}

			$statement = $this->getPdoForSelect($useReadPdo)->prepare($query);

			$statement->execute($me->prepareBindings($bindings));

			return $statement->fetchAll($me->getFetchMode());
		});
	}

	/**
	 * 插入入口.
	 *
	 * @param string $query
	 * @param array $bindings
	 * @return bool
	 */
	public function insert($query, $bindings = [])
	{
		return $this->statement($query, $bindings);
	}

	/**
	 * 修改入口.
	 *
	 * @param string $query
	 * @param array $bindings
	 * @return int
	 */
	public function update($query, $bindings = [])
	{
		return $this->affectingStatement($query, $bindings);
	}

	/**
	 * 删除入口.
	 *
	 * @param string $query
	 * @param array $bindings
	 * @return int
	 */
	public function delete($query, $bindings = [])
	{
		return $this->affectingStatement($query, $bindings);
	}

	/**
	 * 插入操作.
	 *
	 * @param string $query
	 * @param array $bindings
	 * @return bool
	 */
	public function statement($query, $bindings = [])
	{
		return $this->run($query, $bindings, function ($me, $query, $bindings) {
			if ($me->pretending()) {
				return true;
			}

			$bindings = $me->prepareBindings($bindings);

			return $me->getPdo()->prepare($query)->execute($bindings);
		});
	}

	/**
	 * 修改、删除操作.
	 *
	 * @param string $query
	 * @param array $bindings
	 * @return int
	 */
	public function affectingStatement($query, $bindings = [])
	{
		return $this->run($query, $bindings, function ($me, $query, $bindings) {
			if ($me->pretending()) {
				return 0;
			}

			$statement = $me->getPdo()->prepare($query);

			$statement->execute($me->prepareBindings($bindings));

			return $statement->rowCount();
		});
	}

	/**
	 * 无参数操作.
	 *
	 * @param string $query
	 * @return bool
	 */
	public function unprepared($query)
	{
		return $this->run($query, [], function ($me, $query) {
			if ($me->pretending()) {
				return true;
			}
			return (bool) $me->getPdo()->exec($query);
		});
	}

	/**
	 * 参数处理.
	 *
	 * @return array
	 */
	public function prepareBindings(array $bindings)
	{
		foreach ($bindings as $key => $value) {
			if ($value instanceof DateTime) {
				$bindings[$key] = $value->format($this->getDateFormat());
			} elseif ($value === false) {
				$bindings[$key] = 0;
			}
		}

		return $bindings;
	}

	/**
	 * Closure事务
	 *
	 * @throws \Exception
	 * @return mixed
	 */
	public function transaction(Closure $callback)
	{
		$this->beginTransaction();
		try {
			$result = $callback($this);
			$this->commit();
		} catch (Exception $e) {
			$this->rollBack();
			throw $e;
		}

		return $result;
	}

	/**
	 * 开启事务
	 */
	public function beginTransaction()
	{
		++$this->transactions;

		if ($this->transactions == 1) {
			$this->pdo->beginTransaction();
		}
	}

	/**
	 * 提交事务
	 */
	public function commit()
	{
		if ($this->transactions == 1) {
			$this->pdo->commit();
		}

		--$this->transactions;
	}

	/**
	 * 回滚事务
	 */
	public function rollBack()
	{
		if ($this->transactions == 1) {
			$this->transactions = 0;

			$this->pdo->rollBack();
		} else {
			--$this->transactions;
		}
	}

	/**
	 * 获取事务数.
	 *
	 * @return int
	 */
	public function transactionLevel()
	{
		return $this->transactions;
	}

	/**
	 * Execute the given callback in "dry run" mode.
	 * $db->pretend(function() use ($migration, $method)
	 * 	{
	 * 		$migration->$method();
	 *  });.
	 *
	 * @return array
	 */
	public function pretend(Closure $callback)
	{
		$loggingQueries = $this->loggingQueries;

		$this->enableQueryLog();

		$this->pretending = true;

		$this->queryLog = [];

		$callback($this);

		$this->pretending = false;

		$this->loggingQueries = $loggingQueries;

		return $this->queryLog;
	}

	/**
	 * 关闭连接.
	 */
	public function disconnect()
	{
		$this->setPdo(null)->setReadPdo(null);
	}

	/**
	 * 重新连接.
	 *
	 * @throws \LogicException
	 */
	public function reconnect()
	{
		if (is_callable($this->reconnector)) {
			return call_user_func($this->reconnector, $this);
		}

		throw new LogicException('Lost connection and no reconnector available.');
	}

	/**
	 * 日期格式化.
	 *
	 * @return string
	 */
	public function getDateFormat()
	{
		return 'Y-m-d H:i:s';
	}

	/**
	 * 获取PDO.
	 *
	 * @return \PDO
	 */
	public function getPdo()
	{
		return $this->pdo;
	}

	/**
	 * 获取只读PDO.
	 *
	 * @return \PDO
	 */
	public function getReadPdo()
	{
		if ($this->transactions >= 1) {
			return $this->getPdo();
		}

		return $this->readPdo ?: $this->pdo;
	}

	/**
	 * 设置PDO.
	 *
	 * @param null|\PDO $pdo
	 * @return $this
	 */
	public function setPdo($pdo)
	{
		if ($this->transactions >= 1) {
			throw new RuntimeException("Can't swap PDO instance while within transaction.");
		}

		$this->pdo = $pdo;

		return $this;
	}

	/**
	 * 设置只读PDO.
	 *
	 * @param null|\PDO $pdo
	 * @return $this
	 */
	public function setReadPdo($pdo)
	{
		$this->readPdo = $pdo;

		return $this;
	}

	/**
	 * 设置重连 reconnector.
	 *
	 * @return $this
	 */
	public function setReconnector(callable $reconnector)
	{
		$this->reconnector = $reconnector;

		return $this;
	}

	/**
	 * 获取PDO驱动类型.
	 *
	 * @return string
	 */
	public function getDriverName()
	{
		return $this->pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);
	}

	/**
	 * Determine if the connection in a "dry run".
	 *
	 * @return bool
	 */
	public function pretending()
	{
		return $this->pretending === true;
	}

	/**
	 * 获取fetchMode.
	 *
	 * @return int
	 */
	public function getFetchMode()
	{
		return $this->fetchMode;
	}

	/**
	 * 设置fetchMode.
	 *
	 * @param int $fetchMode
	 * @return int
	 */
	public function setFetchMode($fetchMode)
	{
		$this->fetchMode = $fetchMode;
	}

	/**
	 * 记录日志.
	 *
	 * @param string $query
	 * @param array $bindings
	 * @param null|float $time
	 */
	public function logQuery($query, $bindings, $time = null)
	{
		if (! $this->loggingQueries) {
			return;
		}

		$this->queryLog[] = compact('query', 'bindings', 'time');
	}

	/**
	 * 获取日志.
	 *
	 * @return array
	 */
	public function getQueryLog()
	{
		return $this->queryLog;
	}

	/**
	 * 清空日志.
	 */
	public function flushQueryLog()
	{
		$this->queryLog = [];
	}

	/**
	 * 开启日志.
	 */
	public function enableQueryLog()
	{
		$this->loggingQueries = true;
	}

	/**
	 * 关闭日志.
	 */
	public function disableQueryLog()
	{
		$this->loggingQueries = false;
	}

	/**
	 * 判断日志状态
	 *
	 * @return bool
	 */
	public function logging()
	{
		return $this->loggingQueries;
	}

	/**
	 * 获取Select PDO.
	 *
	 * @param bool $useReadPdo
	 * @return \PDO
	 */
	protected function getPdoForSelect($useReadPdo = true)
	{
		return $useReadPdo ? $this->getReadPdo() : $this->getPdo();
	}

	/**
	 * Closure 入口.
	 *
	 * @param string $query
	 * @param array $bindings
	 * @throws \Colaphp\Db\QueryException
	 * @return mixed
	 */
	protected function run($query, $bindings, Closure $callback)
	{
		//判断重连
		$this->reconnectIfMissingConnection();

		$start = microtime(true);

		try {
			//执行SQL操作
			$result = $this->runQueryCallback($query, $bindings, $callback);
		} catch (QueryException $e) {
			//连接失败重连
			$result = $this->tryAgainIfCausedByLostConnection($e, $query, $bindings, $callback);
		}

		$time = $this->getElapsedTime($start);

		$this->logQuery($query, $bindings, $time);

		return $result;
	}

	/**
	 * Run a SQL statement.
	 *
	 * @param string $query
	 * @param array $bindings
	 * @throws \Colaphp\Db\QueryException
	 * @return mixed
	 */
	protected function runQueryCallback($query, $bindings, Closure $callback)
	{
		try {
			$result = $callback($this, $query, $bindings);
		} catch (Exception $e) {
			throw new QueryException($query, $this->prepareBindings($bindings), $e);
		}

		return $result;
	}

	/**
	 * 如果丢失连接|重试连接.
	 *
	 * @param \Colaphp\Db\QueryException $e
	 * @param string $query
	 * @param array $bindings
	 * @throws \Colaphp\Db\QueryException
	 * @return mixed
	 */
	protected function tryAgainIfCausedByLostConnection(QueryException $e, $query, $bindings, Closure $callback)
	{
		if ($this->causedByLostConnection($e)) {
			$this->reconnect();

			return $this->runQueryCallback($query, $bindings, $callback);
		}

		throw $e;
	}

	/**
	 * 判断是否重新连接.
	 *
	 * @param \Colaphp\Db\QueryException $e
	 * @return bool
	 */
	protected function causedByLostConnection(QueryException $e)
	{
		$message = $e->getPrevious()->getMessage();

		return str_contains_db($message, [
			'server has gone away',
			'no connection to the server',
			'Lost connection',
		]);
	}

	/**
	 * 判断并执行重连.
	 */
	protected function reconnectIfMissingConnection()
	{
		if (is_null($this->getPdo()) || is_null($this->getReadPdo())) {
			$this->reconnect();
		}
	}

	/**
	 * 获取SQL执行时间.
	 *
	 * @param int $start
	 * @return float
	 */
	protected function getElapsedTime($start)
	{
		return round((microtime(true) - $start) * 1000, 2);
	}
}
