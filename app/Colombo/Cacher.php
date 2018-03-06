<?php
/**
 * Created by PhpStorm.
 * User: hocvt
 * Date: 7/7/17
 * Time: 13:35
 */

namespace App\Colombo;


use App\User;

class Cacher {
	
	
	public static function users_list($refresh = false){
		if($refresh){
			\Cache::forget('user_list');
		}
		return \Cache::remember('user_list', 100, function(){
			return User::all()->mapWithKeys(function ($user){
				return [$user->id => $user->name];
			})->all();
		});
	}
}