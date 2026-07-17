<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;

class DocumentUploadValidation
{
    /**
     * @return list<string>
     */
    public static function allowedExtensions(?string $configKey = null): array
    {
        $key = $configKey ?? 'documents.mimes';
        $configured = (string) config($key, 'pdf');

        return array_values(array_filter(array_map(
            static fn (string $ext): string => strtolower(trim($ext)),
            explode(',', $configured)
        )));
    }

    /**
     * @return array<string, list<string|callable>>
     */
    public static function rules(
        string $key = 'document',
        ?string $extensionsConfigKey = null,
        ?string $maxKilobytesConfigKey = null
    ): array {
        $extensionsKey = $extensionsConfigKey ?? 'documents.mimes';
        $maxKey = $maxKilobytesConfigKey ?? 'documents.max_kilobytes';
        $max = (int) config($maxKey, 10240);
        $extensions = self::allowedExtensions($extensionsKey);

        return [
            $key => [
                'required',
                'file',
                'max:'.$max,
                function (string $attribute, mixed $value, \Closure $fail) use ($extensions, $extensionsKey): void {
                    if (! $value instanceof UploadedFile) {
                        $fail('Invalid upload.');

                        return;
                    }

                    if (! self::isAllowed($value, $extensionsKey)) {
                        $fail(
                            'This file type is not allowed, or PHP could not detect the type. '
                            .'Allowed extensions: '.implode(', ', $extensions).'.'
                        );
                    }
                },
            ],
        ];
    }

    public static function isAllowed(UploadedFile $file, ?string $extensionsConfigKey = null): bool
    {
        $extensions = self::allowedExtensions($extensionsConfigKey);
        $ext = strtolower($file->getClientOriginalExtension() ?: '');

        if ($ext !== '' && in_array($ext, $extensions, true)) {
            return true;
        }

        $mime = strtolower((string) ($file->getMimeType() ?: ''));

        if ($mime === '' || $mime === 'application/octet-stream') {
            return $ext !== '' && in_array($ext, $extensions, true);
        }

        return in_array($mime, self::allowedMimeTypes(), true);
    }

    /**
     * @return list<string>
     */
    public static function allowedMimeTypes(): array
    {
        return [
            'application/pdf',
            'image/jpeg',
            'image/jpg',
            'image/pjpeg',
            'image/png',
            'image/x-png',
            'image/gif',
            'image/webp',
            'image/bmp',
            'image/x-ms-bmp',
            'image/svg+xml',
            'image/heic',
            'image/heif',
            'image/heic-sequence',
            'image/heif-sequence',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'text/plain',
            'text/csv',
            'text/rtf',
            'application/rtf',
            'message/rfc822',
            'application/vnd.ms-outlook',
        ];
    }
}
