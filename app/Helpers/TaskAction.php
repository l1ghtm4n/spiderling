<?php
/**
 * Created by PhpStorm.
 * User: hocvt
 * Date: 7/5/17
 * Time: 12:05
 */

namespace App\Helpers;


use App\Models\Site;
use App\Models\Task;

class TaskAction {
	
	private $site;
	
	private $tasks_status = [
		'crawl' => [
			'status' => null,
			'status_text' => 'N/A',
			'icon' => 'fa-bug',
		],
		'download_link' => [
			'status' => null,
			'status_text' => 'N/A',
			'icon' => 'fa-download',
		],
		'upload' => [
			'status' => null,
			'status_text' => 'N/A',
			'icon' => 'fa-upload',
		]
	];
	
	public function __construct(Site $site, $manage = false) {
		$this->site = $site;
		$this->manage = $manage;
		/** @var Task $task */
		foreach ($this->site->tasks as $task){
			$this->tasks_status[$task->name]['status'] = $task->status;
			$this->tasks_status[$task->name]['status_text'] = $task->status_label;
		}
	}
	
	public function render(){
		$out = "";
		foreach ($this->tasks_status as $name => $task){
		    if(empty($task['icon']))dd($name, $task);
			$out .= "<div class='task-controls task_" . $name . "_controls'>";
			$out .= "<i class='p-1 fa " . $task['icon'] . " " . $this->status_to_class($task['status']) . "' title='" . $task['status_text'] . "'></i>";
			$out .= $task['status_text'];
			if($this->manage){
				$out .= " [ ";
				$out .= $this->buttons($name, $task['status']);
				$out .= " ] ";
			}
			$out .= "</div>";
		}
		return $out;
	}
	
	public function status_to_class($status){
		if($status === null){
			return "text-muted";
		}
		switch ($status){
			case Task::STATUS_WAIT :
			case Task::STATUS_WAIT_RERUN :
				return "text-muted";
			case Task::STATUS_DONE :
				return "text-success";
			case Task::STATUS_RUNNING :
				return "text-warning";
			case Task::STATUS_FAIL :
			case Task::STATUS_STOP :
				return "text-danger";
			default :
				return "text-muted";
		}
	}
	public function buttons($task, $status){
		$buttons = [
			'play' => "<i class='fa fa-play text-success'></i>",
			'stop' => "<i class='fa fa-stop text-warning'></i>",
			'refresh' => "<i class='fa fa-refresh text-danger'></i>",
		];
		switch ($task){
			case 'download_link':
			case 'upload':
				$buttons = array_except($buttons, ['refresh']);
		}
		switch ($status){
			case Task::STATUS_WAIT :
			case Task::STATUS_WAIT_RERUN :
			case Task::STATUS_DONE :
			case Task::STATUS_RUNNING :
			case Task::STATUS_FAIL :
			case Task::STATUS_STOP :
			default :
		}
		return implode(" ", $buttons);
	}
	
	/**
	 * @param Site $site
	 *
	 * @return string
	 */
	public static function controls(Site $site, $manage = false){
		$instance = new self($site, $manage);
		return $instance->render();
	}
}