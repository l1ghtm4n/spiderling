<?php
/**
 * Created by PhpStorm.
 * User: hocvt
 * Date: 8/1/17
 * Time: 13:34
 */

namespace App\Helpers;


use App\Colombo\Download\Exceptions\CanNotSaveFlysystem;
use Illuminate\Support\Str;
use League\Flysystem\Filesystem;

class Utils
{
    public static function formatBytes($bytes)
    {
        $bytes = (int)$bytes;

        if ($bytes > 1048576) {
            return round($bytes / 1048576, 1) . 'MB';
        } elseif ($bytes > 1024) {
            return round($bytes / 1024, 0) . 'KB';
        }
        return $bytes . 'B';
    }

    public static function makePath($subfix = '', $prefix = '')
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

    public static function store($file, $disk_name, $path, $overwrite = false)
    {
        try {
            $stream = fopen($file, 'r+');
            /** @var  $disk Filesystem */
            $disk = \Flysystem::connection($disk_name);
            if ($overwrite) {
                $save = $disk->putStream($path, $stream);
            } else {
                $save = $disk->writeStream($path, $stream);
            }
            @unlink($file);
            return [
                'success' => true,
                'path' => $path,
                'disk' => $disk_name,
                'size' => $disk->getSize($path)
            ];
        } catch (\Exception $exception) {
            \Log::error($exception->getMessage());
            @unlink($file);
            throw new CanNotSaveFlysystem($exception->getMessage());
        }
    }
}