<?php

/**
 * @author: nydia87 <349196713@qq.com>
 */
namespace ColaPHP\Db\Connectors;

class MySqlConnector extends Connector
{
	/**
	 * Mysql连接.
	 */
	public function connect(array $config)
	{
		$dsn = $this->getDsn($config);

		$options = $this->getOptions($config);

		$connection = $this->createConnection($dsn, $config, $options);

		// unix_socket
		if (isset($config['unix_socket'])) {
			$connection->exec("use `{$config['database']}`;");
		}

		// collation、charset
		$collation = $config['collation'];
		$charset = $config['charset'];
		$names = "set names '{$charset}'" . (!is_null($collation) ? " collate '{$collation}'" : '');
		$connection->prepare($names)->execute();

		// timezone
		if (isset($config['timezone'])) {
			$connection->prepare('set time_zone="' . $config['timezone'] . '"')->execute();
		}

		// sql_mode
		if (isset($config['strict']) && $config['strict']) {
			$connection->prepare("set session sql_mode='STRICT_ALL_TABLES'")->execute();
		}

		return $connection;
	}

	/**
	 * 创建DSN.
	 */
	protected function getDsn(array $config)
	{
		return $this->configHasSocket($config) ? $this->getSocketDsn($config) : $this->getHostDsn($config);
	}

	/**
	 * 判断DSN SOCKET.
	 */
	protected function configHasSocket(array $config)
	{
		return isset($config['unix_socket']) && !empty($config['unix_socket']);
	}

	/**
	 * DSN SOCKET.
	 */
	protected function getSocketDsn(array $config)
	{
		extract($config);

		return "mysql:unix_socket={$config['unix_socket']};dbname={$database}";
	}

	/**
	 * DSN HOST.
	 */
	protected function getHostDsn(array $config)
	{
		extract($config);

		return isset($config['port'])
						? "mysql:host={$host};port={$port};dbname={$database}"
						: "mysql:host={$host};dbname={$database}";
	}
}
