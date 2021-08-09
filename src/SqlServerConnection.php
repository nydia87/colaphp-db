<?php
/**
 * @contact  nydia87 <349196713@qq.com>
 * @license  http://www.apache.org/licenses/LICENSE-2.0
 */
namespace Colaphp\Db;

use Closure;
use Exception;

class SqlServerConnection extends Connection
{
	/**
	 * Execute a Closure within a transaction.
	 *
	 * @throws \Exception
	 * @return mixed
	 */
	public function transaction(Closure $callback)
	{
		if ($this->getDriverName() == 'sqlsrv') {
			return parent::transaction($callback);
		}

		$this->pdo->exec('BEGIN TRAN');

		try {
			$result = $callback($this);

			$this->pdo->exec('COMMIT TRAN');
		} catch (Exception $e) {
			$this->pdo->exec('ROLLBACK TRAN');

			throw $e;
		}

		return $result;
	}

	/**
	 * 日期格式化.
	 *
	 * @return string
	 */
	public function getDateFormat()
	{
		return 'Y-m-d H:i:s.000';
	}
}
