<?php

/*
 * Copyright (C) 2016 Iurii Prudius <hardwork.mouse@gmail.com>
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
 * Abstract static class provide a lot of functions
 * for manipulations with the arrays and objects.
 *
 * @author Iurii Prudius <hardwork.mouse@gmail.com>
 */
abstract class Complex {

	/**
	 * Exclude the blacklisted keys
	 * 
	 * @param array $array Source array
	 * @param array $black Array of the blacklisted keys
	 * @return array Cleared array
	 */
	public static function except($array, $black) {
		return array_diff_key((array) $array, array_flip((array) $black));
	}

	/**
	 * Include the whitelisted keys only
	 * 
	 * @param array $array Source array
	 * @param array $white Array of the whitelisted keys
	 * @return array Cleared array
	 */
	public static function only($array, $white) {
		return array_intersect_key((array) $array, array_flip((array) $white));
	}

	/**
	 * Object to Array
	 *
	 *  @param object $obj
	 *  @param array|boolean $remove Remove the array specified keys or '@attributes' if true
	 *  @return array
	 */
	public static function obj2arr($obj, $remove = null) {
		if(is_object($obj)) {
			$elem = (array) $obj;
		} else {
			$elem = $obj;
		}

		if($remove && is_bool($remove)) {
			$remove = ['@attributes'];
		}
		if(is_array($elem)) {
			return array_map(__METHOD__, $remove ? self::except($elem, $remove) : $elem);
		}
		return $elem;
	}

	/**
	 * Array to Object
	 *
	 * @param array $arr  
	 * @return object
	 */
	public static function arr2obj($arr) {
		return json_decode(json_encode($arr));
	}

	/**
	 * Array to .INI formatted text
	 *
	 * @parameter array $a  
	 * @parameter array $parent  
	 * @return string
	 */
	public static function arr2ini($a, array $parent = []) {
		$out = array();
		$a = (array) $a;
		uasort($a, function($a, $b) {
			if(is_scalar($a)) {
				if(!is_scalar($b))
					return -1;
			} else {
				if(is_scalar($b))
					return 1;
			}
			return 0;
		});
		foreach($a as $k => $v) {
			if(is_null($v)) {
				$out[] = "$k=null";
			} elseif(is_bool($v)) {
				$out[] = "$k=" . ($v ? 'on' : 'off');
			} elseif($v && !is_scalar($v)) {
				$parent[] = $k;
				$out[] = '[' . join('.', $parent) . ']';
				$out[] = self::arr2ini($v, $parent);
			} else {
				$out[] = "$k=$v";
			}
		}
		return implode(PHP_EOL, $out);
	}

	/**
	 * .INI formatted text to Array
	 *
	 * @param string $ini
	 * @return array
	 */
	public static function ini2arr($ini) {
		$arr = parse_ini_string($ini, true, INI_SCANNER_TYPED);
		$complex = array_filter($arr, function($key) {
			return false !== strpos($key, '.');
		}, ARRAY_FILTER_USE_KEY);
		foreach($complex as $key => $value) {
			$parts = array_reverse(explode('.', $key));
			$branch = array_reduce($parts, function($carry, $item) {
				return [$item => $carry];
			}, $value);
			$arr = array_merge_recursive($arr, $branch);
			unset($arr[$key]);
		}
		return $arr;
	}

	/**
	 * Convert array to XML
	 * 
	 * @param array $data
	 * @param \SimpleXMLElement $root
	 * @param \SimpleXMLElement $parent
	 * @return \SimpleXMLElement
	 */
	public static function arr2xml($data, $root = null, $parent = null) {
		if(null === $root) {
			$root = new \SimpleXMLElement('<?xml version="1.0" standalone="yes"?><xml></xml>');
		}

		if(is_array($data)) {
			$serial = null;
			foreach($data as $k => $v) {
				if(is_numeric($k)) {
					if($parent) {
						$serial = self::arr2xml($v, $serial ? $parent->addChild($root->getName()) : $root, $parent);
					} else {
						self::arr2xml($v, $root->addChild('node'), $root);
					}
				} elseif('@' === $k{0}) {
					$root->addAttribute(substr($k, 1), $v);
				} else {
					self::arr2xml($v, $root->addChild($k), $root);
				}
			}
		} elseif(!empty($data)) {
			$root[0] = htmlentities($data, ENT_DISALLOWED || ENT_XML1);
		}
		return $root;
	}

	public static function xml2arr($xml) {
		$arr = (array)$xml;
		var_export($arr);

		$out = [];
		foreach($arr as $k => $v) {
			if('@attributes' === $k) {
				foreach($v as $attr => $set) {
					$out["@{$attr}"] = $set;
				}
			} elseif(!is_scalar($v)) {
				$out[$k] = self::xml2arr($v);
			} elseif(is_string($v) && is_numeric($v)) {
				$out[$k] = false === strpos($v, '.') ? intval($v) : doubleval($v);
			} else {
				$out[$k] = $v;
			}
		}
		return $out;
	}

	/**
	 * Get routed value from array
	 * 
	 * @param string $from
	 * @param string $name
	 * @param mixed $dflt
	 */
	public static function extract($from, $name, $dflt = null) {
		$src = $from;
		$parts = preg_split('~[./]~', $name);
		foreach($parts as $sub) {
			if(isset($src[$sub])) {
				$src = $src[$sub];
			} else {
				return $dflt;
			}
		}
		return $src;
	}

	/**
	 * Get named value from array or default
	 *
	 * @param mixed $from Array or Object
	 * @param string $key 
	 * @param mixed $dflt Default value
	 * @return mixed
	 */
	public static function get($from, $key, $dflt = null) {
		$afrom = (array) $from;
		if(isset($afrom[$key])) {
			return $afrom[$key];
		}
		return $dflt;
	}

	/**
	 * Get named value from array or default and unset it
	 *
	 * @param mixed $from Array or Object
	 * @param string $key 
	 * @param mixed $dflt
	 * @return mixed
	 */
	public static function eliminate(&$from, $key, $dflt = null) {
		$afrom = (array) $from;
		if(isset($afrom[$key])) {
			$ret = $afrom[$key];
			unset($from[$key]);
			return $ret;
		}
		return $dflt;
	}

	/**
	 * Array to `key`=`value` array
	 *
	 * @parameter array $a  
	 * @parameter array $only  
	 * @return array
	 */
	public static function arr2attr(array $a, array $only = null) {
		$arr = $only ? array_intersect_key($a, array_flip($only)) : $a;
		array_walk($arr, function(&$v, $k) {
			if(is_null($v)) {
				$v = $k;
			} elseif(is_bool($v)) {
				$v = $k . '="' . ($v ? 'on' : 'off') . '"';
			} else {
				$v = "{$k}=\"{$v}\"";
			}
		});
		return array_values($arr);
	}

	/**
	 * Get attributes of XML node as array.
	 * 
	 * @param mixed $el DOMNode or SimpleXMLElement 
	 * @return array|null
	 */
	public static function attr2arr($el) {
		if($el instanceof \DOMElement) {
			if(!$el->attributes) {
				return [];
			}
			$xml = simplexml_import_dom($el);
		} elseif(!($xml = $el) || !($xml instanceof \SimpleXMLElement)) {
			return null;
		}
		$atts = (array) $xml->attributes();
		return isset($atts['@attributes']) ? $atts['@attributes'] : [];
	}

}
