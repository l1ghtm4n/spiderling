<?php
/**
 * Created by PhpStorm.
 * User: conghoan
 * Date: 24/07/2017
 * Time: 14:09
 */

namespace App\Colombo\Download;


use App\Colombo\Download\Exceptions\CanNotSaveFlysystem;
use App\Colombo\Download\Exceptions\CanNotSaveTmp;
use App\Colombo\Download\Exceptions\DisconnectedException;
use App\Colombo\Download\Exceptions\DownloadedException;
use App\Colombo\Download\Exceptions\TimeoutException;
use App\Colombo\Download\Exceptions\TmpLinkDieException;
use App\Colombo\Download\Exceptions\ValidatorFileException;
use App\Helpers\MimeTypeHelper;
use App\Helpers\PhpUri;
use App\Helpers\Utils;
use App\Models\DownloadLink;
use Carbon\Carbon;
use League\Flysystem\AdapterInterface;
use League\Flysystem\Filesystem;

class DownloadProcesser
{
    protected $downloadLink;
    protected $force;
    protected $tmp_file = '';
    function __construct($downloadLink, $force = false, $testing = false)
    {
        if ($downloadLink instanceof DownloadLink){
            $this->downloadLink = $downloadLink;
        }else{
            $this->downloadLink = DownloadLink::find($downloadLink);
        }
        if (!$testing){
            if ($this->downloadLink->status != 1){
                $this->downloadLink->status = 10;
                $this->downloadLink->save();
            }
        }

        $this->force = $force;
    }
    /**
     *
     */
    public function run(){
        try {
            $this->checkRedownload();
            $downloader = DownloaderFactory::buildDownloader($this->downloadLink->filtered);
            $base = PhpUri::parse($this->downloadLink->page_link);
            $link = $base->join($this->downloadLink->href);
            $result = $downloader->run($link);
            $tmpFile = $result['tmpFile'];
            $this->tmp_file = $tmpFile;
            $size = filesize($tmpFile);
            $mime_type = mime_content_type($tmpFile);
            $ext = MimeTypeHelper::ext_from_mime($mime_type);
            $this->checkConditions($mime_type, $size);
            if (MimeTypeHelper::isArchive($mime_type)) {
                $mime_type_int = 0;
            } else {
                $mime_type_int = config('crawler.document.mime_type.' . $ext);
            }
            if ($this->force && $this->downloadLink->status == 1) {
                $disk_name = $this->downloadLink->disk;
                $path = $this->downloadLink->path;
            } else {
                if (MimeTypeHelper::isArchive($mime_type)) {
                    $disk_name = config('crawler.download.disk.archive');
                } else {
                    $disk_name = config('crawler.download.disk.file');
                }
                $path = Utils::makePath($this->downloadLink->id . '_' . time(). '.' . $ext);
            }

            $storeResult = $this->store($tmpFile, $disk_name, $path, $this->force);
            if (isset($result['data'])) {
                $data = $result['data'];
                if (isset($data['name'])){
                    $storeResult['name'] = $data['name'];
                }
            }
            $storeResult['status'] = 1;
            $storeResult['mime_type'] = $mime_type_int;
            $storeResult['downloaded_at'] = Carbon::now();
            unset($storeResult['success']);

            return [
                'success' => true,
                'id' => $this->downloadLink->id,
                'data' => $storeResult
            ];
        }catch (DownloadedException $exception) {
            return [
                'success' => false,
                'data' => [],
                'message' => $exception->getMessage()
            ];
        }catch (TimeoutException $timeoutException){
            $data['status'] = -10;
            return [
                'success' => false,
                'data' => $data,
                'message' => $timeoutException->getMessage()
            ];
        }catch (DisconnectedException $exception) {
            \Log::alert("Disconnected " . $exception->getMessage());
            $data['status'] = 0;
            return [
                'success' => false,
                'data' => $data,
                'stop' => true,
                'message' => $exception->getMessage()
            ];
        }catch (CanNotSaveFlysystem $exception) {
            $data['status'] = 0;
            \Log::alert('Error: can not store file from link : '
                . $this->downloadLink->href . ' message ' . $exception->getMessage());
            return [
                'success' => false,
                'data' => $data,
                'message' => $exception->getMessage(),
                'stop' => true
            ];
        }catch (CanNotSaveTmp $exception){
            $data['status'] = 0;
            \Log::alert('Error: can not save tmp file : '
                . $this->downloadLink->href . ' message ' . $exception->getMessage());
            return [
                'success' => false,
                'data' => $data,
                'message' => $exception->getMessage(),
                'stop' => true
            ];
        }catch (\Exception $exception){
            \Log::alert('Error: can not store file from link : '
                . $this->downloadLink->href . ' message ' . $exception->getMessage());
            $data['status'] = -1;
            return [
                'success' => false,
                'data' => $data,
                'message' => $exception->getMessage()
            ];
        }
    }
    /**
     * Kiểm tra link được phép download lại không
     * @throws DownloadedException
     */
    protected function checkRedownload(){
        if ($this->downloadLink->status == 1){
            if (!$this->force){
                throw new DownloadedException($this->downloadLink->id);
            }
        }
    }

    /**
     * Kiểm tra các điều kiện của file
     * @param $mimeType
     * @param $size
     * @return array
     * @throws ValidatorFileException
     */
    protected function checkConditionFile($mimeType, $size){
        $maxSizeFile = config('crawler.download.size.file') * 1024 * 1024;
        if (MimeTypeHelper::is_auto_download($mimeType)) {
            if ($size > $maxSizeFile) {
                throw new ValidatorFileException('limit max file : ' . $this->maxSizeFile);
            }
        } else {
            throw new ValidatorFileException('mime type :' . $mimeType. ' is not auto download');
        }
    }

    /**
     * Kiểm tra điều kiện của file nén
     * @param $mimType
     * @param $size
     * @return boolean
     * @throws ValidatorFileException
     */
    protected function checkConditionArchive($mimType, $size){
        $maxSizeArchive = config('crawler.download.size.archive') * 1024 * 1024;
        if ($size > $maxSizeArchive) {
            throw new ValidatorFileException('limit max file Archive : ' . $this->maxSizeArchive);
        }
        return true;
    }
    protected function checkConditions($mimeType, $size){
        if (MimeTypeHelper::isArchive($mimeType)) {
            $this->checkConditionArchive($mimeType, $size);

        } else {
            $this->checkConditionFile($mimeType, $size);
        }
        return true;
    }

    protected function store($tmpFile, $disk_name, $path, $overwrite = false){
        try{
            $stream = fopen($tmpFile, 'r+');
            /** @var  $disk Filesystem*/
            $disk = \Flysystem::connection($disk_name);
            if ($overwrite){
                $save = $disk->putStream($path, $stream);
            }else{
                $save = $disk->writeStream($path, $stream);
            }
//            @unlink($tmpFile);
            return [
                'success' => true,
                'path' => $path,
                'disk' => $disk_name,
                'size' => $disk->getSize($path)
            ];
        }catch (\Exception $exception){
            \Log::error("Store error " . $exception->getMessage());
//            @unlink($tmpFile);
            throw new CanNotSaveFlysystem($exception->getMessage());
        }

    }

    public function saveInfo($data){
        try{

            $data['downloaded_at'] = Carbon::now();
            $updated = $this->downloadLink->update($data);
            if (!$updated){
                throw new \Exception('can not save info download link with id: ' . $this->downloadLink->id);
            }
            return true;
        }catch (\Exception $exception){
            \Log::error('Can not save: ' . $exception->getMessage());
            $this->clear($data['disk'], $data['path']);
            $this->downloadLink->status = -5;
            $this->downloadLink->save();
            throw new \Exception('can not save info download link with id: ' . $this->downloadLink->id);
        }
    }
    protected function clear($disk_name, $path){
        /** @var  $disk Filesystem*/
        $disk = \Flysystem::connection($disk_name);
        $disk->delete($path);
    }
    public function __destruct()
    {
        if (!empty($this->tmp_file)){
            @unlink($this->tmp_file);
        }
    }
}