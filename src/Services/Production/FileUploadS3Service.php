<?php

namespace LaravelRocket\Foundation\Services\Production;

use Aws\S3\S3Client;
use LaravelRocket\Foundation\Services\FileUploadS3ServiceInterface;

class FileUploadS3Service extends FileUploadService implements FileUploadS3ServiceInterface
{

    public function upload($srcPath, $mediaType, $filename, $attributes)
    {
        $region = array_get($attributes, 'region', config('storage.s3.region'));
        $bucket = $this->decideBucket(array_get($attributes, 'buckets', $this->getDefaultBucket()));
        $key = array_get($attributes, 'key');

        $client = $this->getS3Client($region);

        $url = '';
        $success = false;

        if (file_exists($srcPath)) {
            $client->putObject([
                'Bucket'      => $bucket,
                'Key'         => $key,
                'SourceFile'  => $srcPath,
                'ContentType' => $mediaType,
                'ACL'         => 'public-read',
            ]);

            $url = $client->getObjectUrl($bucket, $key);
            $success = true;
        }

        return [
            'success' => $success,
            'url'     => $url,
            'bucket'  => $bucket,
            'key'     => $key,
            'region'  => $region,
        ];
    }

    public function delete($attributes)
    {
        $region = array_get($attributes, 'region', config('storage.s3.region'));
        $bucket = array_get($attributes, 'bucket', $this->getDefaultBucket());
        $key = array_get($attributes, 'key');

        $success = false;

        if (!empty($key)) {
            $client = $this->getS3Client($region);
            $client->deleteObject([
                'Bucket' => $bucket,
                'Key'    => $key,
            ]);
        }

        return [
            'success' => $success,
        ];
    }

    protected function getDefaultBucket()
    {
        $buckets = config('storage.s3.buckets');

        return $this->decideBucket($buckets);
    }

    protected function decideBucket($buckets, $default = null)
    {
        if (is_array($buckets)) {
            $pos = ord(time() % 10) % count($buckets);

            return $buckets[$pos];
        }

        if (is_string($buckets)) {
            return $buckets;
        }

        return $default;
    }

    /**
     * @param string $region
     *
     * @return S3Client
     */
    protected function getS3Client($region)
    {
        $config = config('aws');

        return new S3Client([
            'credentials' => [
                'key'    => array_get($config, 'key'),
                'secret' => array_get($config, 'secret'),
            ],
            'region'      => $region,
            'version'     => 'latest',
        ]);
    }
}
