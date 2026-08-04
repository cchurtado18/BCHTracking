<?php

namespace App\Services;

use App\Models\Preregistration;
use App\Models\PreregistrationPhoto;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PreregistrationPhotoService
{
    private const MAX_PHOTOS_PER_PREREGISTRATION = 3;

    /** Lado máximo (px) del lado más largo. */
    private const MAX_SIDE = 1600;

    /** Calidad JPEG 0–100. */
    private const JPEG_QUALITY = 78;

    /** Si el archivo ya es más liviano, no reprocesar. */
    private const SKIP_IF_UNDER_BYTES = 450_000;

    /**
     * Upload a photo for a preregistration.
     * Allows up to MAX_PHOTOS_PER_PREREGISTRATION photos per preregistration.
     *
     * @param  bool  $replace  Deprecated. Kept for compatibility, ignored in multi-photo mode.
     *
     * @throws \Exception
     */
    public function uploadPhoto(Preregistration $preregistration, UploadedFile $file, bool $replace = false): PreregistrationPhoto
    {
        $existingCount = $preregistration->photos()->count();
        if ($existingCount >= self::MAX_PHOTOS_PER_PREREGISTRATION) {
            throw new \Exception('Este preregistro ya tiene 3 fotos. Máximo permitido: 3.');
        }

        $year = now()->format('Y');
        $month = now()->format('m');
        $directory = "preregistrations/{$year}/{$month}";

        [$path, $mime, $sizeBytes] = $this->storeOptimized($file, $directory);

        $attrs = [
            'preregistration_id' => $preregistration->id,
            'path' => $path,
            'mime' => $mime,
            'size_bytes' => $sizeBytes,
        ];
        if ($this->hasSortOrderColumn()) {
            $maxOrder = $preregistration->photos()->max('sort_order');
            $attrs['sort_order'] = is_numeric($maxOrder) ? ((int) $maxOrder) + 1 : 0;
        }

        return PreregistrationPhoto::create($attrs);
    }

    /**
     * Get the public URL for a photo.
     */
    public function getPhotoUrl(PreregistrationPhoto $photo): string
    {
        return Storage::disk('public')->url($photo->path);
    }

    /**
     * Comprime/redimensiona la imagen y la guarda en el disco public.
     * Si GD no está disponible o falla, guarda el original.
     *
     * @return array{0: string, 1: string, 2: int} [path, mime, size_bytes]
     */
    private function storeOptimized(UploadedFile $file, string $directory): array
    {
        $originalSize = (int) $file->getSize();

        // Sin GD, o archivo ya liviano: guardar original
        if (! function_exists('imagecreatefromstring')
            || ($originalSize > 0 && $originalSize <= self::SKIP_IF_UNDER_BYTES)) {
            return $this->storeOriginal($file, $directory);
        }

        $binary = @file_get_contents($file->getRealPath());
        if ($binary === false || $binary === '') {
            return $this->storeOriginal($file, $directory);
        }

        $src = @imagecreatefromstring($binary);
        if ($src === false) {
            return $this->storeOriginal($file, $directory);
        }

        $src = $this->applyExifOrientation($src, $file->getRealPath());

        $width = imagesx($src);
        $height = imagesy($src);
        if ($width < 1 || $height < 1) {
            imagedestroy($src);

            return $this->storeOriginal($file, $directory);
        }

        $scale = min(1, self::MAX_SIDE / max($width, $height));
        $newW = max(1, (int) round($width * $scale));
        $newH = max(1, (int) round($height * $scale));

        if ($scale < 1) {
            $dst = imagecreatetruecolor($newW, $newH);
            if ($dst === false) {
                imagedestroy($src);

                return $this->storeOriginal($file, $directory);
            }
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $width, $height);
            imagedestroy($src);
            $src = $dst;
        }

        // Fondo blanco por si venía PNG con alpha
        $canvas = imagecreatetruecolor($newW, $newH);
        if ($canvas === false) {
            imagedestroy($src);

            return $this->storeOriginal($file, $directory);
        }
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefilledrectangle($canvas, 0, 0, $newW, $newH, $white);
        imagecopy($canvas, $src, 0, 0, 0, 0, $newW, $newH);
        imagedestroy($src);

        $filename = Str::uuid().'.jpg';
        $relativePath = trim($directory, '/').'/'.$filename;
        $absolutePath = Storage::disk('public')->path($relativePath);
        Storage::disk('public')->makeDirectory($directory);

        $ok = imagejpeg($canvas, $absolutePath, self::JPEG_QUALITY);
        imagedestroy($canvas);

        if (! $ok || ! is_file($absolutePath)) {
            return $this->storeOriginal($file, $directory);
        }

        $size = (int) filesize($absolutePath);

        // Si quedó más pesada que el original, usa el original
        if ($originalSize > 0 && $size >= $originalSize) {
            @unlink($absolutePath);

            return $this->storeOriginal($file, $directory);
        }

        return [$relativePath, 'image/jpeg', $size];
    }

    /**
     * @return array{0: string, 1: string, 2: int}
     */
    private function storeOriginal(UploadedFile $file, string $directory): array
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        if (! in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            $extension = 'jpg';
        }
        $filename = Str::uuid().'.'.$extension;
        $path = $file->storeAs($directory, $filename, 'public');

        return [$path, $file->getMimeType() ?: 'image/jpeg', (int) $file->getSize()];
    }

    /**
     * Corrige rotación EXIF (fotos de celular).
     *
     * @param  \GdImage|resource  $image
     * @return \GdImage|resource
     */
    private function applyExifOrientation($image, string $path)
    {
        if (! function_exists('exif_read_data')) {
            return $image;
        }

        $exif = @exif_read_data($path);
        if (! is_array($exif) || empty($exif['Orientation'])) {
            return $image;
        }

        return match ((int) $exif['Orientation']) {
            3 => imagerotate($image, 180, 0) ?: $image,
            6 => imagerotate($image, -90, 0) ?: $image,
            8 => imagerotate($image, 90, 0) ?: $image,
            default => $image,
        };
    }

    private function hasSortOrderColumn(): bool
    {
        static $cached = null;
        if ($cached === null) {
            $cached = Schema::hasColumn('preregistration_photos', 'sort_order');
        }

        return $cached;
    }
}
