<?php
//检测API路径
namespace PHzc\WpsOpensdk\OpenApi;

use PHzc\WpsOpensdk\Exceptions\OpenapiException;
use PHzc\WpsOpensdk\Http\HttpClient;
use PHzc\WpsOpensdk\OpenApi\Config;

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


class Oauth2
{
    private $token  = null;
    private $tokenExpires = 0;

    function  __construct($code)
    {
        $uri = "/oauthapi/v2/token";
        $uri .= "?appid=".Config::getAppid();
        $uri .= "&appkey=".Config::getAppkey();
        $uri .= "&code=". $code;

        $cc = Config::getProxy();
        $rslt = HttpClient::getInstance()->Get($uri, null, $cc);
        if (!is_null($rslt)){
            $curDate = time();
            $this->token = $rslt["token"];
            if ($this->token != null) {
                $this->tokenExpires = $curDate + $rslt["token"]["expires_in"];
            }
        }
    }

    public function getTokenSelf(){
        $curDate = time();
        if ($curDate < $this->tokenExpires) {
            return $this->token;
        }

        $uri = "/oauthapi/v2/token/refresh";
        $option = [
            'json' => [
                'appid'         => (string)Config::getAppid(),
                'appkey'        => (string)Config::getAppkey(),
                'refresh_token' => (string)$this->token["refresh_token"]
            ]
        ];
        $rslt = HttpClient::getInstance()->Post($uri, $option, Config::getProxy());
        if (!is_null($rslt)){
            $curDate = time();
            $this->token = $rslt["token"];
            $this->tokenExpires = $curDate + $rslt["token"]["expires_in"];
            return $this->token;
        }

        return null;
    }

    static public function getToken($code){
        $uri = "/oauthapi/v2/token";
        $uri .= "?appid=".Config::getAppid();
        $uri .= "&appkey=".Config::getAppkey();
        $uri .= "&code=". $code;

        $rslt = HttpClient::getInstance()->Get($uri, null, Config::getProxy());
        if (is_null($rslt)){
            return  $rslt;
        }

        return $rslt["token"];
    }

    static public function refreshToken( $refreshToken){
        $uri = "/oauthapi/v2/token/refresh";
        $option = [
            'json' => [
                'appid'         => (string)Config::getAppid(),
                'appkey'        => (string)Config::getAppkey(),
                'refresh_token' => (string)$refreshToken
            ]
        ];
        $rslt = HttpClient::getInstance()->Post($uri, $option, Config::getProxy());
        if (is_null($rslt)){
            return  $rslt;
        }

        return $rslt["token"];
    }

    static public function getUserInfo($accessToken, $openid){
        $uri = "/oauthapi/v2/user";
        $uri .= "?appid=" .Config::getAppid();
        $uri .= "&access_token=" .$accessToken;
        $uri .= "&openid=" .$openid;

        $rslt = HttpClient::getInstance()->Get($uri, null, Config::getProxy());
        return $rslt;
    }

    static public function getUserInfoV3($accessToken, $openid){
        $uri = "/oauthapi/v3/user";
        $uri .= "?appid=" .Config::getAppid();
        $uri .= "&access_token=" .$accessToken;
        $uri .= "&openid=" .$openid;

        $rslt = HttpClient::getInstance()->Get($uri, null, Config::getProxy());
        return $rslt;
    }

    static public function getRPCToken($accessToken, $scope){
        $uri = "/oauthapi/v2/rpc/token";
        $option = [
            'json' => [
                'appid'         => (string)Config::getAppid(),
                'scope'         => (string)$scope,
                'access_token'  => (string)$accessToken
            ]
        ];

        $rslt = HttpClient::getInstance()->Post($uri, $option, Config::getProxy());
        return $rslt["rpc_token"];
    }

    static public function checkRPCToken($rpcToken, $scope){
        $uri = "/oauthapi/v2/rpc/scope/authorize";
        $option = [
            'json' => [
                'appid'     => (string)Config::getAppid(),
                'scope'     => (string)$scope,
                'rpc_token' => (string)$rpcToken
            ]
        ];
        $rslt = HttpClient::getInstance()->Post($uri, $option, Config::getProxy());
        $chkRslt =  $rslt["authorized"] == 1;
        return $chkRslt;
    }
}