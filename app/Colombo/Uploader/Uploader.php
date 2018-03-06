<?php
/**
 * Created by PhpStorm.
 * User: conghoan
 * Date: 27/07/2017
 * Time: 08:35
 */

namespace App\Colombo\Uploader;


use App\Colombo\CliEcho;
use App\Colombo\DiskManager;
use App\Colombo\DocumentNameResolver;
use App\Colombo\NetworkChecker;
use App\Colombo\Uploader\Exceptions\CanNotReadFileUpload;
use App\Colombo\Uploader\Exceptions\DisconnectedException;
use App\Colombo\Uploader\Exceptions\EmptyTitleException;
use App\Colombo\Uploader\Exceptions\TimeoutException;
use App\Models\DownloadLink;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use League\Flysystem\Adapter\Local;
use League\Flysystem\FileNotFoundException;
use Mockery\Exception;

class Uploader
{
    private $host;
    private $token;
    private $uri;
    private $header;
    private $client;
    private $downloadLink;
    private $timeout;
    private $bot;
    function __construct($downloadLink)
    {
        if ($downloadLink instanceof DownloadLink){
            if (!empty($downloadLink->site)){
                $this->downloadLink = DownloadLink::with(['site' => function($query){
                    $query->select('id', 'country');
                }])
                    ->find($downloadLink);
            }else{
                $this->downloadLink = $downloadLink;
            }
        }else{
            $this->downloadLink = DownloadLink::with(['site' => function($query){
                $query->select('id', 'country');
            }])
                ->find($downloadLink);
        }
        $this->downloadLink->upload_status = 10;
        $this->downloadLink->save();
        $site = $this->downloadLink->site;
        if (!empty($site)){
            $this->host = config('crawler.uploader.'.$site->country.'.host');
            $this->token = config('crawler.uploader.'.$site->country.'.sapi');
            $this->uri = config('crawler.uploader.'.$site->country.'.uri_post');
            $this->bot = config('crawler.uploader.'.$site->country.'.bot');
            if (empty($this->host) || empty($this->token) || empty($this->uri)){
                echo 'Do not have config for uploader';
                die();
            }
        }else{
            echo 'Do not have site from download link';
        }

        $this->header = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->token,
        ];
        $this->timeout = config('crawler.uploader.timeout');
        $this->client = new Client([
            'base_uri' => $this->host,
            'headers' => $this->header,
            'timeout' => $this->timeout
        ]);
    }

    public function run($debug = false){
        $time_start = '';
        try {
            $this->checkFileUpload($this->downloadLink);
            $country = $this->downloadLink->site->country;
            $this->preUploadCheck();
            $config = config('crawler.name_file.special_keywords');
            $resolver = new DocumentNameResolver($config);
            $title = $resolver->handleTitle($country, $this->downloadLink->name,
                $this->downloadLink->link_text, $this->downloadLink->page_title);
            if (empty($title)){
                throw new EmptyTitleException('Link id =  '.$this->downloadLink->id.' do not have title');
            }
            $transformer = new Transform();
            $transformer->title = $title;
            $data = $transformer->getData($this->downloadLink);
            if($debug){
            	CliEcho::infonl("<pre>Sent : \n" . print_r($data, true) . "</pre>");
            }
            $time_start = microtime(true);
            $response = $this->client->post($this->uri, [
                'multipart' => $data
            ]);
            
            $rawResponse = $response->getBody()->getContents();
            
	        if($debug){
	        	CliEcho::infonl("Status code " . $response->getStatusCode());
		        CliEcho::infonl("<pre>Receive : \n" . print_r($rawResponse, true) . "</pre>");
	        }

            if ($response->getStatusCode() == 200) {
                $dataResponse = json_decode($rawResponse, true);
	            if($debug){
		            CliEcho::infonl("<pre>dataResponse : \n" . print_r($dataResponse, true) . "</pre>");
		            CliEcho::infonl("<pre>Receive : \n" . print_r($rawResponse, true) . "</pre>");
	            }
                if ($dataResponse['success'] == true) {
                    $dataInfo['uploaded_document_id'] = $dataResponse['document']['id'];
                    $dataInfo['uploaded_link'] = $this->miny_link($dataResponse['url']);
                    $dataInfo['uploaded_at'] = Carbon::now();
                    $dataInfo['upload_status'] = 1;
                    $dataInfo['uploaded_document_title'] = str_limit($title, 160);
	
	                $this->downloadLink->update($dataInfo);
	
	                if($debug){
		                CliEcho::infonl("<pre>dataInfo : \n" . print_r($dataInfo, true) . "</pre>");
	                }
	
	                return [
                        'success' => true,
                        'data' => $dataInfo
                    ];
                } elseif ($dataResponse['error_code'] == 444) {
                    $dataInfo['uploaded_document_id'] = $dataResponse['document']['id'];
                    $dataInfo['upload_status'] = -2;
                    $dataInfo['uploaded_at'] = Carbon::now();
                    $dataInfo['uploaded_link'] = $this->miny_link($dataResponse['url']);
	                $dataInfo['uploaded_document_title'] = str_limit(array_get($dataResponse, 'title'), 160);
	
	                $saved = $this->downloadLink->update($dataInfo);
	
	                if($debug){
		                CliEcho::infonl("<pre>dataInfo : \n" . print_r($dataInfo, true) . "</pre>");
		                CliEcho::infonl("saved : " . ($saved?"yes":"no"));
	                }
	
	                return [
                        'success' => false,
                        'duplicated' => true,
                        'url' => $dataResponse['url'],
                        'message' => $dataResponse['message'],
                        'data' => $dataInfo
                    ];
                } else {
                    $this->downloadLink->uploaded_at = Carbon::now();
                    $this->downloadLink->upload_status = -1;
                    $this->downloadLink->save();
                    \Log::error('Upload: ' . $dataResponse['message']);
                    return [
                        'success' => false,
                        'message' => $dataResponse['message']
                    ];
                }
            }
        }catch (RequestException $exception) {
            $stop = false;
            $time_end = microtime(true);
            $time = $time_end - $time_start;
            if ($exception->getCode() == 0) {
                if ($time >= $this->timeout) {
                    $stop = false;
                    $this->downloadLink->upload_status = -10;
                } else {
                    if (!NetworkChecker::has()){
                        $stop = true;
                        $this->downloadLink->upload_status = 0;
                    }
                }
            }else{
                $this->downloadLink->upload_status = -1;
            }
            $this->downloadLink->uploaded_at = Carbon::now();

            $this->downloadLink->save();
            return [
                'success' => false,
                'message' => 'Upload loi ket noi: ' . $exception->getMessage(),
                'stop' => $stop
            ];
        }catch(FileNotFoundException $ex){
	        $this->downloadLink->upload_status = -1;
            $this->downloadLink->uploaded_at = Carbon::now();
	        $this->downloadLink->save();
	        return [
		        'success' => false,
		        'message' => $ex->getMessage(),
		        'stop' => false
	        ];
        }catch (CanNotReadFileUpload $exception) {
            $this->downloadLink->upload_status = 0;
            $this->downloadLink->save();
            return [
                'success' => false,
                'message' => $exception->getMessage(),
                'stop' => true
            ];
        }catch (EmptyTitleException $exception){
            $this->downloadLink->upload_status = -20;
            $this->downloadLink->uploaded_at = Carbon::now();
            $this->downloadLink->save();
            return [
                'success' => false,
                'message' => $exception->getMessage()
            ];
        }catch (\Exception $exception){
        	if($exception->getCode() !== 1000){
		        $this->downloadLink->upload_status = -1;
		        $this->downloadLink->uploaded_at = Carbon::now();
		        $this->downloadLink->save();
	        }
            \Log::error('Loi upload ' . $exception->getMessage());
            return [
                'success' => false,
                'message' => $exception->getMessage()
            ];
        }
    }

    /**
     * @param $downloadLink DownloadLink
     */
    public function checkFileUpload(DownloadLink $downloadLink){
        if ($downloadLink->status != 1){
            throw new Exception('File id = '. $downloadLink->id
                .' not enough condition to upload : status = ' . $downloadLink->status);
        }
        if ($downloadLink->mime_type == 0){
            throw new Exception('File id = '. $downloadLink->id
                .' not enough condition to upload : type = 0. It is archive file');
        }
        if (empty($downloadLink->site->id)){
            throw new Exception('File id = '. $downloadLink->id
                .' do not have site_id');
        }
        return true;
    }
    
    private function miny_link($link){
	    return str_limit($link, 150, '.html');
    }
    
    private function preUploadCheck(){
    	$country = $this->downloadLink->site->country;
    	try{
		    if($checker_class = config('crawler.uploader.' . $country .  '.preChecker')){
		    	/** @var PreChecker $checker */
			    $checker = new $checker_class;
			    $disk = DiskManager::get($this->downloadLink->disk);
			    $adapter = $disk->getAdapter();
			    if($adapter instanceof Local){
			    	$path = $adapter->applyPathPrefix($this->downloadLink->path);
			    	$hash = $checker->getHash($path);
			    	$result = $checker->check($hash);
			    	if($result['success'] == true){
			    		return;
				    }
				    if($result['error_code'] == 444){
					    $dataInfo['uploaded_document_id'] = array_get($result, 'data.id', 1000);
					    $dataInfo['upload_status'] = -2;
					    $dataInfo['uploaded_at'] = Carbon::now();
					    $dataInfo['uploaded_link'] = $this->miny_link(array_get($result, 'data.url', ''));
					    $dataInfo['uploaded_document_title'] = str_limit(array_get($result, 'data.title', 'Duplicated'));
					
					    $saved = $this->downloadLink->update($dataInfo);
					    if($saved){
						    throw new \Exception("Tài liệu đã có trên hệ thống " . array_get($result, 'data.url', ''), 1000);
					    }
				    }
			    }
		    }
	    }catch (\Exception $ex){
    	    if($ex->getCode() == 1000){
		        throw $ex;
	        }
	    }
    }
}