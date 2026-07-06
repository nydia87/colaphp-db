<?php
/**
 * @author: nydia87 <349196713@qq.com>
 * @description:
 */
function db_array_get($array, $key, $default = null)
{
	if (is_null($key)) {
		return $array;
	}

	if (isset($array[$key])) {
		return $array[$key];
	}

	foreach (explode('.', $key) as $segment) {
		if (! is_array($array) || ! array_key_exists($segment, $array)) {
			return db_value($default);
		}

		$array = $array[$segment];
	}

	return $array;
}

/**
 * 判断Closure.
 *
 * @param mixed $value
 */
function db_value($value)
{
	return $value instanceof Closure ? $value() : $value;
}

/**
 * 替换.
 *
 * @param mixed $search
 * @param mixed $subject
 */
function db_str_replace_array($search, array $replace, $subject)
{
	foreach ($replace as $value) {
		$subject = preg_replace('/' . $search . '/', $value, $subject, 1);
	}

	return $subject;
}

/**
 * 批量查找.
 *
 * @param mixed $haystack
 * @param mixed $needles
 */
function db_str_contains_db($haystack, $needles)
{
	foreach ((array) $needles as $needle) {
		if ('' != $needle && false !== strpos($haystack, $needle)) {
			return true;
		}
	}

	return false;
}
