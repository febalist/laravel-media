<?php

namespace Febalist\Laravel\Media;

use Febalist\Laravel\File\File;
use Febalist\Laravel\Media\Jobs\MediaConvert;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use URL;

/**
 * @property-read Model    $model
 * @property-read File     $file
 * @property string        $disk
 * @property string        $target_disk
 * @property string        $path
 * @property integer       $size
 * @property string        $mime
 * @property array         $conversions
 * @property string        $model_type
 * @property integer       $model_id
 * @property-read boolean  $abandoned
 * @property-read string   $name
 * @property-read string   $extension
 * @property-read string   $icon
 * @property-read string   $type
 * @property-read boolean  $local
 * @property-read resource $stream
 */
class Media extends Model
{
    protected $guarded = [];
    protected $hidden = [];

    protected $casts = [
        'conversions' => 'array',
    ];

    /** @return string */
    public static function galleryUrl($media)
    {
        if (!$media instanceof Collection) {
            $media = collect(array_wrap($media));
        }

        $ids = $media->pluck('id')->implode(',');

        return URL::signedRoute('media.gallery', [$ids]);
    }

    public static function zip($media, $name)
    {
        if (!$media instanceof Collection) {
            $media = collect(array_wrap($media));
        }

        return File::zip($media->pluck('file'), $name);
    }

    public static function zipUrl($media, $name)
    {
        if (!$media instanceof Collection) {
            $media = collect(array_wrap($media));
        }

        $ids = $media->pluck('id')->implode(',');

        return URL::signedRoute('media.zip', [$ids, $name]);
    }

    public static function boot()
    {
        parent::boot();

        static::saved(function (Media $media) {
            if ($media->model) {
                $media->model->touch();
            }
        });

        static::deleted(function (Media $media) {
            $media->deleteFiles();

            if ($media->model) {
                $media->model->touch();
            }
        });
    }

    /** @return static */
    public static function fromFile($file, $disk = null, $name = null, $delete = false)
    {
        $name = $name ?: File::fileName($file);
        $path = static::generatePath($name);
        $target_disk = File::diskName($disk ?: static::defaultDisk());
        $disk = static::preliminaryDisk();

        $file = File::put($file, $path, $disk, $delete);

        $size = $file->size();
        $mime = $file->mime();

        return static::create(compact('size', 'mime', 'disk', 'target_disk', 'path', 'name'));
    }

    /** @return Collection|static[] */
    public static function fromRequest($key = null, $disk = null, $name = null)
    {
        $result = collect();

        $files = array_wrap_flatten($key ? request()->file($key) : request()->allFiles());
        foreach ($files as $file) {
            $media = static::fromFile($file, $disk, $name, true);
            $result->push($media);
        }

        return $result;
    }

    public static function fromUrl($url, $disk = null, $name = null)
    {
        return static::fromFile($url, $disk, $name);
    }

    protected static function preliminaryDisk()
    {
        return config('media.preliminary_disk') ?: 'public';
    }

    protected static function defaultDisk()
    {
        return config('media.disk') ?: config('filesystems.default');
    }

    protected static function generatePath($name = '')
    {
        return File::pathJoin(config('media.path'), str_uuid(true), File::slugName($name));
    }

    public function model()
    {
        return $this->morphTo();
    }

    public function associate(Model $model)
    {
        $this->model()->associate($model);
        $this->save();

        $this->convert();

        return $this;
    }

    public function copy($disk = null)
    {
        $target_disk = File::diskName($disk ?: static::defaultDisk());
        $disk = $target_disk;
        $path = static::generatePath($this->file->name());

        $this->file->copy($path, $disk);
        $this->getConversions()->each(function (Conversion $conversion) use ($disk, $path) {
            $conversion->file->copy([File::pathDirectory($path), $conversion->name, $this->file->name()], $disk);
        });

        $clone = $this->replicate(['model_type', 'model_id']);
        $clone->fill(compact('disk', 'target_disk', 'path'))->save();

        return $clone;
    }

    public function move($disk = null)
    {
        $target_disk = File::diskName($disk ?: static::defaultDisk());
        $disk = $target_disk;
        $path = static::generatePath($this->file->name());

        $this->file->move($path, $disk);
        $this->getConversions()->each(function (Conversion $conversion) use ($disk, $path) {
            $conversion->file->move([File::pathDirectory($path), $conversion->name, $this->file->name()], $disk);
        });
        $this->deleteFiles();

        $this->fill(compact('disk', 'target_disk', 'path'))->save();

        return $this;
    }

    public function getFileAttribute()
    {
        return new File($this->path, $this->disk, $this->name);
    }

    public function deleteFiles($directory = null)
    {
        return $this->file->storage()->deleteDir($this->file->directory());
    }

    public function deleteConversions()
    {
        $directories = $this->file->storage()->directories($this->file->directory());
        foreach ($directories as $directory) {
            $this->file->storage()->deleteDirectory($directory);
        }
        $this->update(['conversions' => []]);
    }

    public function getConversionsAttribute($value)
    {
        return list_cleanup(json_decode($value) ?: []);
    }

    public function convert($run = false)
    {
        if ($run) {
            $this->deleteConversions();

            if (method_exists($this->model, 'mediaConvert')) {
                $conversions = $this->conversions;

                $converter = new MediaConverter($this->file);

                $this->model->mediaConvert($converter);

                foreach ($converter->results() as $result) {
                    $conversions[] = $result->name;
                    $result->file->move([$this->file->directory(), $result->name, $this->file->name()], $this->disk);
                }

                $this->update([
                    'size' => $this->file->size(),
                    'conversions' => list_cleanup($conversions),
                ]);
            }

            if ($this->target_disk && $this->disk != $this->target_disk) {
                $this->move($this->target_disk);
            }
        } else {
            if ($this->file->convertible()) {
                if ($queue = config('media.queue')) {
                    MediaConvert::dispatch($this)->onQueue($queue);
                } else {
                    MediaConvert::dispatchNow($this);
                }
            }
        }

        return $this;
    }

    /** @return Collection|Conversion[] */
    public function getConversions($check = false)
    {
        $conversions = collect();
        foreach ($this->conversions as $name) {
            $conversions->push($this->getConversion($name, $check));
        }

        return $conversions;
    }

    /** @return Conversion|self */
    public function getConversion($name, $check = false)
    {
        if ($this->hasConversion($name)) {
            return new Conversion($name, $this, $this->file->neighbor([$name, $this->file->name()]));
        }

        return $this;
    }

    public function hasConversion($name)
    {
        return in_array($name, $this->conversions);
    }

    /** @return string|null */
    public function url($conversion = null, $expiration = null)
    {
        return $this->getConversion($conversion)->file->url($expiration);
    }

    /** @return string|null */
    public function view($conversion = null, $expiration = null)
    {
        return $this->getConversion($conversion)->file->view($expiration);
    }

    public function getAbandonedAttribute()
    {
        if (!$this->model_id) {
            return true;
        }

        if ($this->relationLoaded('model')) {
            return false;
        }

        $query = $this->model();
        if (method_exists($query, 'withTrashed')) {
            $query = $query->withTrashed();
        }

        return !$query->count();
    }

    public function getNameAttribute()
    {
        return $this->attributes['name'] ?? $this->file->name();
    }

    public function getExtensionAttribute()
    {
        return $this->file->extension();
    }

    public function getIconAttribute()
    {
        return $this->file->icon();
    }

    public function getTypeAttribute()
    {
        return $this->file->type();
    }

    public function getStreamAttribute()
    {
        return $this->file->stream();
    }

    public function getLocalAttribute()
    {
        return $this->file->local();
    }
}
