<?php

/**
 * @author: nydia87 <349196713@qq.com>
 */
namespace ColaPHP\Db\Connectors;

class SQLiteConnector extends Connector
{
	/**
	 * 创建 Sqlite.
	 */
	public function connect(array $config)
	{
		$options = $this->getOptions($config);

		// For memory
		if (':memory:' == $config['database']) {
			return $this->createConnection('sqlite::memory:', $config, $options);
		}

		$path = realpath($config['database']);

		if (false === $path) {
			throw new \InvalidArgumentException('Database does not exist.');
		}

		return $this->createConnection("sqlite:{$path}", $config, $options);
	}
}
