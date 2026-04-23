<?php

namespace App\Services;

use App\Models\Preregistration;
use App\Models\PreregistrationPhoto;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PreregistrationPhotoService
{
    private const MAX_PHOTOS_PER_PREREGISTRATION = 3;

    /**
     * Upload a photo for a preregistration.
     * Allows up to MAX_PHOTOS_PER_PREREGISTRATION photos per preregistration.
     *
     * @param Preregistration $preregistration
     * @param UploadedFile $file
     * @param bool $replace Deprecated. Kept for compatibility, ignored in multi-photo mode.
     * @return PreregistrationPhoto
     * @throws \Exception
     */
    public function uploadPhoto(Preregistration $preregistration, UploadedFile $file, bool $replace = false): PreregistrationPhoto
    {
        $existingCount = $preregistration->photos()->count();
        if ($existingCount >= self::MAX_PHOTOS_PER_PREREGISTRATION) {
            throw new \Exception('Este preregistro ya tiene 3 fotos. Máximo permitido: 3.');
        }

        // Generar ruta: storage/app/public/preregistrations/YYYY/MM/
        $year = now()->format('Y');
        $month = now()->format('m');
        $directory = "preregistrations/{$year}/{$month}";

        // Generar nombre único
        $extension = $file->getClientOriginalExtension();
        $filename = Str::uuid() . '.' . $extension;
        $path = $file->storeAs($directory, $filename, 'public');

        $maxOrder = $preregistration->photos()->max('sort_order');
        $nextOrder = is_numeric($maxOrder) ? ((int) $maxOrder) + 1 : 0;

        // Crear registro en base de datos
        $photo = PreregistrationPhoto::create([
            'preregistration_id' => $preregistration->id,
            'path' => $path,
            'mime' => $file->getMimeType(),
            'size_bytes' => $file->getSize(),
            'sort_order' => $nextOrder,
        ]);

        return $photo;
    }

    /**
     * Get the public URL for a photo.
     *
     * @param PreregistrationPhoto $photo
     * @return string
     */
    public function getPhotoUrl(PreregistrationPhoto $photo): string
    {
        return Storage::disk('public')->url($photo->path);
    }
}

