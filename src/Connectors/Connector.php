<?php
/**
 * @contact  nydia87 <349196713@qq.com>
 * @license  http://www.apache.org/licenses/LICENSE-2.0
 */
namespace Colaphp\Db\Connectors;

use PDO;

class Connector
{
	/**
	 * 数据库默认配置.
	 *
	 * @var array
	 */
	protected $options = [
		PDO::ATTR_CASE => PDO::CASE_NATURAL,
		PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
		PDO::ATTR_ORACLE_NULLS => PDO::NULL_NATURAL,
		PDO::ATTR_STRINGIFY_FETCHES => false,
		PDO::ATTR_EMULATE_PREPARES => false,
	];

	/**
	 * 设置配置.
	 */
	public function setDefaultOptions(array $options)
	{
		$this->options = $options;
	}

	/**
	 * 获取合并后的配置.
	 *
	 * @return array
	 */
	public function getOptions(array $config)
	{
		$options = array_get($config, 'options', []);

		return array_diff_key($this->options, $options) + $options;
	}

	/**
	 * 获取配置.
	 *
	 * @return array
	 */
	public function getDefaultOptions()
	{
		return $this->options;
	}

	/**
	 * 创建连接.
	 *
	 * @param string $dsn
	 * @return \PDO
	 */
	public function createConnection($dsn, array $config, array $options)
	{
		$username = array_get($config, 'username');

		$password = array_get($config, 'password');

		return new PDO($dsn, $username, $password, $options);
	}
}
