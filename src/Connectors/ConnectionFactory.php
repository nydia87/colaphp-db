<?php

declare(strict_types=1);
/**
 * @contact  nydia87 <349196713@qq.com>
 * @license  http://www.apache.org/licenses/LICENSE-2.0
 */
namespace Colaphp\Db\Connectors;

use Colaphp\Db\MySqlConnection;
use Colaphp\Db\PostgresConnection;
use Colaphp\Db\SQLiteConnection;
use Colaphp\Db\SqlServerConnection;
use InvalidArgumentException;
use PDO;

/**
 * PDO 工厂模式.
 */
class ConnectionFactory
{
	/**
	 * 初始化.
	 */
	public function __construct()
	{
	}

	/**
	 * 建立PDO.
	 *
	 * @param string $name
	 * @return \Colaphp\Db\Connection
	 */
	public function make(array $config, $name = null)
	{
		$config = $this->parseConfig($config, $name);
		//只读|须配置写库
		if (isset($config['read'])) {
			return $this->createReadWriteConnection($config);
		}
		//创建独立的数据库对象
		return $this->createSingleConnection($config);
	}

	/**
	 * 创建PDO连接.
	 *
	 * @throws \InvalidArgumentException
	 * @return \Colaphp\Db\Connectors\ConnectorInterface
	 */
	public function createConnector(array $config)
	{
		if (! isset($config['driver'])) {
			throw new InvalidArgumentException('A driver must be specified.');
		}

		switch ($config['driver']) {
			case 'mysql':
				return new MySqlConnector();
			case 'pgsql':
				return new PostgresConnector();
			case 'sqlite':
				return new SQLiteConnector();
			case 'sqlsrv':
				return new SqlServerConnector();
		}

		throw new InvalidArgumentException("Unsupported driver [{$config['driver']}]");
	}

	/**
	 * 独立的数据库对象
	 *
	 * @return \Colaphp\Db\Connection
	 */
	protected function createSingleConnection(array $config)
	{
		//获取PDO实例
		$pdo = $this->createConnector($config)->connect($config);
		//交给对应的数据库对象
		return $this->createConnection($config['driver'], $pdo, $config['database'], $config['prefix'], $config);
	}

	/**
	 * 读、写数据库对象
	 *
	 * @return \Colaphp\Db\Connection
	 */
	protected function createReadWriteConnection(array $config)
	{
		//获取写数据库对象
		$connection = $this->createSingleConnection($this->getWriteConfig($config));
		//在写对象种生成只读对象
		return $connection->setReadPdo($this->createReadPdo($config));
	}

	/**
	 * 创建只读PDO.
	 *
	 * @return \PDO
	 */
	protected function createReadPdo(array $config)
	{
		$readConfig = $this->getReadConfig($config);

		return $this->createConnector($readConfig)->connect($readConfig);
	}

	/**
	 * 获取写配置.
	 *
	 * @return array
	 */
	protected function getReadConfig(array $config)
	{
		$readConfig = $this->getReadWriteConfig($config, 'read');

		return $this->mergeReadWriteConfig($config, $readConfig);
	}

	/**
	 * 获取读配置.
	 *
	 * @return array
	 */
	protected function getWriteConfig(array $config)
	{
		$writeConfig = $this->getReadWriteConfig($config, 'write');

		return $this->mergeReadWriteConfig($config, $writeConfig);
	}

	/**
	 * 获取读、写配置.
	 *
	 * @param string $type
	 * @return array
	 */
	protected function getReadWriteConfig(array $config, $type)
	{
		if (isset($config[$type][0])) {
			return $config[$type][array_rand($config[$type])];
		}

		return $config[$type];
	}

	/**
	 * 合并读、写配置.
	 *
	 * @return array
	 */
	protected function mergeReadWriteConfig(array $config, array $merge)
	{
		return array_except(array_merge($config, $merge), ['read', 'write']);
	}

	/**
	 * 分析配置.
	 *
	 * @param string $name
	 * @return array
	 */
	protected function parseConfig(array $config, $name)
	{
		return array_add(array_add($config, 'prefix', ''), 'name', $name);
	}

	/**
	 * 把PDO连接交给对象类.
	 *
	 * @param string $driver
	 * @param string $database
	 * @param string $prefix
	 * @throws \InvalidArgumentException
	 * @return \Colaphp\Db\Connection
	 */
	protected function createConnection($driver, PDO $connection, $database, $prefix = '', array $config = [])
	{
		switch ($driver) {
			case 'mysql':
				return new MySqlConnection($connection, $database, $prefix, $config);
			case 'pgsql':
				return new PostgresConnection($connection, $database, $prefix, $config);
			case 'sqlite':
				return new SQLiteConnection($connection, $database, $prefix, $config);
			case 'sqlsrv':
				return new SqlServerConnection($connection, $database, $prefix, $config);
		}

		throw new InvalidArgumentException("Unsupported driver [{$driver}]");
	}
}
