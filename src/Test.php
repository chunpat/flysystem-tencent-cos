<?php
/**
 * Created by PhpStorm.
 * User: zzhpeng
 * Date: 19/6/2020
 * Time: 9:25 AM
 */

namespace Chunpat\FlysystemTencentCos;


use Aws\S3\S3Client;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\Filesystem;

class Test
{

    function test(){
        $client = new S3Client([
            'credentials' => [
                'key'    => 'your-key',
                'secret' => 'your-secret'
            ],
            'region' => 'your-region',
            'version' => 'latest|version',
        ]);

        $adapter = new AwsS3Adapter($client, 'your-bucket-name');
        $filesystem = new Filesystem($adapter);
        $filesystem->
    }
}