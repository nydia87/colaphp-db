<?php

declare(strict_types=1);
/**
 * @contact  nydia87 <349196713@qq.com>
 * @license  http://www.apache.org/licenses/LICENSE-2.0
 */
// 助手函数

if (! function_exists('array_get')) {
	/**
	 * 获取配置.
	 *
	 * @param array $array
	 * @param string $key
	 * @param mixed $default
	 * @return mixed
	 */
	function array_get($array, $key, $default = null)
	{
		if (is_null($key)) {
			return $array;
		}

		if (isset($array[$key])) {
			return $array[$key];
		}

		foreach (explode('.', $key) as $segment) {
			if (! is_array($array) || ! array_key_exists($segment, $array)) {
				return value($default);
			}

			$array = $array[$segment];
		}

		return $array;
	}
}

if (! function_exists('array_add')) {
	/**
	 * 添加配置.
	 *
	 * @param array $array
	 * @param string $key
	 * @param mixed $value
	 * @return array
	 */
	function array_add($array, $key, $value)
	{
		if (is_null($key)) {
			return $array = $value;
		}

		$keys = explode('.', $key);

		while (count($keys) > 1) {
			$key = array_shift($keys);

			if (! isset($array[$key]) || ! is_array($array[$key])) {
				$array[$key] = [];
			}

			$array = &$array[$key];
		}

		$array[array_shift($keys)] = $value;

		return $array;
	}
}

if (! function_exists('array_except')) {
	/**
	 * 排除配置.
	 *
	 * @param array $array
	 * @param array|string $keys
	 * @return array
	 */
	function array_except($array, $keys)
	{
		return array_diff_key($array, array_flip((array) $keys));
	}
}

if (! function_exists('value')) {
	/**
	 * 判断Closure.
	 *
	 * @param mixed $value
	 * @return mixed
	 */
	function value($value)
	{
		return $value instanceof Closure ? $value() : $value;
	}
}

if (! function_exists('str_replace_array')) {
	/**
	 * 替换.
	 *
	 * @param string $search
	 * @param string $subject
	 * @return string
	 */
	function str_replace_array($search, array $replace, $subject)
	{
		foreach ($replace as $value) {
			$subject = preg_replace('/' . $search . '/', $value, $subject, 1);
		}

		return $subject;
	}
}

if (! function_exists('str_contains_db')) {
	/**
	 * 批量查找.
	 *
	 * @param string $haystack
	 * @param array|string $needles
	 * @return bool
	 */
	function str_contains_db($haystack, $needles)
	{
		foreach ((array) $needles as $needle) {
			if ($needle != '' && strpos($haystack, $needle) !== false) {
				return true;
			}
		}

		return false;
	}
}

if (! function_exists('getPDO')) {
	/**
	 * 获取PDO实例.
	 *
	 * @param string $poolname PDO实例名
	 * @param array $config 配置
	 * @return object
	 */
	function getPDO($poolname = 'mysql', $config = [])
	{
		static $pdos = [];
		if (! isset($pdos[$poolname])) {
			$dbFactory = new \Colaphp\Db\Connectors\ConnectionFactory();
			$pdos[$poolname] = $dbFactory->make($config, $poolname);
		}
		return $pdos[$poolname];
	}
}
