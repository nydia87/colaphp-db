<?php
/**
 * @contact  nydia87 <349196713@qq.com>
 * @license  http://www.apache.org/licenses/LICENSE-2.0
 */
namespace Colaphp\Db\Connectors;

interface ConnectorInterface
{
	/**
	 * 连接数据库入口.
	 *
	 * @return \PDO
	 */
	public function connect(array $config);
}
