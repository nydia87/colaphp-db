<?php
/**
 * @author: nydia87 <349196713@qq.com>
 */
namespace ColaPHP\Db\Connections;

class SqlServerConnection extends Connection
{
	public function transaction(\Closure $callback)
	{
		if ('sqlsrv' == $this->getDriverName()) {
			return parent::transaction($callback);
		}

		$this->pdo->exec('BEGIN TRAN');

		try {
			$result = $callback($this);

			$this->pdo->exec('COMMIT TRAN');
		} catch (\Exception $e) {
			$this->pdo->exec('ROLLBACK TRAN');

			throw $e;
		}

		return $result;
	}

	public function getDateFormat()
	{
		return 'Y-m-d H:i:s.000';
	}
}
