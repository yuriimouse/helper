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

use Wtf\Helper\Complex;

/**
 * Abstract static class provide a lot of functions
 * for HTML formatting.
 *
 * @author Iurii Prudius <hardwork.mouse@gmail.com>
 */
abstract class Html {

	/**
	 * Produce for each element of array formatted value by template using markers for keys.
	 * 
	 * First or once template used for scalar elements, second - for complex elements, third - glue for complex.
	 * Named marker are used when keys is same.
	 *
	 *  @param array $arr 
	 *  @parameter mixed $templates Array or String
	 *  @param array $marker 
	 *  @return string[]
	 */
	public static function show($arr, $templates, $marker = null) {
		$arr = (array) $arr;
		$marker = (array) $marker;
		$templates = (array) $templates;
		$scalar = array_shift($templates);
		$complex = array_shift($templates);
		$glue = array_shift($templates);
		foreach($arr as $key => $item) {
			if(is_scalar($item)) {
				$arr[$key] = sprintf($scalar, $key, $item, empty($marker[$key]) ? '' : $marker[$key]);
			} else {
				$branch = implode($glue, self::show($item, [$scalar, $complex, $glue], empty($marker[$key]) ? $marker : $marker[$key]));
				$arr[$key] = sprintf($complex? : $scalar, $key, $branch, empty($marker[$key]) ? '' : $marker[$key]);
			}
		}
		return $arr;
	}

	/**
	 * Produce for each element of array string '$key="$value"'
	 *
	 * @param array $arr 
	 * @return string[]
	 */
	public static function showAttrs($arr) {
		return array_values(self::show($arr, '%1$s="%2$s"'));
	}

	/**
	 * Produce tag 'option' for each element of array
	 *
	 * @param array $arr 
	 * @param mixed $selected 
	 * @return string[]
	 */
	public static function showOptions($arr, $selected = null) {
		return array_values(self::show($arr, array(
				'<option value="%1$s"%3$s>%2$s</option>',
				'<optgroup label="%1$s">%2$s</optgroup>'), is_array($selected) ? $selected : (is_string($selected) ? array(
						$selected => ' selected="selected"') : null) ));
	}

	/**
	 * Produce tag 'input'
	 *
	 * @param array $attr 
	 * @param string $type 
	 * @param string $name 
	 * @param string $value
	 * @return string[]
	 */
	public static function showInput($attr = null, $type = '', $name = '', $value = '') {
		$ret = array();
		if($div = Complex::eliminate($attr, 'div')) {
			$ret[] = '<div ' . implode(' ', self::showAttrs($div)) . '>';
		}
		$value = Complex::eliminate($attr, 'value', $value);
		$type = Complex::eliminate($attr, 'type', $type ? $type : (is_bool($value) ? 'checkbox' : 'text'));
		$name = Complex::eliminate($attr, 'name', $name ? $name : ($type . '[]'));
		if($label = Complex::eliminate($attr, 'label')) {
			$ret[] = '<label>' . $label;
		}
		switch($type) {
			case 'select':
				$selected = Complex::eliminate($attr, 'selected');
				$ret[] = "<select name=\"{$name}\" " . implode(' ', self::showAttrs($attr)) . '>';
				$ret[] = implode(PHP_EOL, self::showOptions((array) $value, $selected));
				$ret[] = '</select>';
				break;
			case 'textarea':
				$ret[] = '<textarea name="' . $name . '" ' . implode(' ', self::showAttrs($attr)) . '>';
				$ret[] = htmlspecialchars(implode(PHP_EOL, (array) $value));
				$ret[] = '</textarea>';
				break;
			case 'image':
				$ret[] = "<button name=\"{$name}\" " . implode(' ', self::showAttrs($attr)) . '>';
				$ret[] = "<img src=\"{$value}\" />";
				$ret[] = '</button>';
				break;
			default:
				$ret[] = "<input type=\"$type\" name=\"$name\"";
				if($attr) {
					$ret[] = implode(' ', self::showAttrs($attr));
				}
				$ret[] = 'value="' . htmlspecialchars($value) . '" />';
		}
		if($label) {
			$ret[] = '</label>';
		}
		if($div) {
			$ret[] = '</div>';
		}
		return $ret;
	}

	/**
	 * Produce tree as specified in attribute 'as' (or 'ul') from array
	 *
	 * @param array $tree xD 
	 * @param string $rootas 
	 * @return string[]
	 */
	public static function showTree($tree, $rootas = 'ul') {
		$ret = [];
		if($atree = Complex::obj2arr($tree)) {
			$ret = ["<{$rootas}>"];
			switch($rootas) {
				case 'ul':
				case 'ol': $as = 'li';
					break;
				case 'div': $as = 'div';
					break;
				default: $as = 'span';
			}
			foreach($atree as $val) {
				if(is_scalar($val)) {
					$ret[] = "<{$as}>{$val}</{$as}>";
				} else {
					$ret[] = "<{$as}>" . implode(PHP_EOL, self::showTree($val, $rootas)) . "</{$as}>";
				}
			}
			$ret[] = "</{$rootas}>";
		}
		return $ret;
	}

	/**
	 * Produce menu as 'ul' from array
	 *
	 * @param array $tree xD 
	 * @param array $attr 
	 * @return string[]
	 */
	public static function showMenu($tree, $attr = null) {
		$ret = array();
		if($menu = Complex::obj2arr($tree)) {
			$ret[] = '<ul ' . implode(' ', self::showAttrs($attr)) . '>';
			foreach($menu as $key => $val) {
				if(is_array($val)) {
					$ret[] = '<li>' . implode(PHP_EOL, self::showMenu($val)) . '</li>';
				} else {
					$ret[] = "<li><a href=\"{$val}\">{$key}</a></li>";
				}
			}
			$ret[] = '</ul>';
		}
		return $ret;
	}

}
