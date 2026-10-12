<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/** Example animation for an activity: a GIF or a short silent MP4 shown like a GIF. Only files on our own "uploads" disk. */
final class ActivityMedia
{
    public const TYPES = ['gif', 'mp4'];

    /** @return array{type: string, src: string}|null */
    public static function make(?string $path, ?string $type): ?array
    {
        if (! $path || ! in_array($type, self::TYPES, true)) {
            return null;
        }

        return ['type' => $type, 'src' => Storage::disk('uploads')->url($path)];
    }

    /** Detect the real type from the file content, not the name. */
    public static function typeOf(string $bytes): ?string
    {
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($bytes);

        return match ($mime) {
            'image/gif' => 'gif',
            'video/mp4' => 'mp4',
            default => null,
        };
    }
}
