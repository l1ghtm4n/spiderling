<?php
/**
 * Created by PhpStorm.
 * User: conghoan
 * Date: 18/07/2017
 * Time: 09:28
 */

namespace App\Colombo\Download;

use App\Helpers\MimeTypeHelper;

class DirectDownloader extends DownloaderAbstract
{
    function __construct($downloadLink, $force = false)
    {
        parent::__construct($downloadLink, $force);
        $this->filtered = 'downloadable';
    }

    /**
     * Thực hiện các bước download
     * @return array [success: trạng thái download, message: thông báo lỗi nếu có]
     */
    public function download()
    {
        try {
            $this->checkFilterLink();
            $this->checkRedownload();
            // B1: Download xuong tmp
            $resultFileTmp = $this->fileToTmp($this->downloadLink->href);
            $tmpFile = $resultFileTmp['tmpFile'];
            if (!$resultFileTmp['success']){
                @unlink($tmpFile);
                return $resultFileTmp;
            }
            $sizeFileTmp = array_get($resultFileTmp['data'], 'size');
            $mimeType = array_get($resultFileTmp['data'], 'mime_type');
            // B2: Kiem tra cac dieu kien
            $check = $this->checkConditions($mimeType, $sizeFileTmp);
            if (!$check['success']){
                @unlink($tmpFile);
                return $check;
            }
            // B3: Luu tru len Flysystem
            $resultStore = $this->storeFlysystem($tmpFile,MimeTypeHelper::ext_from_mime($mimeType), $this->force);
            if (!$resultStore['success']){
                @unlink($tmpFile);
                return $resultStore;
            }
            $saved = $resultStore['sizeFile'] == $sizeFileTmp;
            @unlink($tmpFile);
            // B4: Cap nhat lai thong tin vao csdl
            if ($saved) {
                $this->dataInfo['size'] = $sizeFileTmp;
                $this->dataInfo['path'] = $resultStore['pathFile'];
                $this->dataInfo['status'] = 1;
                $this->saveInfo($this->dataInfo);
                return [
                    'success' => true,
                    'message' => 'Download success disk: ' .$this->disk_name .
                        ' - path: '. $resultStore['pathFile']
                ];
            } else {
                $dataInfo['status'] = -1;
                $this->saveInfo($this->dataInfo);
                return [
                    'success' => false,
                    'message' => 'save fail'
                ];
            }
        }
        catch (\Exception $ex) {
            \Log::error($ex);
            return [
                'success' => false,
                'message' => $ex->getMessage()
            ];
        }
    }
}