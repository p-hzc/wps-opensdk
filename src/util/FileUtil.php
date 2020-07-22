<?php


namespace OpenSdk\Util;


class FileUtil
{
    public static function getFileSHA1($filePath,  $blockSize=4096){
        $sha1file = sha1_file($filePath);
        return $sha1file;
    }
}