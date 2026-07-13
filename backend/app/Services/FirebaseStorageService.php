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
        $isImage = strpos($mimeType, 'image/') === 0;

        $tempPath = null;
        $uploadFilePath = $file->getRealPath();

        // Si es una imagen y la extensión GD está activa, la optimizamos
        if ($isImage && extension_loaded('gd')) {
            try {
                $optimized = $this->optimizeImage($file->getRealPath(), $mimeType);
                if ($optimized) {
                    $tempPath = $optimized;
                    $uploadFilePath = $optimized;
                }
            } catch (\Exception $e) {
                Log::warning('No se pudo optimizar la imagen, se subirá el archivo original: ' . $e->getMessage());
            }
        }

        if (!$this->isConfigured || !$this->bucket) {
            // Fallback: almacenar en disco public local de Laravel
            if ($tempPath) {
                $fileName = uniqid('ev_', true) . '.' . $file->getClientOriginalExtension();
                $path = $folder . '/' . $fileName;
                // Mover archivo temporal al disco local público
                copy($tempPath, storage_path('app/public/' . $path));
            } else {
                $path = $file->store($folder, 'public');
            }

            if ($tempPath && file_exists($tempPath)) {
                unlink($tempPath);
            }
            return asset('storage/' . $path);
        }

        try {
            $extension = $file->getClientOriginalExtension();
            $fileName = $folder . '/' . uniqid('ev_', true) . ($extension ? '.' . $extension : '');

            // Subir a Firebase Storage usando stream
            $this->bucket->upload(
                fopen($uploadFilePath, 'r'),
                [
                    'name' => $fileName,
                ]
            );

            if ($tempPath && file_exists($tempPath)) {
                unlink($tempPath);
            }

            // Firebase Storage utiliza este formato de URL para acceso de lectura pública sin firma
            $bucketName = config('services.firebase.storage_bucket');
            $encodedName = urlencode($fileName);

            return "https://firebasestorage.googleapis.com/v0/b/{$bucketName}/o/{$encodedName}?alt=media";
        } catch (\Exception $e) {
            Log::error('Fallo al subir archivo a Firebase Storage: ' . $e->getMessage() . '. Usando local.');
            if ($tempPath && file_exists($tempPath)) {
                $fileName = uniqid('ev_', true) . '.' . $file->getClientOriginalExtension();
                $path = $folder . '/' . $fileName;
                copy($tempPath, storage_path('app/public/' . $path));
                unlink($tempPath);
            } else {
                $path = $file->store($folder, 'public');
            }
            return asset('storage/' . $path);
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
    private function optimizeImage($sourcePath, $mimeType, $maxWidth = 1200, $maxHeight = 1200, $quality = 75)
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
