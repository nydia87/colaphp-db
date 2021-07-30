<?php

declare(strict_types=1);
/**
 * @contact  nydia87 <349196713@qq.com>
 * @license  http://www.apache.org/licenses/LICENSE-2.0
 */
namespace Colaphp\Db\Connectors;

use PDO;

class PostgresConnector extends Connector implements ConnectorInterface
{
	/**
	 * 默认配置.
	 *
	 * @var array
	 */
	protected $options = [
		PDO::ATTR_CASE => PDO::CASE_NATURAL,
		PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
		PDO::ATTR_ORACLE_NULLS => PDO::NULL_NATURAL,
		PDO::ATTR_STRINGIFY_FETCHES => false,
	];

	/**
	 * Pgsql连接.
	 *
	 * @return \PDO
	 */
	public function connect(array $config)
	{
		$dsn = $this->getDsn($config);

		$options = $this->getOptions($config);

		$connection = $this->createConnection($dsn, $config, $options);

		//charset
		$charset = $config['charset'];
		$connection->prepare("set names '{$charset}'")->execute();

		//timezone
		if (isset($config['timezone'])) {
			$timezone = $config['timezone'];
			$connection->prepare("set time zone '{$timezone}'")->execute();
		}

		//schema
		if (isset($config['schema'])) {
			$schema = $config['schema'];
			$connection->prepare("set search_path to {$schema}")->execute();
		}

		return $connection;
	}

	/**
	 * 创建DSN.
	 *
	 * @return string
	 */
	protected function getDsn(array $config)
	{
		extract($config);

		$host = isset($host) ? "host={$host};" : '';

		$dsn = "pgsql:{$host}dbname={$database}";

		//port
		if (isset($config['port'])) {
			$dsn .= ";port={$port}";
		}

		//sslmode
		if (isset($config['sslmode'])) {
			$dsn .= ";sslmode={$sslmode}";
		}

		return $dsn;
	}
}
