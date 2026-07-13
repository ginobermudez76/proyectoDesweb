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
        if (!$this->isConfigured || !$this->bucket) {
            // Fallback: almacenar en disco public local de Laravel
            $path = $file->store($folder, 'public');
            return asset('storage/' . $path);
        }

        try {
            $extension = $file->getClientOriginalExtension();
            $fileName = $folder . '/' . uniqid('ev_', true) . ($extension ? '.' . $extension : '');

            // Subir a Firebase Storage usando stream para optimizar memoria
            $this->bucket->upload(
                fopen($file->getRealPath(), 'r'),
                [
                    'name' => $fileName,
                ]
            );

            // Firebase Storage utiliza este formato de URL para acceso de lectura pública sin firma
            $bucketName = config('services.firebase.storage_bucket');
            $encodedName = urlencode($fileName);

            return "https://firebasestorage.googleapis.com/v0/b/{$bucketName}/o/{$encodedName}?alt=media";
        } catch (\Exception $e) {
            Log::error('Fallo al subir archivo a Firebase Storage: ' . $e->getMessage() . '. Usando local.');
            $path = $file->store($folder, 'public');
            return asset('storage/' . $path);
        }
    }
}
