<?php
/**
 * Created by PhpStorm.
 * User: conghoan
 * Date: 24/07/2017
 * Time: 11:40
 */

namespace App\Colombo\Download\Contract;


use App\Colombo\DocumentNameResolver;
use App\Colombo\Download\Exceptions\CanNotSaveTmp;
use App\Colombo\Download\Exceptions\DisconnectedException;
use App\Colombo\Download\Exceptions\LinkDieException;
use App\Colombo\Download\Exceptions\TimeoutException;
use App\Colombo\Download\Exceptions\TmpLinkDieException;
use App\Colombo\NetworkChecker;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Response;

abstract class DownloaderAbstract
{
    protected $client;
    protected $timeout;
    function __construct()
    {
        $this->timeout = config('crawler.download.timeout');
        $this->client = new Client([
            'timeout' => $this->timeout,
            'headers' => [
                'User-Agent' => config('crawler.agent')
            ]
        ]);
    }

    /**
     * Tạo file tạm tmp để download
     *
     * @return bool|string
     */
    protected function newTmp()
    {
        try{
            $path_tmp = config('crawler.tmp.path');
            $tmpFile = tempnam($path_tmp,'downloadable_tmp_');
            return $tmpFile;
        }catch (\Exception $exception){
            \Log::error('TMP DIR ERROR: ' . $exception->getMessage());
            throw new CanNotSaveTmp($exception->getMessage());
        }
    }

    /**
     * Lưu file down xuống vào file tạm tmp
     * @param $link
     * @return array
     * @throws CanNotSaveTmp
     * @throws DisconnectedException
     * @throws LinkDieException
     * @throws TimeoutException
     * @throws TmpLinkDieException
     */
    protected function fileToTmp($link){
        if (empty($link)){
            throw new CanNotSaveTmp('Link: ' . $link.' empty');
        }
        $tmpFile = $this->newTmp();
        try {
            $response = $this->client->request('get', $link, [
                'sink' => $tmpFile
            ]);
            \Log::info($response->getHeaders());
            $data = $this->getInfoFile($link, $response);
            return [
                'success' => true,
                'data' => $data,
                'tmpFile' => $tmpFile
            ];
        }catch (RequestException $requestException){
            if ($requestException->getCode() == 0){
                $error = $requestException->getHandlerContext();
                $errno = $error['errno'];
                if ($errno == 28) {
                    throw new TimeoutException('Timeout: ' . $this->timeout);
                }elseif (!NetworkChecker::has()){
                    throw new DisconnectedException('No have internet');
                }else{
                    throw new LinkDieException('Error link died : ' . $link);
                }
            }elseif ($requestException->getCode() == 404){
                throw new LinkDieException('Error 404 link died : ' . $link);
            }else{
                throw new TmpLinkDieException('Error tmp link died : ' . $link);
            }

        }catch (\Exception $exception){
            \Log::alert('Error request to download link ' . $link . ''.
                $exception->getMessage()
            );
            throw new CanNotSaveTmp($exception->getMessage());
        }
    }

    /**
     * @param $response Response
     */
    protected function getInfoFile($link, $response){
        $data = [];
        $name_file = '';
        $disposition = $response->getHeader('Content-Disposition');
        if (!empty($disposition)){
           $name_file = DocumentNameResolver::fromContentDisposition($disposition[0]);
            $data['name'] = $name_file;
        }
        if (empty($name_file)){
            $name_file = DocumentNameResolver::fromUrl($link);
            if ($name_file != false){
                $data['name'] = $name_file;
            }
        }
        return $data;
    }
    abstract function run($link);
}