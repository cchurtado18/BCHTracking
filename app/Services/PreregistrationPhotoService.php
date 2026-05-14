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

        // Generar ruta: storage/app/public/preregistrations/YYYY/MM/
        $year = now()->format('Y');
        $month = now()->format('m');
        $directory = "preregistrations/{$year}/{$month}";

        // Generar nombre único
        $extension = $file->getClientOriginalExtension();
        $filename = Str::uuid().'.'.$extension;
        $path = $file->storeAs($directory, $filename, 'public');

        $attrs = [
            'preregistration_id' => $preregistration->id,
            'path' => $path,
            'mime' => $file->getMimeType(),
            'size_bytes' => $file->getSize(),
        ];
        if (Schema::hasColumn('preregistration_photos', 'sort_order')) {
            $maxOrder = $preregistration->photos()->max('sort_order');
            $attrs['sort_order'] = is_numeric($maxOrder) ? ((int) $maxOrder) + 1 : 0;
        }

        // Crear registro en base de datos
        $photo = PreregistrationPhoto::create($attrs);

        return $photo;
    }

    /**
     * Get the public URL for a photo.
     */
    public function getPhotoUrl(PreregistrationPhoto $photo): string
    {
        return Storage::disk('public')->url($photo->path);
    }
}
