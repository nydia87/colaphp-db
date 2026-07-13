<?php
/**
 * @author: nydia87 <349196713@qq.com>
 */
namespace ColaPHP\Db\Connections;

use ColaPHP\Db\Exceptions\QueryException;

class Connection
{
	/**
	 * PDO|连接句柄.
	 */
	protected $pdo;

	/**
	 *  PDO|连接句柄|只读.
	 */
	protected $slavePdo;

	/**
	 * 重连（需自定义）.
	 */
	protected $reconnector;

	/**
	 * 事务数.
	 */
	protected $transactions = 0;

	/**
	 * 日志.
	 */
	protected $logs = [];

	/**
	 * 是否记录日志.
	 */
	protected $logging = false;

	/**
	 * 调试.
	 */
	protected $debuging = false;

	/**
	 * 配置.
	 */
	protected $config = [];

	/**
	 * 数据库.
	 */
	protected $database = '';

	/**
	 * 表前缀
	 */
	protected $tablePrefix = '';

	/**
	 * 初始化.
	 *
	 * @param mixed $database
	 * @param mixed $tablePrefix
	 */
	public function __construct(\PDO $pdo, $database = '', $tablePrefix = '', array $config = [])
	{
		$this->pdo = $pdo;
		$this->database = $database;
		$this->tablePrefix = $tablePrefix;
		$this->config = $config;
	}

	/**
	 * 查询单条记录.
	 *
	 * @param mixed $query
	 * @param mixed $bindings
	 * @param mixed $slave
	 */
	public function selectOne($query, $bindings = [], $slave = true)
	{
		$records = $this->select($query, $bindings, $slave);

		return count($records) > 0 ? reset($records) : null;
	}

	/**
	 * 查询多条记录|直接操作.
	 *
	 * @param mixed $query
	 * @param mixed $bindings
	 * @param mixed $slave
	 */
	public function select($query, $bindings = [], $slave = true)
	{
		return $this->run($query, $bindings, function ($me, $query, $bindings) use ($slave) {
			if ($me->debuging) {
				return [];
			}

			$statement = $this->selectPdo($slave)->prepare($query);
			$statement->execute($me->prepareBindings($bindings));

			return $statement->fetchAll(\PDO::FETCH_ASSOC);
		});
	}

	/**
	 * 插入入口.
	 *
	 * @param mixed $query
	 * @param mixed $bindings
	 */
	public function insert($query, $bindings = [])
	{
		return $this->run($query, $bindings, function ($me, $query, $bindings) {
			if ($me->debuging) {
				return true;
			}

			$bindings = $me->prepareBindings($bindings);

			return $me->getPdo()->prepare($query)->execute($bindings);
		});
	}

	/**
	 * 删除入口.
	 *
	 * @param mixed $query
	 * @param mixed $bindings
	 */
	public function delete($query, $bindings = [])
	{
		return $this->affectingStatement($query, $bindings);
	}

	/**
	 * 修改入口.
	 *
	 * @param mixed $query
	 * @param mixed $bindings
	 */
	public function update($query, $bindings = [])
	{
		return $this->affectingStatement($query, $bindings);
	}

	/**
	 * 返回插入SQL LastID.
	 *
	 * @param null|mixed $name
	 */
	public function lastId($name = null)
	{
		return (int) $this->getPdo()->lastInsertId($name);
	}

	/**
	 * 无参数操作.
	 *
	 * @param mixed $query
	 */
	public function exec($query)
	{
		return $this->run($query, [], function ($me, $query) {
			if ($me->debuging) {
				return true;
			}

			return (bool) $me->getPdo()->exec($query);
		});
	}

	/**
	 * Closure事务
	 */
	public function transaction(\Closure $callback)
	{
		$this->beginTransaction();

		try {
			$result = $callback($this);
			$this->commit();
		} catch (\Exception $e) {
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

		if (1 == $this->transactions) {
			$this->pdo->beginTransaction();
		}
	}

	/**
	 * 提交事务
	 */
	public function commit()
	{
		if (1 == $this->transactions) {
			$this->pdo->commit();
		}

		--$this->transactions;
	}

	/**
	 * 回滚事务
	 */
	public function rollBack()
	{
		if (1 == $this->transactions) {
			$this->transactions = 0;

			$this->pdo->rollBack();
		} else {
			--$this->transactions;
		}
	}

	/**
	 * 调试.
	 *
	 * $db->debug(function() use ($migration, $method)
	 * 	{
	 * 		$migration->$method();
	 *  });.
	 */
	public function debug(\Closure $callback)
	{
		$logging = $this->logging;
		$debuging = $this->debuging;

		$this->enableLog();

		$this->debuging = true;

		$this->logs = [];

		try {
			$callback($this);
		} finally {
			$this->logging = $logging;
			$this->debuging = $debuging;
		}

		return $this->logs;
	}

	/**
	 * 关闭连接.
	 */
	public function disconnect()
	{
		$this->setPdo(null)->setSlavePdo(null);
	}

	/**
	 * 重新连接.
	 */
	public function reconnect()
	{
		if (is_callable($this->reconnector)) {
			return call_user_func($this->reconnector, $this);
		}

		throw new \LogicException('pdo reconnect error .');
	}

	/**
	 * 获取PDO.
	 */
	public function getPdo()
	{
		return $this->pdo;
	}

	/**
	 * 获取只读PDO.
	 */
	public function getSlavePdo()
	{
		if ($this->transactions >= 1) {
			return $this->getPdo();
		}

		return $this->slavePdo ?: $this->pdo;
	}

	/**
	 * 设置PDO.
	 *
	 * @param mixed $pdo
	 */
	public function setPdo($pdo)
	{
		if ($this->transactions >= 1) {
			throw new \RuntimeException('pdo transaction is running ..');
		}

		$this->pdo = $pdo;

		return $this;
	}

	/**
	 * 设置只读PDO.
	 *
	 * @param mixed $pdo
	 */
	public function setSlavePdo($pdo)
	{
		$this->slavePdo = $pdo;

		return $this;
	}

	/**
	 * 获取PDO驱动类型.
	 */
	public function getDriverName()
	{
		return $this->pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);
	}

	/**
	 * 开启日志.
	 */
	public function enableLog()
	{
		$this->logging = true;
	}

	/**
	 * 关闭日志.
	 */
	public function disableLog()
	{
		$this->logging = false;
	}

	/**
	 * 获取日志.
	 */
	public function getLogs()
	{
		return $this->logs;
	}

	/**
	 * 修改、删除操作.
	 *
	 * @param mixed $query
	 * @param mixed $bindings
	 */
	protected function affectingStatement($query, $bindings = [])
	{
		return $this->run($query, $bindings, function ($me, $query, $bindings) {
			if ($me->debuging) {
				return 0;
			}

			$statement = $me->getPdo()->prepare($query);

			$statement->execute($me->prepareBindings($bindings));

			return $statement->rowCount();
		});
	}

	/**
	 * 参数处理.
	 */
	protected function prepareBindings(array $bindings)
	{
		foreach ($bindings as $key => $value) {
			if ($value instanceof \DateTime) {
				$bindings[$key] = $value->format($this->getDateFormat());
			} elseif (false === $value) {
				$bindings[$key] = 0;
			}
		}

		return $bindings;
	}

	/**
	 * 日期格式化.
	 *
	 * @return string
	 */
	protected function getDateFormat()
	{
		return 'Y-m-d H:i:s';
	}

	/**
	 * 设置重连.
	 */
	protected function setReconnector(callable $reconnector)
	{
		$this->reconnector = $reconnector;

		return $this;
	}

	/**
	 * 记录日志.
	 *
	 * @param mixed      $query
	 * @param mixed      $bindings
	 * @param null|mixed $time
	 */
	protected function logQuery($query, $bindings, $time = null)
	{
		if (!$this->logging) {
			return;
		}

		$this->logs[] = compact('query', 'bindings', 'time');
	}

	/**
	 * 获取 Select PDO.
	 *
	 * @param mixed $slave
	 */
	protected function selectPdo($slave = true)
	{
		return ($slave && !is_null($this->getSlavePdo())) ? $this->getSlavePdo() : $this->getPdo();
	}

	/**
	 * Closure 入口.
	 *
	 * @param mixed $query
	 * @param mixed $bindings
	 */
	protected function run($query, $bindings, \Closure $callback)
	{
		// 判断重连
		$this->reconnectIfMissingConnection();

		$start = microtime(true);

		try {
			// 执行SQL操作
			$result = $this->runQueryCallback($query, $bindings, $callback);
		} catch (QueryException $e) {
			// 连接失败重连
			$result = $this->tryAgainIfCausedByLostConnection($e, $query, $bindings, $callback);
		}

		$time = $this->getElapsedTime($start);

		$this->logQuery($query, $bindings, $time);

		return $result;
	}

	/**
	 * Run a SQL statement.
	 *
	 * @param mixed $query
	 * @param mixed $bindings
	 */
	protected function runQueryCallback($query, $bindings, \Closure $callback)
	{
		try {
			$result = $callback($this, $query, $bindings);
		} catch (\Exception $e) {
			throw new QueryException($query, $this->prepareBindings($bindings), $e);
		}

		return $result;
	}

	/**
	 * 如果丢失连接|重试连接.
	 *
	 * @param mixed $query
	 * @param mixed $bindings
	 */
	protected function tryAgainIfCausedByLostConnection(QueryException $e, $query, $bindings, \Closure $callback)
	{
		if ($this->causedByLostConnection($e)) {
			$this->reconnect();

			return $this->runQueryCallback($query, $bindings, $callback);
		}

		throw $e;
	}

	/**
	 * 判断是否重新连接.
	 */
	protected function causedByLostConnection(QueryException $e)
	{
		$message = $e->getPrevious()->getMessage();

		return db_str_contains_db($message, [
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
		if (is_null($this->getPdo()) || is_null($this->getSlavePdo())) {
			$this->reconnect();
		}
	}

	/**
	 * 获取SQL执行时间.
	 *
	 * @param mixed $start
	 */
	protected function getElapsedTime($start)
	{
		return round((microtime(true) - $start) * 1000, 2);
	}
}
