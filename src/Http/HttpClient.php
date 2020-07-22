<?php
namespace PHzc\WpsOpensdk\Http;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use PHzc\WpsOpensdk\Exceptions\OpenapiException;

class HttpClient
{
    const DefaultHost = "https://openapi.wps.cn";

    private static $_instance = NULL;

    private $client = null;
    private function __construct()
    {
        $this->client = new Client(["base_uri" => self::DefaultHost]);

    }

    public static function getInstance()
    {
        if (self::$_instance == NULL) {
            self::$_instance = new HttpClient();
        }
        return self::$_instance;
    }



    public function __call($name, $arguments)
    {
        if (
            count($arguments) != 3
            || !is_string($arguments[0])
            || !(is_array($arguments[1])||is_null($arguments[1]))
            || !(is_string($arguments[2])||is_null($arguments[2]))
        ) {
            throw new OpenapiException("run correct api invole.");
        }
        // TODO: Implement __call() method.
        $ret = null;

        $options = $arguments[1];
        if (!empty($arguments[2])){
            $options['proxy'] = $arguments[2];
        }

        switch ($name) {
            case "Get":
                {
                    $ret = $this->send("GET", $arguments[0], $options, false);
                    break;
                }
            case "Post":
                {
                    $ret = $this->send("POST", $arguments[0], $options, false);
                    break;
                }
            case "Put":
                {
                    $ret = $this->send("PUT", $arguments[0], $options, false);
                    break;
                }
            case "PutFile":
                {
                    $response = $this->send("PUT", $arguments[0], $options, true);
                    $body = (string)$response->getBody();
                    $sha1 = json_decode($body, true)['newfilename'];
                    $etag = str_replace("\"","", $response->getHeader("Etag")[0]);
                    return array($sha1, $etag);
                    break;
                }
            case "Delete":
                {
                    $ret = $this->send("DELETE", $arguments[0], $options, false);
                    break;
                }
            default:
                {
                    throw new OpenapiExceoptions("no found api!");
                }
        }
        return $ret;
    }

    private function send($method, $url, $options, $esReponse){
        $retMsg = null;
        try{
            $retMsg = $this->_send($method, $url, $options, $esReponse);

        }catch (ConnectException $exception){
            try{
                $retMsg = $this->_send($method, $url, $options, $esReponse);

            }catch (\Exception $e){
                throw new OpenapiException($e->getMessage(),$e->getCode(), $e);

            }
        }catch (\Exception $e){
            throw new OpenapiException($e->getMessage(), $e->getCode(), $e);

        }
        return $retMsg;

    }

    private function _send($method, $url, $options, $esReponse){
        $resp = null;
        if (is_null($options)){
            $resp =  $this->client->request($method, $url);

        }else{
            $resp =  $this->client->request($method, $url, $options);

        }
        if($esReponse != true) {
            $body = (string)$resp->getBody();
            $retMsg = json_decode($body, true);
            return $retMsg;
        }
        return $resp;
    }
}