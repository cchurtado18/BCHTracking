<?php

namespace App\Services;

use App\Models\Preregistration;
use App\Models\PreregistrationPhoto;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PreregistrationPhotoService
{
    /**
     * Upload a photo for a preregistration.
     * Only one photo is allowed per preregistration. If replace is true, deletes existing photo.
     *
     * @param Preregistration $preregistration
     * @param UploadedFile $file
     * @param bool $replace If true, replaces existing photo. If false, throws error if photo exists.
     * @return PreregistrationPhoto
     * @throws \Exception
     */
    public function uploadPhoto(Preregistration $preregistration, UploadedFile $file, bool $replace = false): PreregistrationPhoto
    {
        // Verificar si ya existe una foto
        $existingPhoto = $preregistration->photos()->first();
        
        if ($existingPhoto && !$replace) {
            throw new \Exception('El preregistro ya tiene una foto. Solo se permite una foto por paquete.');
        }

        // Si hay foto existente y se debe reemplazar, eliminarla
        if ($existingPhoto && $replace) {
            // Eliminar archivo físico
            if (Storage::disk('public')->exists($existingPhoto->path)) {
                Storage::disk('public')->delete($existingPhoto->path);
            }
            // Eliminar registro de base de datos
            $existingPhoto->delete();
        }

        // Generar ruta: storage/app/public/preregistrations/YYYY/MM/
        $year = now()->format('Y');
        $month = now()->format('m');
        $directory = "preregistrations/{$year}/{$month}";

        // Generar nombre único
        $extension = $file->getClientOriginalExtension();
        $filename = Str::uuid() . '.' . $extension;
        $path = $file->storeAs($directory, $filename, 'public');

        // Crear registro en base de datos
        $photo = PreregistrationPhoto::create([
            'preregistration_id' => $preregistration->id,
            'path' => $path,
            'mime' => $file->getMimeType(),
            'size_bytes' => $file->getSize(),
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

