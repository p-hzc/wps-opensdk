<?php
/**
 * Created by IntelliJ IDEA.
 * User: kingsoft
 * Date: 2019/7/10
 * Time: 17:52
 */

namespace OpenSdk\OpenApi;

if(!defined("OPEN_API_PATH"))
    define("OPEN_API_PATH", dirname(dirname(__FILE__)));

require_once OPEN_API_PATH.DIRECTORY_SEPARATOR."vendor".DIRECTORY_SEPARATOR."autoload.php";
//include "YunBase.php";
use OpenSdk\Exceptions\OpenapiException;
use OpenSdk\Http\HttpClient;
use OpenSdk\Util\FileUtil;

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

class YunFile
{
    static public function getRemainingSpace($accessToken){
        $uri = "/oauthapi/v2/appfile/remaining";
        $uri .= "?appid=". Config::getAppid();
        $uri .= "&access_token=" . $accessToken;

        $rslt = HttpClient::getInstance()->Get($uri, null, Config::getProxy());
        return $rslt['remaining'];
    }

    static public function getAppFileList($accessToken, $order, $orderBy, $offset, $count, $parentId){
        $uri = "/oauthapi/v3/app/files/list";
        $uri .= "?appid=" . Config::getAppid();
        $uri .= "&access_token=" . $accessToken;
        $uri .= "&open_parentid=" . $parentId;
        if (!(is_null($order) || empty($order))){
            $uri .= "&order=" . $order;
        }
        if (!(is_null($offset) || empty($offset))){
            $uri .= "&offset=" . $offset;
        }
        if (!(is_null($count) || empty($count))){
            $uri .= "&count=" . $count;
        }
        if (!(is_null($orderBy) || empty($orderBy))){
            $uri .= "&order_by=" . $orderBy;
        }

        $res = HttpClient::getInstance()->Get($uri, null, Config::getProxy());
        return $res['files'];
    }

    static public function createFlodler($accessToken, $name, $parentId) {
        $uri = "/oauthapi/v3/app/folders/create";
        $option = [
            'json' => [
                'appid'         => (string)Config::getAppid(),
                'open_parentid' => (string)$parentId,
                'name'          => (string)$name,
                'access_token'  => (string)$accessToken
            ]
        ];
        $rslt = HttpClient::getInstance()->Post($uri, $option, Config::getProxy());
        return $rslt['data'];
    }

    static public function getFileLinkInfo($accessToken, $fileId, $permission=null, $period=null){
        $uri = "/oauthapi/v3/app/files/link";
        $uri .= "?appid=" . Config::getAppid();
        $uri .= "&access_token=" . $accessToken;
        $uri .= "&open_fileid=" . $fileId;
        if (!(is_null($permission) || empty($permission))){
            $uri .= "&permission=" . $permission;
        }
        if (!(is_null($period) || empty($period))){
            $uri .= "&period=" . $period;
        }

        $rslt = HttpClient::getInstance()->Get($uri, null, Config::getProxy());
        return $rslt;
    }

    static public function getFileDownloadUrl($accessToken, $fileId){
        $uri = "/oauthapi/v3/app/files/download/url";
        $uri .= "?appid=" . Config::getAppid();
        $uri .= "&access_token=" . $accessToken;
        $uri .= "&open_fileid=" . $fileId;

        $rslt = HttpClient::getInstance()->Get($uri, null, Config::getProxy());
        return $rslt;
    }

    static public function uploadFile($accessToken, $parentId, $filePath, $addNameIndex){
        $sha1 = FileUtil::getFileSHA1($filePath);
        $file = fopen($filePath, 'r');
        $fileName = basename($filePath);
        $fileSize = filesize($filePath);
        if (!$file){
            throw new OpenapiException("open file failed.");
        }

        $uploadData = self::getUploadUrl($accessToken, $fileName, $parentId, $fileSize, $sha1);
        if ($uploadData) {
            list ($fsha1, $etag)  = self::upload2CDN($uploadData, $file);
            return self::uploadFileInfo($accessToken, $parentId, $sha1, $fileName, $fileSize, $etag, $addNameIndex);
        }
        throw new OpenapiException("upload file failed.");
    }

    static public function updateFile($accessToken, $parentId, $fileId, $filePath){
        $sha1 = FileUtil::getFileSHA1($filePath);
        $file = fopen($filePath, 'r');
        $fileName = basename($filePath);
        $fileSize = filesize($filePath);
        if (!$file){
            throw new OpenapiException("open file failed.");
        }

        $uploadData = self::getUploadUrl($accessToken, $fileName, $parentId, $fileSize, $sha1);
        if ($uploadData) {
            list ($fsha1, $etag) = self::upload2CDN($uploadData, $file);
            return self::updateFileInfo($accessToken, $parentId, $fileId, $sha1, $fileName, $fileSize, $etag);
        }
        throw new OpenapiException("upload file failed.");
    }

    static public function fileRename($accessToken, $newName , $fileId ){
        $uri = "/oauthapi/v3/app/files/rename";
        $option = [
            'json' => [
                'appid'         => (string)Config::getAppid(),
                'new_name'      => (string)$newName,
                'open_fileid'   => (string)$fileId,
                'access_token'  => (string)$accessToken
            ]
        ];
        $rslt = HttpClient::getInstance()->Put($uri, $option, Config::getProxy());
        return $rslt;
    }

    static public function fileCopyInApp($accessToken, $fileIds, $fromParentId, $toParentId){
        $uri = "/oauthapi/v3/app/files/copy";
        $option = [
            'json' => [
                'appid'                  => (string)Config::getAppid(),
                'open_from_parentid'     => (string)$fromParentId,
                'open_to_parentid'       => (string)$toParentId,
                'open_fileids'           => (string)$fileIds,
                'access_token'           => (string)$accessToken
            ]
        ];
        $rslt = HttpClient::getInstance()->Post($uri, $option, Config::getProxy());
        return $rslt;
    }

    static public function fileMoveInApp($accessToken, $fileIds, $fromParentId, $toParentId){
        $uri = "/oauthapi/v3/app/files/move";
        $option = [
            'json' => [
                'appid'                  => (string)Config::getAppid(),
                'open_from_parentid'     => (string)$fromParentId,
                'open_to_parentid'       => (string)$toParentId,
                'open_fileids'           => (string)$fileIds,
                'access_token'           => (string)$accessToken
            ]
        ];
        $rslt = HttpClient::getInstance()->Post($uri, $option, Config::getProxy());
        return $rslt;
    }

    static public function fileDelete($accessToken, $fileIds){
        $uri = "/oauthapi/v3/app/files/delete";
        $option = [
            'json' => [
                'appid'         => (string)Config::getAppid(),
                'open_fileids'  => (string)$fileIds,
                'access_token'  => (string)$accessToken
            ]
        ];
        $rslt = HttpClient::getInstance()->Delete($uri, $option, Config::getProxy());
        return $rslt;
    }

    static public function serchByName($accessToken, $parentId, $fName, $offset, $count){
        $uri = "/oauthapi/v3/app/files/searchbyname";
        $uri .= "?appid=" . Config::getAppid();
        $uri .= "&access_token=" . $accessToken;
        $uri .= "&open_parentid=" . $parentId;
        $uri .= "&file_name=" . $fName;
        if (!(is_null($offset) || empty($offset))){
            $uri .= "&offset=" . $offset;
        }
        if (!(is_null($count) || empty($count))){
            $uri .= "&count=" . $count;
        }

        $res = HttpClient::getInstance()->Get($uri, null, Config::getProxy());
        return $res['files'];
    }

    static public function serchByContent($accessToken, $parentId, $content, $offset, $count){
        $uri = "/oauthapi/v3/app/files/searchbycontent";
        $uri .= "?appid=" . Config::getAppid();
        $uri .= "&access_token=" . $accessToken;
        $uri .= "&open_parentid=" . $parentId;
        $uri .= "&content=" . $content;
        if (!(is_null($offset) || empty($offset))){
            $uri .= "&offset=" . $offset;
        }
        if (!(is_null($count) || empty($count))){
            $uri .= "&count=" . $count;
        }

        $res = HttpClient::getInstance()->Get($uri, null, Config::getProxy());
        return $res['files'];
    }

    static public function createFile($accessToken, $parentId, $name, $addNameIndex) {
        $uri = "/oauthapi/v3/app/files/create";
        $option = [
            'json' => [
                'appid'           => (string)Config::getAppid(),
                'open_parentid'   => (string)$parentId,
                'file_name'       => (string)$name,
                'access_token'    => (string)$accessToken,
                'add_name_index'  => (bool)$addNameIndex
            ]
        ];
        $rslt = HttpClient::getInstance()->Post($uri, $option, Config::getProxy());
        return $rslt['file'];
    }

    static public function getFileInfo($accessToken, $fileId){
        $uri = "/oauthapi/v2/file/info";
        $uri .= "?appid=" . Config::getAppid();
        $uri .= "&access_token=" . $accessToken;
        $uri .= "&open_fileid=" . $fileId;

        $rslt = HttpClient::getInstance()->Get($uri, null, Config::getProxy());
        return $rslt;
    }

    static public function getLinkInfo($accessToken, $linkUrl){
        $uri = "/oauthapi/v2/file/link/info";
        $uri .= "?appid=" . Config::getAppid();
        $uri .= "&access_token=" . $accessToken;
        $uri .= "&link_url=" . $linkUrl;

        $rslt = HttpClient::getInstance()->Get($uri, null, Config::getProxy());
        return $rslt;
    }

    static public function getUploadUrl($accessToken, $name, $parentId, $size, $sha1){
        $uri = "/oauthapi/v4/app/files/upload/request";
        $option = [
            'json' => [
                'appid'           => (string)Config::getAppid(),
                'access_token'    => (string)$accessToken,
                'open_parentid'   => (string)$parentId,
                'size'            => (int)$size,
                'name'            => (string)$name,
                'sha1'            => (string)$sha1
            ]
        ];
        $rslt = HttpClient::getInstance()->Put($uri, $option, Config::getProxy());
        $data = $rslt['data'];
        return $data;
    }

    static public function upload2CDN(array $uploadData, $file){
        if (!is_resource($file)){
            throw new OpenapiException("file argument must be resource type.");
        }

        $putAuth = $uploadData['uploadinfo']["put_auth"];
        $uri = $putAuth['upload_url'];
        $options = [
            'headers' => $uploadData['uploadinfo']["headers"],
            'body' => $file
        ];

        $rslt = HttpClient::getInstance()->PutFile($uri, $options, Config::getProxy());
        return $rslt;
    }

    static public function uploadFileInfo($accessToken, $parentId, $sha1, $name, $size, $etag, $addNameIndex){
        $uri = "/oauthapi/v3/app/files/upload/commit";
        $options = [
            'json' => [
                "access_token"	  =>	(string)$accessToken,
		        "appid"			  =>	(string)Config::getAppid(),
		        "size"			  =>	(int)$size,
                "open_parentid"   =>    (string)$parentId,
		        "name"			  =>	(string)$name,
		        "sha1"			  =>	(string)$sha1,
                "etag"			  =>	(string)$etag,
                "add_name_index"  =>	(string)$addNameIndex,
            ]
        ];
        $rslt = HttpClient::getInstance()->Post($uri, $options, Config::getProxy());
        return $rslt['data'];
    }

    static public function updateFileInfo($accessToken, $parentId, $fileId, $sha1, $name, $size, $etag){
        $uri = "/oauthapi/v3/app/files/upload/update";
        $options = [
            'json' => [
                "access_token"	  =>	(string)$accessToken,
                "appid"			  =>	(string)Config::getAppid(),
                "size"			  =>	(int)$size,
                "open_parentid"   =>    (string)$parentId,
                "open_fileid"     =>    (string)$fileId,
                "name"			  =>	(string)$name,
                "sha1"			  =>	(string)$sha1,
                "etag"			  =>	(string)$etag,
            ]
        ];
        $rslt = HttpClient::getInstance()->Post($uri, $options, Config::getProxy());
        return $rslt['data'];
    }
}