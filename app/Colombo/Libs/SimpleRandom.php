<?php
/**
 * Created by PhpStorm.
 * User: hocvt
 * Date: 8/7/17
 * Time: 10:29
 */

namespace App\Colombo\Libs;


class SimpleRandom {
	
	private $items = [];
	private $total = 0;
	
	
	/**
	 * SimpleRandom constructor.
	 *
	 * @param array $items \
	 * @param int|string $mode 1|100|number
	 */
	public function __construct(array $items = [], $mode = 'number') {
		$function = 'importInMode' . ucfirst($mode);
		$this->{$function}($items);
		$this->shuffleItems();
	}
	
	private function importInMode1($input = []){
		$result = [];
		$total = 0;
		foreach ($input as $k => $v){
			if (is_array($v)){
				$result[$v['key']] = ceil(floatval($v['value']) * 100);
			}else{
				$result[$k] = ceil(floatval($v) * 100);
			}
			$total += $result[$k];
		}
		if($total != 100){
			throw new \Exception("Tong gia tri khac 100%");
		}
		$this->total = $total;
		$this->items = $result;
	}
	
	private function importInMode100($input = []){
		$result = [];
		$total = 0;
		foreach ($input as $k => $v){
			if (is_array($v)){
				$result[$v['key']] = $v['value'];
			}else{
				$result[$k] = $v;
			}
			$total += $result[$k];
		}
		if($total != 100){
			throw new \Exception("Tong gia tri khac 100");
		}
		$this->total = $total;
		$this->items = $result;
	}
	
	private function importInModeNumber($input = []){
		$result = [];
		$total = 0;
		foreach ($input as $k => $v){
			if (is_array($v)){
				$result[$v['key']] = isset($v['value']) ? intval($v['value']) : 1;
			}elseif(is_numeric($k)){
				$result[$v] = 1;
			}else{
				$result[$k] = $v;
			}
			$total += $result[$k];
		}
		$this->total = $total;
		$this->items = $result;
	}
	
	private function shuffleItems(){
			
	}
	
	private function getKey($randInt){
		if($randInt > $this->total){
			$randInt = $randInt % $this->total;
		}
		foreach ($this->items as $k => $v){
			if($randInt < $v){
				return $k;
			}else{
				$randInt -= $v;
			}
		}
	}
	
	private function randomOne(){
		$randomInt = rand(0, $this->total - 1);
		return $this->getKey($randomInt);
	}
	
	public function getRandom($number = null){
		if(!$number){
			return $this->randomOne();
		}else{
			$result = [];
			for ($i = 0; $i<$number; $i++){
				$result[] = $this->randomOne();
			}
			return $result;
		}
	}
	
}