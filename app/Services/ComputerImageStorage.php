<?php

namespace App\Services;

use Cloudinary\Cloudinary;
use Illuminate\Http\UploadedFile;
use RuntimeException;

class ComputerImageStorage
{
    public function upload(UploadedFile $image): array
    {
        $response = $this->cloudinary()->uploadApi()->upload($image->getRealPath(), [
            'folder' => 'adminsena/computers',
            'resource_type' => 'image',
        ]);

        $url = $response['secure_url'] ?? null;
        $publicId = $response['public_id'] ?? null;

        if (! is_string($url) || ! is_string($publicId) || $url === '' || $publicId === '') {
            throw new RuntimeException('Cloudinary no devolvió los datos de la imagen subida.');
        }

        return [
            'url' => $url,
            'public_id' => $publicId,
        ];
    }

    public function delete(string $publicId): void
    {
        $response = $this->cloudinary()->uploadApi()->destroy($publicId, [
            'invalidate' => true,
        ]);

        if (! in_array($response['result'] ?? null, ['ok', 'not found'], true)) {
            throw new RuntimeException('Cloudinary no pudo eliminar la imagen del computador.');
        }
    }

    private function cloudinary(): Cloudinary
    {
        $configuration = config('services.cloudinary');

        if (! $configuration['cloud_name'] || ! $configuration['api_key'] || ! $configuration['api_secret']) {
            throw new RuntimeException('Configura CLOUDINARY_CLOUD_NAME, CLOUDINARY_API_KEY y CLOUDINARY_API_SECRET para subir imágenes.');
        }

        return new Cloudinary([
            'cloud_name' => $configuration['cloud_name'],
            'api_key' => $configuration['api_key'],
            'api_secret' => $configuration['api_secret'],
        ]);
    }
}
