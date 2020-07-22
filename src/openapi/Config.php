<?php
namespace OpenSdk\OpenApi;

class Config
{
    private static $appid      = null;
    private static $appkey     = null;
    private static $proxy      = null;

    public static function setConfig($appid, $appkey){
        self::$appid = $appid;
        self::$appkey = $appkey;
    }

    public static function setConfigWithProxy($appid, $appkey, $proxy){
        self::$appid = $appid;
        self::$appkey = $appkey;
        self::$proxy = $proxy;
    }

    public function setProxy($proxy ){
        self::$proxy = $proxy;
    }

    public static function getAppid(){
        return self::$appid;
    }

    public static function getAppkey(){
        return self::$appkey;
    }

    public static function getProxy(){
        return self::$proxy;
    }
}