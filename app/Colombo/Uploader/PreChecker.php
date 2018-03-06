<?php
/**
 * Created by PhpStorm.
 * User: hocvt
 * Date: 10/23/17
 * Time: 13:20
 * SUCCESS
array:4 [
	"success" => true
	"message" => "Unknown error"
	"error_code" => 0
	"data" => []
]
 * FAIL
array:4 [
	"success" => false
	"message" => "Hash trung voi tai lieu da co"
	"error_code" => 444
	"data" => array:3 [
		"url" => "https://123doc.org/document/500002-ssss.htm"
		"title" => "Quản lý xuất khẩu lao động tại Trung tâm phát triển việc làm"
		"id" => 1000
	]
]
 
 */

namespace App\Colombo\Uploader;


interface PreChecker {
	public function check($hash);
	public function getHash($file);
}