<?php

/**
 * @author: nydia87 <349196713@qq.com>
 */
namespace ColaPHP\Db\Connectors;

class PostgresConnector extends Connector
{
	/**
	 * 默认配置.
	 */
	protected $options = [
		\PDO::ATTR_CASE => \PDO::CASE_NATURAL,
		\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
		\PDO::ATTR_ORACLE_NULLS => \PDO::NULL_NATURAL,
		\PDO::ATTR_STRINGIFY_FETCHES => false,
	];

	/**
	 * Pgsql连接.
	 */
	public function connect(array $config)
	{
		$dsn = $this->getDsn($config);

		$options = $this->getOptions($config);

		$connection = $this->createConnection($dsn, $config, $options);

		// charset
		$charset = $config['charset'];
		$connection->prepare("set names '{$charset}'")->execute();

		// timezone
		if (isset($config['timezone'])) {
			$timezone = $config['timezone'];
			$connection->prepare("set time zone '{$timezone}'")->execute();
		}

		// schema
		if (isset($config['schema'])) {
			$schema = $config['schema'];
			$connection->prepare("set search_path to {$schema}")->execute();
		}

		return $connection;
	}

	/**
	 * 创建DSN.
	 */
	protected function getDsn(array $config)
	{
		extract($config);

		$host = isset($host) ? "host={$host};" : '';

		$dsn = "pgsql:{$host}dbname={$database}";

		// port
		if (isset($config['port'])) {
			$dsn .= ";port={$port}";
		}

		// sslmode
		if (isset($config['sslmode'])) {
			$dsn .= ";sslmode={$sslmode}";
		}

		return $dsn;
	}
}
