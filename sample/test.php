<?php
/**
 * This file is part of the chunpat/flysystem-tencent-cos.
 *
 * (c) chunpat <chunpat@163.com>
 *
 * This source file is subject to the MIT license that is bundled.
 */
require dirname(__FILE__).'/../vendor/autoload.php';

$secretId = '######'; //"云 API 密钥 SecretId";
$secretKey = '######'; //"云 API 密钥 SecretKey";
$region = 'ap-guangzhou'; //设置一个默认的存储桶地域
$cosClient = new Qcloud\Cos\Client(
    [
        'region' => $region,
        'schema' => 'https', //协议头部，默认为http
        'credentials' => [
            'secretId' => $secretId,
            'secretKey' => $secretKey, ], ]
);
$bucket = 'zzhpeng-1256184324'; //存储桶名称 格式：BucketName-APPID
$key = 'exampleobject';

//$cosClient->GetBucketRequest();
//exit;

$adapter = new \Chunpat\FlysystemTencentCos\Adapter($cosClient, $bucket);

try {
    $srcPath = '/Users/zzhpeng/Documents/WechatIMG8.jpeg'; //本地文件绝对路径
    $file = fopen($srcPath, 'rb');
    $filesystem = new \League\Flysystem\Filesystem($adapter);

    // 设置属性
    // 如设置了Content-Type，则可以不指定路径的后缀 (即$filePath可以不包含.jpg等后缀名)
    $fInfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $fInfo->buffer($srcPath);
    $config = [
        'Content-Type' => $mimeType,
    ];
//    $filePath = "test";
    $filePath = 'niubi/6666666';

    // 上传
//    $filesystem->write($filePath, $file , $config);
//    var_dump($adapter->getResult());

    // 更新
//    $filesystem->update($filePath, $file , $config);
//    var_dump($adapter->getResult());

    // 删除
//    $filesystem->delete($filePath);
//    var_dump($adapter->getResult());

    // 检测是否存在
//    var_dump($filesystem->has($filePath));
//    var_dump($adapter->getResult());

    // 读取文件信息
//    $result = $filesystem->read($filePath);
//    var_dump( $result);

    //获取文件的权限
//    $result = $filesystem->getVisibility($filePath);
//    var_dump( $result);

    //设置文件的权限
//    $visibility = 'public';
//    $result = $filesystem->getVisibility($filePath,$visibility);
//    var_dump( $result);

    //复制
//    $newfilePath = "niubi/232132";
//    $result = $filesystem->copy($filePath,$newfilePath);
//    var_dump( $result);

    //重命名
//    $newfilePath = "niubi/6666666";
//    $result = $filesystem->rename($filePath,$newfilePath);
//    var_dump( $result);

    //创建目录
//    $newfilePath = "niubi2";
//    $result = $filesystem->createDir($newfilePath,$config);
//    var_dump($adapter->getResult());

    //删除目录
//    $filePath = "niubi/6666666";
//    $result = $filesystem->deleteDir($filePath);
//    var_dump($result);
//    var_dump($adapter->getResult());

    //获取资源信息
//    $filePath = "1111111";
//    $result = $filesystem->getMetadata($filePath);
//    var_dump($result);
//    var_dump($adapter->getResult());

    //获取列表
//    $filePath = "";
//    $result = $filesystem->listContents($filePath);
//    var_dump($result);

    //用流读
//    $filePath = "23333333";
//    $sourcefile = fopen($srcPath, 'rb');
//    $result = $filesystem->readStream($filePath);

    //用流写
//    $filePath = "23333333";
//    $sourcefile = fopen($srcPath, 'rb');
//    $result = $filesystem->readStream($filePath,$file);

    //用流更新
//    $filePath = "23333333";
//    $sourcefile = fopen($srcPath, 'rb');
//    $result = $filesystem->updateStream($filePath,$file);

    exit;
} catch (\Exception $e) {
    echo "$e\n";
}
