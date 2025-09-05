<?php

namespace App\Filesystem;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;

class EncryptedFilesystemAdapter extends FilesystemAdapter
{
    protected $encryptionKey;

    public function __construct($root, $key, $options = [])
    {
        $this->encryptionKey = $key;
        
        $adapter = new LocalFilesystemAdapter($root);
        $filesystem = new Filesystem($adapter);
        
        parent::__construct($filesystem, $adapter, $options);
    }

    public function put($path, $contents, $options = [])
    {
        $encryptedContents = $this->encrypt($contents);
        return parent::put($path, $encryptedContents, $options);
    }

    public function get($path)
    {
        $encryptedContents = parent::get($path);
        return $this->decrypt($encryptedContents);
    }

    public function readStream($path)
    {
        $stream = parent::readStream($path);
        if ($stream === false) {
            return false;
        }

        $encryptedContents = stream_get_contents($stream);
        fclose($stream);

        $decryptedContents = $this->decrypt($encryptedContents);
        $tempStream = fopen('php://temp', 'r+');
        fwrite($tempStream, $decryptedContents);
        rewind($tempStream);

        return $tempStream;
    }

    public function writeStream($path, $resource, $options = [])
    {
        $contents = stream_get_contents($resource);
        $encryptedContents = $this->encrypt($contents);
        
        $tempStream = fopen('php://temp', 'r+');
        fwrite($tempStream, $encryptedContents);
        rewind($tempStream);

        return parent::writeStream($path, $tempStream, $options);
    }

    protected function encrypt($contents)
    {
        if (empty($contents)) {
            return $contents;
        }

        return Crypt::encrypt($contents);
    }

    protected function decrypt($encryptedContents)
    {
        if (empty($encryptedContents)) {
            return $encryptedContents;
        }

        try {
            return Crypt::decrypt($encryptedContents);
        } catch (\Exception $e) {
            // If decryption fails, return the original content
            // This handles cases where files were stored unencrypted
            return $encryptedContents;
        }
    }
}
