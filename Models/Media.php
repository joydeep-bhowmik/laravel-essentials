<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Media extends Model
{
    use HasFactory;



    protected $fillable = [
        'file_name',
        'original_file_name',
        'mime_type',
        'collection',
        'model_id',
        'model_type',
        'user_id',
        'disk',
        'ordering'
    ];

    /**
     * Get the URL of the media file.
     */
    public function getUrl(): string| null
    {

        $default_directory = config('media.default_directory') ?? 'uploads';

        $disk = $this->disk ?? config('media.disk'); // Use the media disk or fallback to the default

        // Get the file path relative to the storage
        $filePath = trim($default_directory, '/') . '/' . ($this->directory ? $this->directory . '/' : '') . $this->file_name;

        // Return the URL based on the disk configuration
        return Storage::disk($disk)->url($filePath);
    }

    protected static function boot()
    {
        parent::boot();

        $default_directory = config('media.default_directory') ?? 'uploads';

        static::deleting(function ($model) use ($default_directory) {
            $disk = $model->disk ?? config('media.disk'); // Fallback to default disk
            $filePath = trim($default_directory, '/') . '/' . ($model->directory ? $model->directory . '/' : '') . $model->file_name;

            // Check if the file exists before attempting to delete
            if (Storage::disk($disk)->exists($filePath)) {
                return Storage::disk($disk)->delete($filePath); // Delete the file
            }
            if (method_exists(static::class, 'onDelete')) {
                try {
                    static::onDelete($model);
                } catch (Exception $e) {
                    // Optionally, log the exception or handle it accordingly
                    // For example: Log::error('Error in onBoot: ' . $e->getMessage());
                }
            }
        });

        if (method_exists(static::class, 'onBoot')) {
            try {
                static::onBoot();
            } catch (Exception $e) {
                // Optionally, log the exception or handle it accordingly
                // For example: Log::error('Error in onBoot: ' . $e->getMessage());
            }
        }
    }
}
