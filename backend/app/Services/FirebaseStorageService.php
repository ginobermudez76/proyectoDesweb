<?php

namespace App\Services;

use Google\Cloud\Storage\StorageClient;
use Illuminate\Support\Facades\Log;

class FirebaseStorageService
{
    protected $storage;
    protected $bucket;
    protected $isConfigured = false;

    public function __construct()
    {
        $projectId = config('services.firebase.project_id');
        $bucketName = config('services.firebase.storage_bucket');
        $credentialsPath = base_path(config('services.firebase.credentials_path', 'storage/firebase-credentials.json'));

        if ($projectId && $bucketName && file_exists($credentialsPath)) {
            try {
                $this->storage = new StorageClient([
                    'projectId' => $projectId,
                    'keyFilePath' => $credentialsPath,
                ]);
                $this->bucket = $this->storage->bucket($bucketName);
                $this->isConfigured = true;
            } catch (\Exception $e) {
                Log::warning('Error inicializando Firebase Storage, se usará almacenamiento local alternativo: ' . $e->getMessage());
                $this->isConfigured = false;
            }
        } else {
            Log::info('Firebase Storage no configurado en config/services.php (faltan Project ID, Bucket o archivo JSON). Usando almacenamiento local.');
            $this->isConfigured = false;
        }
    }

    /**
     * Sube un archivo a Firebase Storage y retorna su URL pública (o fallback a local)
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @param string $folder
     * @return string
     */
    public function upload($file, $folder = 'evidencias')
    {
        $mimeType = $file->getClientMimeType();
        $isImage  = strpos($mimeType, 'image/') === 0;

        $tempPath = ($isImage && extension_loaded('gd'))
            ? $this->getOptimizedFilePath($file)
            : null;

        $uploadFilePath = $tempPath ?? $file->getRealPath();

        if (!$this->isConfigured || !$this->bucket) {
            return $this->storeLocally($file, $folder, $tempPath);
        }

        return $this->uploadToFirebase($file, $folder, $uploadFilePath, $tempPath);
    }

    /**
     * Tries to create an optimized temp file for images exceeding 1MB (1024 * 1024 bytes); returns null on failure or if smaller.
     */
    private function getOptimizedFilePath($file): ?string
    {
        // Solo comprimir imágenes que superen 1MB (1,048,576 bytes)
        $oneMegabyteInBytes = 1024 * 1024;
        if ($file->getSize() <= $oneMegabyteInBytes) {
            return null;
        }

        try {
            return $this->optimizeImage($file->getRealPath());
        } catch (\Exception $e) {
            Log::warning('No se pudo optimizar la imagen, se subirá el archivo original: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Stores a file locally in Laravel's public disk and returns its URL.
     */
    private function storeLocally($file, string $folder, ?string $tempPath): string
    {
        if ($tempPath) {
            $fileName = uniqid('ev_', true) . '.' . $file->getClientOriginalExtension();
            $path     = $folder . '/' . $fileName;
            copy($tempPath, storage_path('app/public/' . $path));
        } else {
            $path = $file->store($folder, 'public');
        }

        if ($tempPath && file_exists($tempPath)) {
            unlink($tempPath);
        }

        return asset('storage/' . $path);
    }

    /**
     * Uploads the file to Firebase Storage; falls back to local on error.
     */
    private function uploadToFirebase($file, string $folder, string $uploadFilePath, ?string $tempPath): string
    {
        try {
            $extension = $file->getClientOriginalExtension();
            $fileName  = $folder . '/' . uniqid('ev_', true) . ($extension ? '.' . $extension : '');

            $this->bucket->upload(fopen($uploadFilePath, 'r'), ['name' => $fileName]);

            if ($tempPath && file_exists($tempPath)) {
                unlink($tempPath);
            }

            $bucketName  = config('services.firebase.storage_bucket');
            $encodedName = urlencode($fileName);

            return "https://firebasestorage.googleapis.com/v0/b/{$bucketName}/o/{$encodedName}?alt=media";
        } catch (\Exception $e) {
            Log::error('Fallo al subir archivo a Firebase Storage: ' . $e->getMessage() . '. Usando local.');
            return $this->storeLocally($file, $folder, $tempPath);
        }
    }

    /**
     * Comprime y redimensiona una imagen usando GD
     *
     * @param string $sourcePath
     * @param string $mimeType
     * @param int $maxWidth
     * @param int $maxHeight
     * @param int $quality
     * @return string|null Ruta del archivo temporal optimizado, o null si falla
     */
    private function optimizeImage($sourcePath, $maxWidth = 1200, $maxHeight = 1200, $quality = 75)
    {
        if (!file_exists($sourcePath)) {
            return null;
        }

        list($origWidth, $origHeight, $imageType) = getimagesize($sourcePath);

        switch ($imageType) {
            case IMAGETYPE_JPEG:
                $image = @imagecreatefromjpeg($sourcePath);
                break;
            case IMAGETYPE_PNG:
                $image = @imagecreatefrompng($sourcePath);
                break;
            default:
                return null;
        }

        if (!$image) {
            return null;
        }

        // Calcular proporciones manteniendo relación de aspecto
        $ratio = $origWidth / $origHeight;
        $width = $origWidth;
        $height = $origHeight;

        if ($width > $maxWidth || $height > $maxHeight) {
            if ($width / $maxWidth > $height / $maxHeight) {
                $width = $maxWidth;
                $height = round($maxWidth / $ratio);
            } else {
                $height = $maxHeight;
                $width = round($maxHeight * $ratio);
            }
        }

        $newImage = imagecreatetruecolor($width, $height);

        // Preservar canales alfa y transparencia para PNG
        if ($imageType == IMAGETYPE_PNG) {
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
            $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
            imagefilledrectangle($newImage, 0, 0, $width, $height, $transparent);
        }

        imagecopyresampled($newImage, $image, 0, 0, 0, 0, $width, $height, $origWidth, $origHeight);

        // Crear archivo temporal
        $tempFile = tempnam(sys_get_temp_dir(), 'img_opt_');

        if ($imageType == IMAGETYPE_JPEG) {
            imagejpeg($newImage, $tempFile, $quality);
        } else {
            // Calidad PNG: Compresión de 0 (ninguna) a 9 (máxima)
            $pngQuality = round((100 - $quality) / 10);
            $pngQuality = min(max($pngQuality, 0), 9);
            imagepng($newImage, $tempFile, $pngQuality);
        }

        imagedestroy($image);
        imagedestroy($newImage);

        return $tempFile;
    }
}
