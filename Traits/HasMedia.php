<?php

namespace App\Traits;

use App\Models\Media;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

trait HasMedia
{
    private $__mediaFiles = [];

    private object $__currentMediaCollection;


    protected static function bootHasMedia()
    {
        static::deleting(function ($model) {
            Media::where('model_type', class_basename($model))?->delete();
            // ...
        });
    }

    function addMedia(UploadedFile $files)
    {
        if (is_array($files)) {
            foreach ($files as $file) {
                array_push($this->__mediaFiles, $file);
            }
            return $this;
        }

        array_push($this->__mediaFiles, $files);

        return $this;
    }



    function toCollection(string $name = 'uploads', string $disk = null)
    {

        $user = auth()->user();

        $the_disk = $disk ?? config('media.disk');

        foreach ($this->__mediaFiles as $file) {

            $media = new Media();

            $file_name =  time() . $file->getClientOriginalName();

            $media->file_name =  $file_name;

            $media->original_file_name = $file->getClientOriginalName();

            $media->mime_type = $file->getMimeType();

            $media->collection = $name;

            $media->model_id = $this->{$this->primaryKey};

            $media->model_type = class_basename($this);

            $media->user_id = $user?->id;

            $media->disk = $the_disk;

            if ($file->storeAs('uploads/',   $file_name, $the_disk)) {

                $media->save();
            }
        }
    }

    function deleteMediaCollection(string $name)
    {
        $media = $this->media($name);

        $the_disk = $disk ?? config('media.disk');

        foreach ($media->get() as $m) {
            $filepath = 'uploads/' . $m->file_name;
            Storage::disk($the_disk)->exists($filepath) && Storage::disk($the_disk)->delete($filepath);
        }

        return $media->delete();
    }

    function deleteAllMedia()
    {
        $media = $this->media();

        foreach ($media->get() as $m) {

            $the_disk = $m->disk ?? config('media.disk');
            $filepath = 'uploads/' . $m->file_name;
            Storage::disk($the_disk)->exists($filepath) && Storage::disk($the_disk)->delete($filepath);
        }
        return $media?->delete();
    }

    function media(string $collection = null)
    {
        $media = null;

        if ($collection === '*' or !$collection) {
            $media = Media::where('model_type', class_basename($this))
                ->where('model_id', $this->{$this->primaryKey});
        }

        if ($collection !== '*' and $collection) {
            $media = Media::where('model_type', class_basename($this))
                ->where('model_id', $this->{$this->primaryKey})
                ->where('collection', $collection);
        }

        $media = $media->orderBy('ordering');

        $this->__currentMediaCollection =  $media;

        return  $media;
    }


    function getMedia(string $collection = null)
    {
        return  $this->media($collection)->get();
    }

    function getFirstMedia(string $collection = '*')
    {
        return  $this->media($collection)?->first();
    }
    function getFirstMediaUrl(string $collection = '*')
    {
        return $this->media($collection)?->first()?->getUrl();
    }

    function updateMediaOrdering($items, string $collection = "*")
    {
        // Extract the photo IDs from the input array
        $ids = collect($items)->pluck('value')->toArray();



        // Fetch all photos that match the given IDs
        $this->media($collection)->whereIn('id', $ids)
            ->get()
            ->each(function ($item) use ($ids) {
                // Update the 'ordering' field based on the position of the photo ID in the $ids array
                $item->update(['ordering' => array_search($item->id, $ids) + 1]); // Adding 1 to start ordering from 1
            });
    }
}
