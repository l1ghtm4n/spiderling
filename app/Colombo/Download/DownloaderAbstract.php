<?php
/**
 * Created by PhpStorm.
 * User: conghoan
 * Date: 18/07/2017
 * Time: 09:14
 */

namespace App\Colombo\Download;


use App\Colombo\Download\Exceptions\CanNotReDownloadException;
use App\Colombo\Download\Exceptions\FilterLinkException;
use App\Helpers\MimeTypeHelper;
use App\Models\DownloadLink;
use GuzzleHttp\Client;
use League\Flysystem\AdapterInterface;
use League\Flysystem\Filesystem;

abstract class DownloaderAbstract implements DownloaderInterface
{
    protected $maxSizeFile;
    protected $maxSizeArchive;
    protected $client;
    /** @var DownloadLink */
    protected $downloadLink;
    protected $disk_name;
    protected $dataInfo = [];
    protected $filtered;
    protected $force = false;

    function __construct($downloadLink, $force)
    {
        $this->client = new Client([
            'timeout' => config('crawler.download.timeout', 480),
            'headers' => [
                'User-Agent' => config('crawler.agent')
            ]
        ]);
        if ($downloadLink instanceof DownloadLink) {
            $this->downloadLink = $downloadLink;
        } else {
            $this->downloadLink = DownloadLink::find($downloadLink);
        }
        /* Change from Mb to bytes */
        $this->maxSizeFile = config('crawler.download.size.file') * 1024 * 1024;
        $this->maxSizeArchive = config('crawler.download.size.archive') * 1024 * 1024;
        $this->force = $force;
    }

    /**
     * Kiểm tra loại link này có đúng với loại class download không
     * @throws FilterLinkException
     */
    protected function checkFilterLink(){
        if ($this->downloadLink->filtered != $this->filtered){
            throw new FilterLinkException($this->filtered);
        }
    }

    /**
     * Kiểm tra link được phép download lại không
     * @throws CanNotReDownloadException
     */
    protected function checkRedownload(){
        if (!$this->force){
            if ($this->downloadLink->status == 1){
                throw new CanNotReDownloadException($this->downloadLink->id);
            }
        }
    }

    /**
     * Tạo đường dẫn cho file theo định dạng đường dẫn Y/m_d/file
     *
     * @param string $subfix
     * @param string $prefix
     * @return false|string
     */
    protected function makePath($subfix = '', $prefix = '')
    {
        $path = '';
        if (!empty($prefix)) {
            $path .= $prefix . '/';
        } else {
            $path .= '';
        }
        $path .= date('Y/m_d');
        if (!empty($subfix)) {
            $path .= '/' . $subfix;
        }
        return $path;
    }

    /**
     * Tạo file tạm tmp để download
     *
     * @return bool|string
     */
    protected function newTmp()
    {
        $path_tmp = config('crawler.tmp.path');
        $tmpFile = tempnam($path_tmp, 'downloadable_tmp_');
        return $tmpFile;
    }

    /**
     * Lưu thông tin lấy được, trạng thái vào csdl
     *
     * @param $data
     * @return bool
     */
    protected function saveInfo($data)
    {
        try {
            $this->downloadLink->update($data);
            return true;
        } catch (\Exception $exception) {
            \Log::error("Save info error " . $exception->getMessage());
            return false;
        }

    }

    /**
     * Kiểm tra các điều kiện của file
     * @param $mimeType
     * @param $size
     * @return array
     */
    protected function checkConditionFile($mimeType, $size){
        if (MimeTypeHelper::is_auto_download($mimeType)) {
            if ($size > $this->maxSizeFile) {
                return [
                    'success' => false,
                    'message' => 'limit max file : ' . $this->maxSizeFile
                ];
            }
        } else {
            return [
                'success' => false,
                'message' => 'is not auto download'
            ];
        }
        return [
            'success' => true
        ];
    }

    /**
     * Kiểm tra điều kiện của file nén
     * @param $mimType
     * @param $size
     * @return array
     */
    protected function checkConditionArchive($mimType, $size){
        if ($size > $this->maxSizeArchive) {
            return [
                'success' => false,
                'message' => 'limit max file Archive : ' . $this->maxSizeArchive
            ];
        }
        return [
            'success' => true
        ];
    }
    protected function checkConditions($mimeType, $size){
        $ext = MimeTypeHelper::ext_from_mime($mimeType);
        if (MimeTypeHelper::isArchive($mimeType)) {
            $checkArchive = $this->checkConditionArchive($mimeType, $size);
            if ($checkArchive['success']){
                $this->dataInfo['mime_type'] = 0;
            }else{
                return $checkArchive;
            }
        } else {
            $checkFile = $this->checkConditionFile($mimeType, $size);
            if (!$checkFile['success']){
                return $checkFile;
            }
            $this->dataInfo['mime_type'] = config('crawler.document.mime_type.' . $ext);
        }
        return [
            'success' => true
        ];
    }
    /**
     * Lưu file down xuống vào file tạm tmp
     * @param $url
     * @return array
     */
    protected function fileToTmp($url){
        $data = [];
        if (empty($url)){
            return [
                'success' => false,
                'message' => 'url empty'
            ];
        }
        $tmpFile = $this->newTmp();
        try{
            $response = $this->client->request('get', $url, [
                'sink' => $tmpFile
            ]);
            $data['size'] = $response->getHeader('Content-Length')[0];
            $contentType = $response->getHeader('Content-Type');
            $data['mime_type'] = $contentType[0];
            return [
                'success' => true,
                'tmpFile' => $tmpFile,
                'data' => $data
            ];
        }catch (\Exception $exception){
            return [
                'success' => false,
                'tmpFile' => $tmpFile,
                'message' => $exception->getMessage()
            ];
        }
    }

    /**
     * Lưu file tải xuống vào Flysystem
     *
     * @param $tmpFile
     * @param $ext
     * @param bool $overwrite
     * @return array
     * @internal param $pathFile
     */
    protected function storeFlysystem($tmpFile, $ext, $overwrite = false){
        if (MimeTypeHelper::isArchive($ext, true)) {
            $this->disk_name = config('crawler.download.disk.archive');
        }else{
            $this->disk_name = config('crawler.download.disk.file');
        }
        $this->dataInfo['disk'] = $this->disk_name;
        $pathFile = $this->makePath($this->downloadLink->id . '.' . $ext);
        $stream = fopen($tmpFile, 'r+');
        /** @var  $disk Filesystem */
        $disk = \Flysystem::connection($this->disk_name);
        if ($overwrite){
            $save = $disk->putStream($pathFile, $stream);
        }else{
            $save = $disk->writeStream($pathFile, $stream, [
                'visibility' => AdapterInterface::VISIBILITY_PRIVATE
            ]);
        }
        if ($save){
            return [
                'success' => true,
                'sizeFile' => $disk->getSize($pathFile),
                'pathFile' => $pathFile
            ];
        }else{
            return [
                'success' => false,
                'message' => 'Can not store flysystem'
            ];
        }
    }


    abstract function download();
}