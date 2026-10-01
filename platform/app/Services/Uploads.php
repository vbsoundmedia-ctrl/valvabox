<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/** All artist files live on the private "local" disk and are served through authorised routes. */
class Uploads
{
    public function store(UploadedFile $file, string $folder): string
    {
        return $file->store($folder, 'local');
    }

    /** Make a small JPEG thumbnail of the artwork for the dashboard (GD). Returns the path or null. */
    public function thumbnail(string $path, int $size = 600): ?string
    {
        try {
            if (! function_exists('imagecreatefromstring')) {
                return null;
            }
            $src = @imagecreatefromstring(Storage::disk('local')->get($path));
            if (! $src) {
                return null;
            }
            $dst = imagecreatetruecolor($size, $size);
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $size, $size, imagesx($src), imagesy($src));
            ob_start();
            imagejpeg($dst, null, 82);
            $thumb = dirname($path).'/thumb_'.pathinfo($path, PATHINFO_FILENAME).'.jpg';
            Storage::disk('local')->put($thumb, ob_get_clean());
            imagedestroy($src);
            imagedestroy($dst);

            return $thumb;
        } catch (\Throwable) {
            return null;
        }
    }

    public function delete(?string ...$paths): void
    {
        foreach (array_filter($paths) as $p) {
            Storage::disk('local')->delete($p);
        }
    }

    /** Largest upload PHP will accept, in bytes. */
    public static function maxUploadBytes(): int
    {
        $parse = function (string $v): int {
            $v = trim($v);
            $n = (int) $v;

            return match (strtolower(substr($v, -1))) {
                'g' => $n * 1024 ** 3, 'm' => $n * 1024 ** 2, 'k' => $n * 1024, default => $n,
            };
        };
        $limits = array_filter([$parse((string) ini_get('upload_max_filesize')), $parse((string) ini_get('post_max_size'))]);

        return $limits ? min($limits) : 2 * 1024 ** 2;
    }

    public static function maxUploadHuman(): string
    {
        return round(self::maxUploadBytes() / 1024 / 1024).' MB';
    }
}
