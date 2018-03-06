<?php
/**
 * Created by PhpStorm.
 * User: conghoan
 * Date: 08/08/2017
 * Time: 09:06
 */

namespace App\Colombo;

use App\Colombo\Archiver\Archiver;
use App\Helpers\MimeTypeHelper;
use App\Helpers\Utils;
use App\Models\DownloadLink;
use Carbon\Carbon;
use League\Flysystem\Filesystem;
use Symfony\Component\Finder\SplFileInfo;

class FileDecompressor
{
    private $tmp_archive_file_path;
    private $decompressed_path;

    function __construct()
    {
        $this->tmp_archive_file_path = config('crawler.decompress_file.tmp_path');
        $this->decompressed_path = config('crawler.decompress_file.path');
    }

    /**
     * @param $dl DownloadLink
     */
    public function run($dl){
        try{
            /** @var  $disk Filesystem*/
            $disk = \Flysystem::connection($dl->disk);
            $content = $disk->read($dl->path);

            $mime_type = $disk->getMimetype($dl->path);
            $ext = MimeTypeHelper::ext_from_mime($mime_type);
            $archive_file_path = $this->tmp_archive_file_path. '/' . $dl->id . '.' . $ext;
            file_put_contents($archive_file_path, $content);


            $decompress_path = $this->decompressed_path . '/'.$dl->id . '-' . time();
            $archiver = new Archiver();
            $result = $archiver->decompress($archive_file_path,  $decompress_path);

            if (!$result['success']){
                $dl->status = -2;
                $dl->save();
                return $result;
            }
            $decompress_dir = $result['dir'];
            $files = \File::allFiles($decompress_dir);
            $list_files_accepted = [];
            foreach ($files as $file){
                /**@var $file SplFileInfo */
                $ext = $file->getExtension();
                if (MimeTypeHelper::is_auto_download($ext, true) != false){
                    $list_files_accepted[] = $file;
                }
            }

            $decompress_result = [];
            foreach ($list_files_accepted as $file){
                /**@var $file SplFileInfo */
                $result_save_file = $this->saveFile($file, $dl);
                if ($result['success']){
                    $decompress_result[] = $result_save_file;
                }
            }
            \File::deleteDirectory($decompress_dir);
            if (count($decompress_result) > 0){
                $dl->status = 2;
                $dl->save();
                return [
                    'success' => true,
                    'numberFiles' => count($decompress_result),
                    'results' => $decompress_result
                ];
            }else{
                throw new \Exception('Archive file empty');
            }
        }catch (\Exception $exception){
            if (!empty($decompress_dir)){
                \File::deleteDirectory($decompress_dir);
            }
            $dl->status = -2;
            $dl->save();
            return [
                'success' => false,
                'message' => $exception->getMessage()
            ];
        }

    }


    public function cloneDownloadLink(DownloadLink $dl, $newData){
        try{
            $new_dl = $dl->replicate(['id', ]);
            $new_dl->fill($newData);
            $new_dl->save();
        }catch (\Exception $exception){
            throw new \Exception($exception->getMessage());
        }
    }

    /**
     * @param $file SplFileInfo
     * @param $dl DownloadLink
     */
    protected function saveFile($file, $dl){
        try{
            $filename = $file->getBasename();
            $ext = $file->getExtension();
            $path_file = $file->getPathname();
//            $path = Utils::makePath($dl->id . '_' . $filename . '.' . $ext);
            $path = Utils::makePath($dl->id . '_' . str_replace(" ", "_", $filename));
            $disk_name = config('crawler.download.disk.file');
            $store_result = Utils::store($path_file, $disk_name, $path, true);
            $mime_type_int = config('crawler.document.mime_type.' . $ext);
            $newData = [
                'name' => preg_replace("/.\w+$/", '', $filename),
                'href' => $dl->id . ': '. $filename,
                'downloaded_at' => Carbon::now(),
                'status' => 1,
                'upload_status' => 0,
                'mime_type' => $mime_type_int,
                'size' => $store_result['size'],
                'path' => $store_result['path'],
                'disk' => $store_result['disk']
            ];
            $this->cloneDownloadLink($dl, $newData);
            return [
                'success' => true,
                'disk' => $store_result['disk'],
                'path' => $store_result['path']
            ];
        }catch (\Exception $exception){
            throw new \Exception($exception->getMessage());
        }

    }
}