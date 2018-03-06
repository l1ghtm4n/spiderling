<?php
/**
 * Created by PhpStorm.
 * User: hocvt
 * Date: 9/19/16
 * Time: 14:21
 */

namespace App\Colombo;


use App\LaFly\LaFlyAdapter;
use League\Flysystem\Filesystem;

class DiskManager {
	
	public static $last_error;
	
	/**
	 * @param array|string $disks
	 *
	 * @param string $mode write|read|cdn default:srite
	 *
	 * @return string
	 */
	public static function getBestDisk( $disks , $mode = "write") {
		if(!is_array($disks)){
			$disks = explode(",", $disks);
		}
		return $disks[0];
	}
	
	/**
	 * @param $disk
	 *
	 * @return Filesystem
	 */
	public static function get( $disk ) {
		$disk = self::getBestDisk($disk);
		$clutter = config('app.clutter_name', false);
		$config = config('flysystem.connections.' . $disk, []);
		
		if($config['driver'] == 'lafly'){
			return self::makeLaFly($disk, $config);
		}
		
		if($clutter && isset($config[$clutter])){
			return app('flysystem')->connection($disk.".".$clutter);
		}else{
			return app('flysystem')->connection($disk);
		}
		
	}
	
	private static $laflySystems;
	
	private static function makeLaFly($disk, $config){
		if(!isset(self::$laflySystems[$disk])){
			$flyAdapter = new LaFlyAdapter($config, $config['connection']);
			$filesystem = new Filesystem($flyAdapter);
			self::$laflySystems[$disk] = $filesystem;
		}
		return self::$laflySystems[$disk];
	}
	
	/**
	 * Delete from all disk
	 *
	 * @param $disks
	 * @param $path
	 * @param bool $file_only only delete file
	 *
	 * @return array
	 */
	public static function delete($disks, $path, $file_only = true){
		if(!is_array($disks)){
			$disks = explode(",", $disks);
		}
		$result = [];
		$deleted = [];
		$remain = [];
		$success = true;
		foreach ($disks as $disk){
			$result[$disk] = [
				"success" => self::deleteFromDisk($disk, $path, $file_only),
				"file" => $path,
			];
			if($result[$disk]['success'] === false){
				$remain[] = $disk;
				$success = false;
			}else{
				$deleted[] = $disk;
			}
		}
		
		$result['success'] = $success;
		$result['deleted'] = implode(",", $deleted);
		$result['remain'] = implode(",", $remain);
		
		return $result;
		
	}
	
	/**
	 * Copy from one disk to some disks
	 *
	 * @param array $from [disk, path]
	 * @param array $to [[disk,path],[disk,path],...]
	 */
	public function copy($from, $to){
		
		
	}
	
	private static function deleteFromDisk($disk, $path, $file_only){
		$_disk = DiskManager::get($disk);
		if(!$_disk->has($path)){
			return null;
		}
		$file_meta = $_disk->getMetadata($path);
		if($file_meta['type'] == 'dir'){
			if($file_only){
				return false;
			}else{
				\Log::warning("Deleting folder " . $disk . "::" . $path);
				return $_disk->deleteDir($path);
			}
		}else{
			return $_disk->delete($path);
		}
	}
	
}