<?php

/*
 * Copyright (C) 2016 IuriiP <hardwork.mouse@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Wtf\Helper;

/**
 * Common functions
 *
 * @author IuriiP <hardwork.mouse@gmail.com>
 */
abstract class Common {

	/**
	 * Make name in CamelCase style
	 * 
	 * @param string $string
	 * @param boolean $ucfirst
	 * @return string
	 */
	public static function camelCase($string, $ucfirst = true) {
		$str = preg_replace_callback('~_([a-z])~i', function($matches) {
			return ucfirst($matches[1]);
		}, $string);
		return $ucfirst ? ucfirst($str) : $str;
	}

	/**
	 * Make name in snake_case style
	 * 
	 * @param string $string
	 * @return string
	 */
	public static function snakeCase($string) {
		$str = preg_replace_callback('~[A-Z]~', function($matches) {
			return '_' . strtolower($matches[0]);
		}, lcfirst($string));
		return trim($str, '_');
	}

	/**
	 * Pluralise last element in string
	 * 
	 * @param string $string
	 * @return string
	 */
	public static function plural($string) {
		return preg_replace_callback('~es$|[a-z]$~', function($matches) {
			switch($matches[0]) {
				case 'es': return 'es';
				case 'f': return 'ves';
				case 's': return 'ses';
				case 'x': return 'xes';
				case 'y': return 'ies';
				case 'z': return 'zes';
				default: return $matches[0] . 's';
			}
		}, $string);
	}

	/**
	 * Eval string as PHP and return result
	 * 
	 * @param string $string
	 * @return mixed
	 */
	public static function parsePhp($string) {
		$content = null;
		ob_start();
		$content = eval('?>' . $string);
		$echo = ob_get_clean();
		if((false === $content) && preg_match('~Parse error~', $echo)) {
			throw new \ErrorException('Parse error');
		}
		return $content;
	}

	/**
	 * Static wrapper for include code from memory
	 * 
	 * @param string $string
	 * @param array $context
	 * @param mixed $object
	 * @return string
	 */
	public static function includePhp($string, $context = [], $object = null) {
		return includePhp($string, $context, $object);
	}

	/**
	 * Normalize path: resolves '.' and '..' elements
	 * 
	 * @param string $path
	 * @return string
	 */
	public static function normalizePath($path) {
		$path = str_replace(['/', '\\'], '/', $path);
		$parts = array_filter(explode('/', $path), 'strlen');
		$absolutes = [];
		foreach($parts as $part) {
			if('.' == $part)
				continue;
			if('..' == $part) {
				array_pop($absolutes);
			} else {
				$absolutes[] = $part;
			}
		}
		return implode('/', $absolutes);
	}

	/**
	 * Make absolute path, based on root.
	 * 
	 * @param string $path
	 * @param string $root
	 * @return string
	 */
	public static function absolutePath($path, $root = null) {
		$path = str_replace('\\', '/', $path);
		$base = str_replace('\\', '/', realpath('/'));
		if('/' === $path{0}) {
			return self::normalizePath($base . '.' . $path);
		}
		if(0 === strpos($path, $base)) {
			return self::normalizePath($path);
		}
		return self::normalizePath(str_replace('\\', '/', realpath($root)) . '/' . $path);
	}

	public static function parseArgs($string) {
		$ret = [];
		foreach(explode('&', $string) as $chunk) {
			list($key, $val) = explode("=", $chunk);
			$ret[urldecode($key)] = urldecode($val);
		}
		return $ret;
	}

	public static function returnBytes($size_str) {
		switch(substr($size_str, -1)) {
			case 'M': case 'm': return (doubleval($size_str) * 1048576);
			case 'K': case 'k': return (doubleval($size_str) * 1024);
			case 'G': case 'g': return (doubleval($size_str) * 1073741824);
			default: return doubleval($size_str);
		}
	}

	public static function vnsprintf($format, $arguments) {
		$names = preg_match_all('/%\((.*?)\)/', $format, $matches, PREG_SET_ORDER);

		$values = array();
		foreach($matches as $match) {
			$values[] = $arguments[$match[1]];
		}

		$format = preg_replace('/%\((.*?)\)/', '%', $format);
		return vsprintf($format, $values);
	}

}

/**
 * Include code from memory and return output
 * 
 * @param string $string
 * @param array $context
 * @param mixed $object
 * @return string
 */
function includePhp($string, $context = [], $object = null) {
	$closure = \Closure::bind(function($_, $__ = []) {
			ob_start();
			extract($__);
			$result = eval('?>' . $_);
			$echo = ob_get_clean();
			if((false === $result) && preg_match('~Parse error~', $echo)) {
				throw new \ErrorException('Parse error');
			}
			return $echo;
		}, $object, $object);

	return $closure($string, $context);
}
