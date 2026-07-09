<?php

namespace App\Support;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

class DocumentStorage
{
    public static function diskName(): string
    {
        $disk = (string) config('documents.storage_disk', 's3');

        if ($disk !== 's3') {
            throw new \RuntimeException(
                'Document storage must use S3. Set DOCUMENTS_STORAGE_DISK=s3 in .env and run php artisan config:clear.'
            );
        }

        return 's3';
    }

    public static function bucket(): string
    {
        return (string) config('filesystems.disks.s3.bucket', '');
    }

    /**
     * Upload bytes to S3 and throw if the write fails (never silently fall back to local).
     */
    public static function put(string $path, $contents, array $options = []): void
    {
        static::diskName();

        try {
            $ok = static::disk()->put($path, $contents, $options);
        } catch (\Throwable $e) {
            throw new \RuntimeException('Failed to upload file to S3: '.$e->getMessage(), 0, $e);
        }

        if ($ok !== true) {
            throw new \RuntimeException(
                'Failed to upload file to S3. Check AWS credentials, bucket ('.static::bucket().'), and AWS_SSL_VERIFY in .env.'
            );
        }
    }

    public static function disk(): Filesystem
    {
        return Storage::disk(static::diskName());
    }

    /**
     * S3 keys do not require folder creation. Some IAM policies also deny HeadObject on prefixes.
     */
    public static function ensureDirectory(string $path): void
    {
        // S3 object keys do not require explicit folders.
    }

    public static function exists(string $path): bool
    {
        if ($path === '') {
            return false;
        }

        try {
            return static::disk()->exists($path);
        } catch (\Throwable) {
            return false;
        }
    }

    public static function delete(string $path): void
    {
        if ($path === '') {
            return;
        }

        try {
            static::disk()->delete($path);
        } catch (\Throwable) {
            // ignore missing or denied deletes
        }
    }

    /**
     * Direct URL for browser preview (presigned S3 URL, or null to use app proxy route).
     */
    public static function previewUrl(string $path, int $minutes = 30): ?string
    {
        if ($path === '' || static::diskName() !== 's3') {
            return null;
        }

        try {
            return static::disk()->temporaryUrl($path, now()->addMinutes($minutes));
        } catch (\Throwable) {
            return null;
        }
    }
}
