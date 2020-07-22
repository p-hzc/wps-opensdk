<?php
/**
 * Created by IntelliJ IDEA.
 * User: kingsoft
 * Date: 2019/7/10
 * Time: 9:59
 */
namespace PHzc\WpsOpensdk\OpenApi;

use PHzc\WpsOpensdk\Exceptions\OpenapiException;
use PHzc\WpsOpensdk\Http\HttpClient;

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


class Payment
{
    static public function getUsableService($accessToken, $openid, $serviceId, $totalNum) {
        $uri = "/oauthapi/v2/vas/service/usable";
        $options = [
            'json' => [
                'access_token'  => (string)$accessToken,
                'openid'        => (string)$openid,
                'service_id'    => (string)$serviceId,
                'total_num'     => (int)$totalNum,
                'appid'         => (string)Config::getAppid()
            ]
        ];
        $rslt = HttpClient::getInstance()->Post($uri, $options, Config::getProxy());
        $usable = $rslt['result'] == 0;
        return $usable;
    }

    static public function preorderPay($accessToken, $openId, $serviceId , $totalNum, $billno, $subject,  $position,  $clientIp)
    {
        $uri = "/oauthapi/v2/vas/service/usable";
        $options = [
            'json' => [
                "access_token"  =>  (string)$accessToken,
                "openid"		=>  (string)$openId,
                "service_id"    =>	(string)$serviceId,
		        "total_num"     =>  (int)$totalNum,
		        "billno"        =>	(string)$billno,
		        "subject"       =>  (string)$subject,
		        "position"       =>  (string)$position,
		        "client_ip"     =>  (string)$clientIp,
		        "appid"         =>  (string)Config::getAppid(),
            ]
        ];
        $resp = HttpClient::getInstance()->Post($uri, $options, Config::getProxy());
        $payed = $resp['result'] == 0;
        return $payed;
    }


    static public function useService($accessToken, $openId, $serviceId , $totalNum, $billno)
    {
        $uri = "/oauthapi/v2/vas/service/use";
        $options = [
            'json' => [
                "access_token"  =>  (string)$accessToken,
                "openid"		=>  (string)$openId,
                "service_id"    =>	(string)$serviceId,
                "total_num"     =>  (int)$totalNum,
                "billno"        =>	(string)$billno,
                "appid"         =>  (string)Config::getAppid(),
            ]
        ];
        $resp = HttpClient::getInstance()->Post($uri, $options, Config::getProxy());
        $useFlag = $resp["result"] == 0;

        return $useFlag?$billno:"";
    }

    static public function customorderPay($accessToken, $openId, $serviceId, $billNo, $subject, $position,
	$payment, $totalFee, $count){
        $uri = "/oauthapi/v2/vas/pay/customorder";
        $options = [
            'json' => [
                "access_token"  =>  (string)$accessToken,
		        "openid"        =>  (string)$openId,
		        "service_id"    =>  (string)$serviceId,
		        "payment"       =>  (string)$payment,
		        "total_fee"     =>  (int)$totalFee,
		        "count"         =>  (int)$count,
		        "billno"        =>  (string)$billNo,
		        "subject"       =>  (string)$subject,
		        "position"      =>  (string)$position,
		        "appid"         =>  (string)Config::getAppid(),
            ]
        ];
        $resp = HttpClient::getInstance()->Post($uri, $options, Config::getProxy());

        return $resp['data'];
    }

    static public function memberAdd($accessToken, $openId, $orderId, $memberId, $days, $phone){
        $uri = "/oauthapi/v2/vas/pay/member/add";
        $options = [
            'json' => [
                "access_token"  =>  (string)$accessToken,
                "openid"        =>  (string)$openId,
                "orderid"       =>  (string)$orderId,
                "memberid"      =>  (string)$memberId,
                "days"          =>  (string)$days,
                "phone"         =>  (string)$phone,
                "appid"         =>  (string)Config::getAppid(),
            ]
        ];
        $resp = HttpClient::getInstance()->Post($uri, $options, Config::getProxy());

        return $resp['data'];
    }

    static public function bannerOpen($accessToken, $mod, $position){
        $uri = "/oauthapi/v2/vas/banner/open";
        $uri .= "?appid=" . Config::getAppid();
        $uri .= "&access_token=" . $accessToken;
        $uri .= "&mod=" . $mod;
        $uri .= "&position=" . $position;

        $res = HttpClient::getInstance()->Get($uri, null, Config::getProxy());
        return $res['data'];
    }


}