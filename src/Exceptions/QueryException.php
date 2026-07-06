<?php
namespace ColaPHP\Db\Exceptions;

class QueryException extends \PDOException
{
	protected $sql;

	protected $bindings;

	public function __construct($sql, array $bindings, $previous)
	{
		parent::__construct('', 0, $previous);

		$this->sql = $sql;
		$this->bindings = $bindings;
		$this->previous = $previous;
		$this->code = $previous->getCode();
		$this->message = $this->formatMessage($sql, $bindings, $previous);

		if ($previous instanceof \PDOException) {
			$this->errorInfo = $previous->errorInfo;
		}
	}

	public function getSql()
	{
		return $this->sql;
	}

	public function getBindings()
	{
		return $this->bindings;
	}

	protected function formatMessage($sql, $bindings, $previous)
	{
		return $previous->getMessage() . ' (SQL: ' . db_str_replace_array('\?', $bindings, $sql) . ')';
	}
}
