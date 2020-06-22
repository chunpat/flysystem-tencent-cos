<?php
/**
 * Created by PhpStorm.
 * User: zzhpeng
 * Date: 19/6/2020
 * Time: 9:31 AM
 */

namespace Chunpat\FlysystemTencentCos;


use Chunpat\FlysystemTencentCos\Exception\ResultException;
use GuzzleHttp\Command\Result;
use GuzzleHttp\Command\ServiceClientInterface;
use League\Flysystem\Adapter\AbstractAdapter;
use League\Flysystem\Adapter\CanOverwriteFiles;
use League\Flysystem\AdapterInterface;
use League\Flysystem\Config;
use League\Flysystem\Util;
use Qcloud\Cos\Client;

class Adapter extends AbstractAdapter implements CanOverwriteFiles
{
    /**
     * @var array
     */
    protected static $resultMap = [
        'Body' => 'contents',
        'ContentLength' => 'size',
        'ContentType' => 'mimetype',
        'Size' => 'size',
        'Metadata' => 'metadata',
        'StorageClass' => 'storageclass',
        'ETag' => 'etag',
        'VersionId' => 'versionid'
    ];

    /**
     * @var array
     */
    protected static $metaOptions = [
        'ACL',
        'CacheControl',
        'ContentDisposition',
        'ContentEncoding',
        'ContentLength',
        'ContentType',
        'Expires',
        'GrantFullControl',
        'GrantRead',
        'GrantReadACP',
        'GrantWriteACP',
        'Metadata',
        'RequestPayer',
        'SSECustomerAlgorithm',
        'SSECustomerKey',
        'SSECustomerKeyMD5',
        'SSEKMSKeyId',
        'ServerSideEncryption',
        'StorageClass',
        'Tagging',
        'WebsiteRedirectLocation',
    ];

    /**
     * @var array
     */
    protected $result;

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var string
     */
    protected $bucket;

    /**
     * @var array
     */
    protected $options = [];

    /**
     * Constructor.
     *
     * @param ServiceClientInterface $client
     * @param string                 $bucket
     * @param string                 $prefix
     * @param array                  $options
     */
    public function __construct(ServiceClientInterface $client, $bucket, $prefix = '', array $options = [])
    {
        $this->client = $client;
        $this->bucket = $bucket;
        $this->setPathPrefix($prefix);
        $this->options = $options;
    }

    /**
     * @return array
     */
    public function getResult(): array
    {
        return $this->result;
    }

    /**
     * Get the client bucket.
     *
     * @return string
     */
    public function getBucket()
    {
        return $this->bucket;
    }

    /**
     * Set the client bucket.
     *
     * @param string $bucket
     *
     * @return string
     */
    public function setBucket($bucket)
    {
        $this->bucket = $bucket;
    }

    /**
     * Get the client instance.
     *
     * @return ServiceClientInterface
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Write a new file.
     *
     * @param string $path
     * @param string $contents
     * @param Config $config
     *
     * @return array|bool|false
     * @throws ResultException
     */
    public function write($path, $contents, Config $config)
    {
        return $this->upload($path, $contents, $config);
    }

    /**
     * Update a file.
     *
     * @param string $path
     * @param string $contents
     * @param Config $config
     *
     * @return array|bool|false
     * @throws ResultException
     */
    public function update($path, $contents, Config $config)
    {
        return $this->upload($path, $contents, $config);
    }

    /**
     * Rename a file.
     *
     * @param string $path
     * @param string $newpath
     *
     * @return bool|\GuzzleHttp\Command\ResultInterface|mixed
     * @throws ResultException
     */
    public function rename($path, $newpath)
    {
        if (!$this->copy($path, $newpath)) {
            return false;
        }

        return $this->delete($path);
    }

    /**
     * Delete a file.
     *
     * @param string $path
     *
     * @return bool|\GuzzleHttp\Command\ResultInterface|mixed
     * @throws ResultException
     */
    public function delete($path)
    {
        $command = $this->client->getCommand(
            'deleteObject',
            [
                'Bucket' => $this->bucket,
                'Key' => $path
            ]
        );

        $result = $this->client->execute($command);
        if (!($result instanceof Result)) {
            throw new ResultException('appear error when deleting a file');
        }

        $this->result = $this->normalizeResponse($result->toArray());

        return $result;
    }

    /**
     * Delete a directory.
     *
     * @param string $dirname
     *
     * @return bool|\GuzzleHttp\Command\ResultInterface|mixed
     * @throws ResultException
     */
    public function deleteDir($dirname)
    {
        $dir = $this->applyPathPrefix($dirname) . '/';
        return $this->delete($dir);
    }

    /**
     * Create a directory.
     *
     * @param string $dirname
     * @param Config $config
     *
     * @return array|bool|false
     * @throws ResultException
     */
    public function createDir($dirname, Config $config)
    {
        return $this->upload($dirname . '/', '', $config);
    }

    /**
     * Check whether a file exists.
     *
     * @param string $path
     *
     * @return bool
     */
    public function has($path)
    {
        try {
            return $this->client->doesObjectExist($this->bucket,$path,$this->options);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Read a file.
     *
     * @param string $path
     *
     * @return false|array
     */
    public function read($path)
    {
        try {
            $response = $this->readObject($path);
            return $response;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * List contents of a directory.
     *
     * @param string $directory
     * @param bool   $recursive
     *
     * @return array
     */
    public function listContents($directory = '', $recursive = false)
    {
        $prefix = $this->applyPathPrefix(rtrim($directory, '/') . '/');
        $options = ['Bucket' => $this->bucket, 'Prefix' => ltrim($prefix, '/')];

        if ($recursive === false) {
            $options['Delimiter'] = '/';
        }

        $listing = $this->retrievePaginatedListing($options);
        $normalizer = [$this, 'normalizeResponse'];

        $normalized = array_map($normalizer, $listing);

        return Util::emulateDirectories($normalized);
    }

    /**
     * @param array $options
     *
     * @return array
     */
    protected function retrievePaginatedListing(array $options)
    {
        $command = $this->client->getCommand('ListObjects', $options + $this->options);
        /** @var Result $response */
        $resultPaginator = $this->client->execute($command);
        $listing = [];

        foreach ($resultPaginator['Contents'] as $result) {
            $listing[] = $result;
        }

        return $listing;
    }

    /**
     * Get all the meta data of a file or directory.
     *
     * @param string $path
     *
     * @return false|array
     */
    public function getMetadata($path)
    {
        $command = $this->client->getCommand(
            'headObject',
            [
                'Bucket' => $this->bucket,
                'Key' => $this->applyPathPrefix($path),
            ] + $this->options
        );

        /* @var Result $result */
        $result = $this->client->execute($command);

        return $this->normalizeResponse($result->toArray(), $path);
    }

    /**
     * Get all the meta data of a file or directory.
     *
     * @param string $path
     *
     * @return false|array
     */
    public function getSize($path)
    {
        return $this->getMetadata($path);
    }

    /**
     * Get the mimetype of a file.
     *
     * @param string $path
     *
     * @return false|array
     */
    public function getMimetype($path)
    {
        return $this->getMetadata($path);
    }

    /**
     * Get the timestamp of a file.
     *
     * @param string $path
     *
     * @return false|array
     */
    public function getTimestamp($path)
    {
        return $this->getMetadata($path);
    }

    /**
     * Write a new file using a stream.
     *
     * @param string   $path
     * @param resource $resource
     * @param Config   $config Config object
     *
     * @return array|false false on failure file meta data on success
     */
    public function writeStream($path, $resource, Config $config)
    {
        return $this->client->upload($this->bucket, $path, $resource);
    }

    /**
     * Update a file using a stream.
     *
     * @param string   $path
     * @param resource $resource
     * @param Config   $config Config object
     *
     * @return array|false false on failure file meta data on success
     */
    public function updateStream($path, $resource, Config $config)
    {
        return $this->client->upload($this->bucket, $path, $resource);
    }

    /**
     * Copy a file.
     *
     * @param string $path
     * @param string $newpath
     *
     * @return bool
     * @throws \Exception
     */
    public function copy($path, $newpath)
    {
        $command = $this->client->getCommand(
            'CopyObject',
            [
                'Bucket' => $this->bucket,
                'Key' => $this->applyPathPrefix($newpath),
                'CopySource' => $this->encodeKey($this->bucket . '/' . $this->applyPathPrefix($path)),
                'ACL' => $this->getRawVisibility($path) === AdapterInterface::VISIBILITY_PUBLIC
                    ? 'public-read' : 'private',
            ] + $this->options
        );

        $this->client->execute($command);

//        $this->client->copy($this->bucket,$newpath,$path,$this->options);
        return true;
    }

    /**
     * Read a file as a stream.
     *
     * @param string $path
     *
     * @return array|false
     * @throws ResultException
     */
    public function readStream($path)
    {
        $response = $this->readObject($path);

        if ($response !== false) {
            $response['stream'] = $response['contents']->detach();
            unset($response['contents']);
        }

        return $response;
    }

    /**
     * Read an object and normalize the response.
     *
     * @param string $path
     *
     * @return array
     * @throws ResultException
     */
    protected function readObject($path)
    {
        $options = [
            'Bucket' => $this->bucket,
            'Key' => $this->applyPathPrefix($path),
        ];
        $command = $this->client->getCommand('GetObject', $options + $this->options);

        /** @var Result $response */
        $response = $this->client->execute($command);

        if (!($response instanceof Result)) {
            throw new ResultException('appear error when read a file');
        }
        return $this->normalizeResponse($response->toArray(), $path);
    }

    /**
     * Set the visibility for a file.
     *
     * @param string $path
     * @param string $visibility
     *
     * @return array|false file meta data
     */
    public function setVisibility($path, $visibility)
    {
        $command = $this->client->getCommand(
            'putObjectAcl',
            [
                'Bucket' => $this->bucket,
                'Key' => $this->applyPathPrefix($path),
                'ACL' => $visibility === AdapterInterface::VISIBILITY_PUBLIC ? 'public-read' : 'private',
            ]
        );

        $this->client->execute($command);

        return compact('path', 'visibility');
    }

    /**
     * Get the visibility of a file.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function getVisibility($path)
    {
        return ['visibility' => $this->getRawVisibility($path)];
    }

    /**
     * {@inheritdoc}
     */
    public function applyPathPrefix($path)
    {
        return ltrim(parent::applyPathPrefix($path), '/');
    }

    /**
     * {@inheritdoc}
     */
    public function setPathPrefix($prefix)
    {
        $prefix = ltrim($prefix, '/');

        return parent::setPathPrefix($prefix);
    }

    /**
     * Get the object acl presented as a visibility.
     *
     * @param string $path
     *
     * @return string
     */
    protected function getRawVisibility($path)
    {
        $command = $this->client->getCommand(
            'getObjectAcl',
            [
                'Bucket' => $this->bucket,
                'Key' => $this->applyPathPrefix($path),
            ]
        );

        $result = $this->client->execute($command);
        $visibility = AdapterInterface::VISIBILITY_PRIVATE;

        foreach ($result['Grants'] as $grant) {
            foreach ($grant['Grant'] as $grantItem) {
                if (
                    (isset($grantItem['Grantee']['URI'])
                        && $grantItem['Permission'] === 'READ') || $grantItem['Permission'] === 'FULL_CONTROL'
                ) {
                    $visibility = AdapterInterface::VISIBILITY_PUBLIC;
                    break;
                }
            }
        }

        return $visibility;
    }

    /**
     * Upload an object.
     *
     * @param        $path
     * @param        $body
     * @param Config $config
     *
     * @return bool
     * @throws ResultException
     */
    protected function upload($path, $body, Config $config)
    {
        $command = $this->client->getCommand(
            'putObject',
            [
                'Bucket' => $this->bucket,
                'Key' => $path,
                'Body' => $body
            ]
        );

        $result = $this->client->execute($command);
        if (!($result instanceof Result)) {
            throw new ResultException('appear error when upload a file');
        }

        $this->result = $this->normalizeResponse($result->toArray());
        return true;
    }

    /**
     * Check if the path contains only directories
     *
     * @param string $path
     *
     * @return bool
     */
    private function isOnlyDir($path)
    {
        return substr($path, -1) === '/';
    }

    /**
     * Get options from the config.
     *
     * @param Config $config
     *
     * @return array
     */
    protected function getOptionsFromConfig(Config $config)
    {
        $options = $this->options;

        if ($visibility = $config->get('visibility')) {
            // For local reference
            $options['visibility'] = $visibility;
            // For external reference
            $options['ACL'] = $visibility === AdapterInterface::VISIBILITY_PUBLIC ? 'public-read' : 'private';
        }

        if ($mimetype = $config->get('mimetype')) {
            // For local reference
            $options['mimetype'] = $mimetype;
            // For external reference
            $options['ContentType'] = $mimetype;
        }

        foreach (static::$metaOptions as $option) {
            if (!$config->has($option)) {
                continue;
            }
            $options[$option] = $config->get($option);
        }

        return $options;
    }

    /**
     * Normalize the object result array.
     *
     * @param array  $response
     * @param string $path
     *
     * @return array
     */
    protected function normalizeResponse(array $response, $path = null)
    {
        $result['path'] = isset($response['Key']) ? $response['Key'] : '';
        $result = array_merge($result, Util::pathinfo($result['path']));

        if (isset($response['LastModified'])) {
            $result['timestamp'] = strtotime($response['LastModified']);
        }

        if ($this->isOnlyDir($result['path'])) {
            $result['type'] = 'dir';
            $result['path'] = rtrim($result['path'], '/');

            return $result;
        }

        return array_merge($result, Util::map($response, static::$resultMap), ['type' => 'file']);
    }

    /**
     * Raw URL encode a key and allow for '/' characters
     *
     * @param string $key Key to encode
     *
     * @return string Returns the encoded key
     */
    protected function encodeKey($key)
    {
        return str_replace('%2F', '/', rawurlencode($key));
    }

}