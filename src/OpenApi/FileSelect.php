<?php


namespace PHzc\WpsOpensdk\OpenApi;

if(!defined("OPEN_API_PATH"))
    define("OPEN_API_PATH", dirname(dirname(__FILE__)));

require_once OPEN_API_PATH.DIRECTORY_SEPARATOR."vendor".DIRECTORY_SEPARATOR."autoload.php";
use PHzc\WpsOpensdk\Exceptions\OpenapiException;
use PHzc\WpsOpensdk\Http\HttpClient;

if (function_exists("version_compare")){
    if(version_compare(PHP_VERSION, "5.5", "lt")){
        throw new OpenapiException("php version must greater than 5.5.");
    }
}

if(function_exists('get_loaded_extensions')){
    //检测curl扩展
    $extensions = get_loaded_extensions();
    if($extensions){
        if(!in_array('curl', $extensions)){
            throw new OpenapiException("please install curl extension");
        }
        if(!in_array('mbstring', $extensions)){
            throw new OpenapiException("please install mbstring extension");

        }
    }else{
        throw new OpenapiException("please install extensions");
    }
}else{
    throw new OpenapiException("not get_loaded_extensions function");
}


class FileSelect
{
    public function getFileInfoList($accessToken, $fileCode){
        $uri = "/oauthapi/v2/selector/file/info";
        $uri .= "?appid=". Config::getAppid();
        $uri .= "&access_token=" . $accessToken;
        $uri .= "&file_code=" . $fileCode;

        $res = HttpClient::getInstance()->Get($uri, null, Config::getProxy());
        return $res['file_info_list'];
    }

    static public function getShareFileList($accessToken, $fileCode, $ranges=null, $permission=null, $period=null){
        $uri = "/oauthapi/v2/selector/share/info";
        $uri .= "?appid=" . Config::getAppid();
        $uri .= "&access_token=" . $accessToken;
        $uri .= "&file_code=" . $fileCode;
        if (!(is_null($ranges) || empty($ranges))){
            $uri .= "ranges=" . $ranges;
        }
        if (!(is_null($permission) || empty($permission))){
            $uri .= "&permission=" . $permission;
        }
        if (!(is_null($period) || empty($period))){
            $uri .= "&period=" . $period;
        }

        $res = HttpClient::getInstance()->Get($uri, null, Config::getProxy());
        return $res['share_info_list'];
    }

    static public function getDownloadFileList($accessToken, $fileCode){
        $uri = "/oauthapi/v2/selector/download/url";
        $uri .= "?appid=" . Config::getAppid();
        $uri .= "&access_token=" . $accessToken;
        $uri .= "&file_code=" . $fileCode;

        $res = HttpClient::getInstance()->Get($uri, null, Config::getProxy());
        return $res['download_info_list'];
    }

    static public function uploadFile($accessToken, $fileCode, $filePath, $addNameIndex){
        $file = fopen($filePath, 'r');
        $fileName = basename($filePath);
        $fileSize = filesize($filePath);
        if (!$file){
            throw new OpenapiException("open file failed.");
        }

        $uploadData =  self::getUploadUrl($accessToken, $fileCode, $fileName, $fileSize);
        if ($uploadData) {
            list ($sha1, $etag)  = self::upload2CDN($uploadData, $file);
            return self::uploadFileInfo($accessToken, $fileCode, $sha1, $fileName, $fileSize, $etag, $addNameIndex);
        }
        throw new OpenapiException("upload file failed.");
    }

    static public function getUploadUrl($accessToken, $fileCode, $name, $size){
        $uri = "/oauthapi/v3/selector/upload/info";
        $uri .= "?appid=" . Config::getAppid();
        $uri .= "&access_token=" .$accessToken;
        $uri .= "&file_code=" . $fileCode;
        $uri .= "&size=" . $size;
        $uri .= "&name=" . $name;
        $rslt = HttpClient::getInstance()->Get($uri, null, Config::getProxy());
        $data = $rslt['upload_info'];
        return $data;
    }

    static public function upload2CDN(array $uploadData, $file){
        if (!is_resource($file)){
            throw new OpenapiException("file argument must be resource type.");
        }
        $uri = $uploadData["put_auth"]['upload_url'];
        $options = [
            'headers' => $uploadData["headers"],
            'body' => $file
        ];

        $rslt = HttpClient::getInstance()->PutFile($uri, $options, Config::getProxy());
        return $rslt;
    }

    static public function uploadFileInfo($accessToken, $fileCode, $sha1, $name, $size, $etag, $addNameIndex){
        $uri = "/oauthapi/v3/selector/file/create";
        $options = [
            'json' => [
                "access_token"	 =>	(string)$accessToken,
                "appid"			 =>	(string)Config::getAppid(),
                "file_code"      =>	(string)$fileCode,
                "size"			 =>	(int)$size,
                "name"			 =>	(string)$name,
                "sha1"			 =>	(string)$sha1,
                "etag"			 =>	(string)$etag,
                "add_name_index" =>	(string)$addNameIndex
            ]
        ];
        $rslt = HttpClient::getInstance()->Post($uri, $options, Config::getProxy());
        return $rslt['file_info'];
    }
}