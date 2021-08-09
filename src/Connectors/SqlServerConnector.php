<?php
/**
 * @contact  nydia87 <349196713@qq.com>
 * @license  http://www.apache.org/licenses/LICENSE-2.0
 */
namespace Colaphp\Db\Connectors;

use PDO;

class SqlServerConnector extends Connector implements ConnectorInterface
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
	 * SqlServer连接.
	 *
	 * @return \PDO
	 */
	public function connect(array $config)
	{
		$options = $this->getOptions($config);

		return $this->createConnection($this->getDsn($config), $config, $options);
	}

	/**
	 * 创建DSN.
	 *
	 * @return string
	 */
	protected function getDsn(array $config)
	{
		if (in_array('dblib', $this->getAvailableDrivers())) {
			return $this->getDblibDsn($config);
		}

		return $this->getSqlSrvDsn($config);
	}

	/**
	 * DSN dblib.
	 *
	 * @return string
	 */
	protected function getDblibDsn(array $config)
	{
		$port = isset($config['port']) ? ':' . $config['port'] : '';

		return "dblib:host={$config['host']}{$port};dbname={$config['database']}";
	}

	/**
	 * DSN sqlsrv.
	 *
	 * @return string
	 */
	protected function getSqlSrvDsn(array $config)
	{
		$port = isset($config['port']) ? ',' . $config['port'] : '';

		$dbName = $config['database'] != '' ? ";Database={$config['database']}" : '';

		return "sqlsrv:Server={$config['host']}{$port}{$dbName}";
	}

	/**
	 * PDO drivers.
	 *
	 * @return array
	 */
	protected function getAvailableDrivers()
	{
		return PDO::getAvailableDrivers();
	}
}
