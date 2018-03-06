<?php
/**
 * Created by PhpStorm.
 * User: hocvt
 * Date: 7/1/17
 * Time: 08:55
 */

namespace App\Colombo;


use Illuminate\Contracts\Pagination\Paginator;

class PageHelper {
	
	public static function sortLink(Paginator $paginator, $field, $title = '', $options = [], $asc = '&uarr;', $desc = '&darr;', $sort_key = 'sort'){
		$sorting = \Request::query($sort_key);
		$direct = false;
		$sorting = preg_replace_callback('/(^|,)(\-?)(' . $field . ')($|,)/', function($matches) use(&$direct){
			$direct = $matches[2] == "-" ? "" : "-";
			return $matches[1] . $direct . $matches[3] . $matches[4];
		}, $sorting);
		if(array_get($options, "combined", false) === false){
			$sorting = $direct . $field;
		}else{
			unset($options['combined']);
		}
		$title = $title ? $title : studly_case($field);
		if($direct !== false){
			$title .= " ";
			$title .= $direct === '-' ? $asc : $desc;
		}
		if(array_has($options, "filtered")){
			$additions = array_merge([$sort_key => $sorting], (array)$options['filtered']);
			unset($options['filtered']);
		}else{
			$additions = [$sort_key => $sorting];
		}
		$cloned = clone $paginator;
		$url = $cloned->appends($additions)->url(1);
		$escape = !strpos($title, "</");
		return \Html::link($url, $title, $options, null, $escape);
	}
	
	public static function getSorting($default = [], $sort_key = 'sort', $only = []){
		$sorting = [];
		$sorted = explode(",", \Request::query($sort_key));
		foreach ($sorted as $sort){
			if(!$sort){break;}
			if(strpos($sort, "-") === 0){
				$sorting[substr($sort, 1)] = "desc";
			}else{
				$sorting[$sort] = "asc";
			}
		}
		if($only){
			$sorting = array_only($sorting, $only);
		}
		if(empty($sorting)){
			$sorting = $default;
		}
		return $sorting;
	}
	
	public static function getSort($sort_key = 'sort'){
		return [$sort_key => \Request::query($sort_key)];
	}
	
}