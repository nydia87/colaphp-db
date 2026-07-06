<?php
/**
 * @author: nydia87 <349196713@qq.com>
 * @description:
 */

namespace ColaPHP\Db\Connectors;

abstract class Connector
{
	/**
	 * 数据库默认配置.
	 */
	protected $options = [
		\PDO::ATTR_CASE => \PDO::CASE_NATURAL,
		\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
		\PDO::ATTR_ORACLE_NULLS => \PDO::NULL_NATURAL,
		\PDO::ATTR_STRINGIFY_FETCHES => false,
		\PDO::ATTR_EMULATE_PREPARES => false,
	];

	/**
	 * 连接数据库入口.
	 */
	abstract public function connect(array $config);

	/**
	 * 获取合并后的配置.
	 */
	public function getOptions(array $config)
	{
		$options = db_array_get($config, 'options', []);

		return array_diff_key($this->options, $options) + $options;
	}

	/**
	 * 创建连接.
	 *
	 * @param mixed $dsn
	 */
	public function createConnection($dsn, array $config, array $options)
	{
		$username = db_array_get($config, 'username');

		$password = db_array_get($config, 'password');

		return new \PDO($dsn, $username, $password, $options);
	}
}
