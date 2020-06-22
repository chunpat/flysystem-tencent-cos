<?php
/**
 * Created by PhpStorm.
 * User: zzhpeng
 * Date: 20/6/2020
 * Time: 10:11 AM
 */

namespace Chunpat\FlysystemTencentCos;


use Qcloud\Cos\Client;

class CosApi
{
    function PutBucketRequest(Client $client){
        $client->GetBucketRequest();
    }
}