<?php
/**
 * Created by PhpStorm.
 * User: conghoan
 * Date: 19/10/2016
 * Time: 08:59
 */

namespace App\Colombo\Uploader;


trait FileUtils
{
    private $tmp_files = [];
    
    public function createNewTmp($input = null, $is_content = true, $wm = 'w+'){
        $filename = tempnam(storage_path('crawled_file'), 'GldocTmp');
//        $filename = tempnam(sys_get_temp_dir(), 'GldocTmp');
        $filename = $filename.'.txt';
        $this->tmp_files[] = $filename;
        if($input != null){
            if(is_resource($input)){
                $ft = fopen($filename, $wm);
                while($block = fread($input, 4096)){
                    fwrite($ft, $block);
                }
                fclose($ft);
            }elseif($is_content){
                file_put_contents($filename, $input);
            }else{
                $fi = fopen($input, 'rb');
                $ft = fopen($filename, 'wb');
                while($block = fread($fi, 4096)){
                    fwrite($ft, $block);
                }
                fclose($fi);
                fclose($ft);
            }
        }
        return $filename;
    }

    /**
     * @return string
     * @throws ConvertException
     */
    private function newTmpFolder(){
        $filename = tempnam(sys_get_temp_dir(), 'GldocUpload');

        if (file_exists($filename)) { \File::delete($filename); }
        $filename = dirname($filename) . DIRECTORY_SEPARATOR . preg_replace('/\./', '_', basename($filename));
        if(\File::makeDirectory($filename, 0777, true) === false){
            mkdir($filename, '0777', true);
        }
        if (!is_dir($filename)) {
            throw new ConvertException("Can not create tmp folder");
        }
        $this->tmp_files[] = $filename;
        return $filename;
    }

    /**
     * Xóa các file tạm được tạo bởi class hiện tại
     * @return array
     */
    private function clearTmp(){
        foreach($this->tmp_files as $file){
            if(\File::isDirectory($file)){
                \File::deleteDirectory($file, true);
            }else{
                \File::delete($file);
            }
        }
        $this->tmp_files = [];
        return $this->tmp_files;
    }

    function __destruct() {
        $this->clearTmp();
    }
}