<h1 align="center"> flysystem-tencent-cos </h1>

<p align="center"> Tencent Cloud Cos Storage adapter for flysystem - a PHP filesystem abstraction.</p>

![StyleCI build status](https://github.styleci.io/repos/274198375/shield) 
[![Total Downloads](https://poser.pugx.org/chunpat/flysystem-tencent-cos/downloads)](https://packagist.org/packages/chunpat/flysystem-tencent-cos)
[![License](https://poser.pugx.org/chunpat/flysystem-tencent-cos/license)](https://packagist.org/packages/chunpat/flysystem-tencent-cos)

## Installing

```shell
$ composer require chunpat/flysystem-tencent-cos -vvv
```

## Usage

```php

$secretId = "your secretId";
$secretKey = "your secretKey";
$region = "ap-guangzhou"; //set a default bucket region 设置一个默认的存储桶地域 
$cosClient = new Qcloud\Cos\Client(
    array(
        'region' => $region,
        'schema' => 'https', //协议头部，默认为http
        'credentials'=> array(
            'secretId'  => $secretId ,
            'secretKey' => $secretKey)));
$bucket = "zzhpeng-1256184324"; //存储桶名称 格式：BucketName-APPID
$key = "exampleobject"; //filename or path


$adapter = new \Chunpat\FlysystemTencentCos\Adapter($cosClient,$bucket);

try {
    $srcPath = "/Users/zzhpeng/Documents/WechatIMG8.jpeg";//本地文件绝对路径
    $file = fopen($srcPath, "rb");
    $filesystem = new \League\Flysystem\Filesystem($adapter);

    // 设置属性
    // 如设置了Content-Type，则可以不指定路径的后缀 (即$filePath可以不包含.jpg等后缀名)
    $fInfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $fInfo->buffer($srcPath);
    $config = [
        "Content-Type" => $mimeType
    ];
//  $filePath = "test";
    $filePath = "niubi/6666666";

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


```

## Contributing

You can contribute in one of three ways:

1. File bug reports using the [issue tracker](https://github.com/chunpat/flysystem-tencent-cos/issues).
2. Answer questions or fix bugs on the [issue tracker](https://github.com/chunpat/flysystem-tencent-cos/issues).
3. Contribute new features or update the wiki.

_The code contribution process is not very formal. You just need to make sure that you follow the PSR-0, PSR-1, and PSR-2 coding guidelines. Any new code contributions must be accompanied by unit tests where applicable._

## Document
- 1、https://github.com/tencentyun/cos-php-sdk-v5
- 2、https://cloud.tencent.com/document/product/436/12266#composer

## Reference
- 1、https://github.com/thephpleague/flysystem
- 2、http://flysystem.thephpleague.com/api/

## License

MIT

