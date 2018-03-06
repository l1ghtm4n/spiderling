<?php
/**
 * Created by PhpStorm.
 * User: conghoan
 * Date: 18/10/2016
 * Time: 15:36
 */

namespace App\Colombo\Uploader;


use App\Colombo\DocumentNameResolver;
use App\Colombo\Uploader\Exceptions\CanNotReadFileUpload;
use App\Colombo\Uploader\Exceptions\EmptyTitleException;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\Filesystem;

class Transform
{
    public $title = '';

    public function convertDataPost($documentInput){
	
    	/** @var Filesystem $disk */
	    $disk = \Flysystem::connection($documentInput->disk);
	    if(!$disk->has($documentInput->path)){
		    throw new FileNotFoundException($documentInput->path);
	    }
        try{
            $content = $disk->readStream($documentInput->path);
            if (empty($documentInput->site->language)){
                $lang = $documentInput->site->country;
            }else{
                $lang = $documentInput->site->language;
            }
            $documentOutput = [
                [
                    'name' => 'title',
                    'contents' => $this->title
                ],
                [
                    'name' => 'source_url',
                    'contents' => $documentInput->page_link
                ],
                [
                    'name' => 'single_document',
                    'contents' => $content
                ],
                [
                    'name' => 'language',
                    'contents' => $lang
                ],
                [
                    'name' => 'country',
                    'contents' => strtolower($documentInput->site->country)
                ],
                [
                    'name' => 'bot',
                    'contents' => config('crawler.uploader.'
                        .$documentInput->site->country.'.bot')
                ]
            ];
            return $documentOutput;
        }catch (\Exception $exception){
            \Log::error("Transform error " . $exception->getMessage());
            throw new CanNotReadFileUpload($exception->getMessage());
        }
    }
    
    public function getData($documentInput){
        $documentOutput = $this->convertDataPost($documentInput);
        return $documentOutput;
    }
}