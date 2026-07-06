<?php

namespace ColaPHP\Db;

use ColaPHP\Db\Connections\MySqlConnection;
use ColaPHP\Db\Connections\PostgresConnection;
use ColaPHP\Db\Connections\SQLiteConnection;
use ColaPHP\Db\Connections\SqlServerConnection;
use ColaPHP\Db\Connectors\MySqlConnector;
use ColaPHP\Db\Connectors\PostgresConnector;
use ColaPHP\Db\Connectors\SQLiteConnector;
use ColaPHP\Db\Connectors\SqlServerConnector;

class DbManager
{

    protected $config = [];

    public function __construct($config = [])
    {
        $this->config = $config;
    }

	/**
	 * 建立PDO.
	 */
	public function make()
	{
		// 只读|须配置写库
		if (isset($this->config['slave'])) {
			return $this->createMasterSlaveConnection($this->config);
		}

		// 创建独立的数据库对象
		return $this->createConnection($this->config);
	}

	/**
	 * 创建数据库连接
	 */
	protected function createConnection($config = [])
	{
		// 获取PDO实例
		$pdo = $this->getConnector($config['driver'])->connect($config);

		// 交给对应的数据库对象
		return $this->getConnection($config['driver'], $pdo, $config['database'], $config['prefix'], $config);
	}

	/**
	 * 读、写数据库对象
	 */
	protected function createMasterSlaveConnection($config = [])
	{
		// 获取写库对象
        $master = $this->createConnection($this->mergeConfig($config, $config['master']));

        // 获取从库对象
        $slave = $this->createConnection($this->mergeConfig($config, $config['slave']));

		// 持有从库对象
		return $master->setSlavePdo($slave->getPdo());
	}

	/**
	 * 合并读、写配置.
	 */
	protected function mergeConfig($config = [], $merge  = [])
	{
        return array_diff_key(array_merge($config, $merge), array_flip( ['slave', 'master']));
	}

    /**
     * 创建PDO连接.
     *
     * @throws \InvalidArgumentException
     */
    protected function getConnector($driver)
    {
        switch ($driver) {
            case 'mysql':
                return new MySqlConnector();
            case 'pgsql':
                return new PostgresConnector();
            case 'sqlite':
                return new SQLiteConnector();
            case 'sqlsrv':
                return new SqlServerConnector();
        }

        throw new \InvalidArgumentException("Unsupported driver [{$driver}]");
    }

	/**
	 * 把PDO连接交给对象类.
	 *
	 * @param string $driver
	 * @param string $database
	 * @param string $prefix
	 *
	 * @throws \InvalidArgumentException
	 */
	protected function getConnection($driver, \PDO $connection, $database, $prefix = '', $config = [])
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

		throw new \InvalidArgumentException("Unsupported driver [{$driver}]");
	}
}
