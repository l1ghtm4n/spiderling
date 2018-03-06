<?php
/**
 * Created by PhpStorm.
 * User: conghoan
 * Date: 29/07/2017
 * Time: 10:53
 */

namespace App\Colombo;


use GuzzleHttp\Client;

class NetworkChecker
{
    public static $client;

    public static function getClient(){
        if (!self::$client){
            self::$client = new Client([
                'timeout' => 5,
                'headers' => [
                    'User-Agent' => "Mozilla/5.0 (platform; rv:geckoversion) Gecko/geckotrail Firefox/firefoxversion"
                ]
            ]);
        }
        return self::$client;
    }
    public static function has(){
        $urls = [
            'https://www.google.com',
            'https://gitlab.com',
            'https://github.com/'
        ];
        $client = self::getClient();
        foreach ($urls as $url){
            try{
                $response = $client->head($url);
                if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 600){
                   return true;
                }
            }catch (\Exception $exception){

            }
        }
        return false;
    }
}