<?php
/**
 * @contact  nydia87 <349196713@qq.com>
 * @license  http://www.apache.org/licenses/LICENSE-2.0
 */
namespace Colaphp\Db\Connectors;

use InvalidArgumentException;

class SQLiteConnector extends Connector implements ConnectorInterface
{
	/**
	 * 创建Sqlite.
	 *
	 * @throws \InvalidArgumentException
	 * @return \PDO
	 */
	public function connect(array $config)
	{
		$options = $this->getOptions($config);

		//For memory
		if ($config['database'] == ':memory:') {
			return $this->createConnection('sqlite::memory:', $config, $options);
		}

		$path = realpath($config['database']);

		if ($path === false) {
			throw new InvalidArgumentException('Database does not exist.');
		}

		return $this->createConnection("sqlite:{$path}", $config, $options);
	}
}
