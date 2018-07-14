<?php

namespace Febalist\Laravel\Media;

use Febalist\Laravel\File\File;
use Febalist\Laravel\Media\Jobs\MediaConvert;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use URL;

/**
 * @property-read Model $model
 * @property-read File  $file
 * @property string     $disk
 * @property string     $path
 * @property integer    $size
 * @property string     $mime
 * @property array      $conversions
 * @property string     $model_type
 * @property integer    $model_id
 */
class Media extends Model
{
    protected static $force_convert = false;

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

        $name = File::slugName($name);
        $ids = $media->pluck('id')->implode(',');

        return URL::signedRoute('media.zip', [$ids, $name]);
    }

    public static function boot()
    {
        parent::boot();

        static::deleted(function (Media $model) {
            $model->deleteFiles();
        });
    }

    /** @return static */
    public static function fromFile($file, $disk = null, $name = null, $delete = false)
    {
        $name = File::slugName($name) ?: File::fileName($file, true);
        $path = static::generatePath($name);
        $disk = File::diskName($disk ?: static::defaultDisk());

        $file = File::put($file, $path, $disk, $delete);

        $size = $file->size;
        $mime = $file->mime;

        return static::create(compact('size', 'mime', 'disk', 'path'));
    }

    public static function fromRequest($keys = null, $disk = null, $name = null)
    {
        if (!$keys) {
            $keys = array_keys(request()->allFiles());
        } elseif (!is_array($keys)) {
            $keys = [$keys];
        }

        $result = collect();

        foreach ($keys as $key) {
            $files = request()->file($key);
            if (!is_array($files)) {
                $files = [$files];
            }
            foreach ($files as $file) {
                $media = static::fromFile($file, $disk, $name, true);
                $result->push($media);
            }
        }

        return $result;
    }

    public static function fromUrl($url, $disk = null, $name = null)
    {
        return static::fromFile($url, $disk, $name);
    }

    public static function setForceConvert($enabled = true)
    {
        static::$force_convert = $enabled;
    }

    protected static function defaultDisk()
    {
        return config('media.disk') ?: config('filesystems.default');
    }

    protected static function generatePath($name = '')
    {
        return File::pathJoin(config('media.path'), str_uuid(true), $name);
    }

    public function model()
    {
        return $this->morphTo();
    }

    public function associate(Eloquent $model)
    {
        $this->model()->associate($model);
        $this->save();

        $this->convert(true);

        return $this;
    }

    public function copy($disk = null)
    {
        $disk = File::diskName($disk ?: static::defaultDisk());
        $path = static::generatePath($this->file->name);

        $this->file->copy($path, $disk);

        $clone = $this->replicate(['model_type', 'model_id', 'conversions']);
        $clone->fill(compact('disk', 'path'))->save();

        return $clone;
    }

    public function move($disk = null)
    {
        $disk = File::diskName($disk ?: static::defaultDisk());
        $path = static::generatePath($this->file->name);

        $this->file->move($path, $disk);
        $this->deleteFiles();

        $conversions = [];
        $this->fill(compact('disk', 'path', 'conversions'))->save();

        return $this;
    }

    public function cloud()
    {
        return $this->move('cloud');
    }

    public function getFileAttribute()
    {
        return new File($this->path, $this->disk);
    }

    public function deleteFiles($directory = null)
    {
        return $this->file->storage()->deleteDir($this->file->directory);
    }

    public function getConversionsAttribute($value)
    {
        return list_cleanup(json_decode($value) ?: []);
    }

    public function convert($force = false, $run = false)
    {
        if ($run) {
            Media::setForceConvert($force);
            if ($force) {
                $directories = $this->file->storage->directories($this->file->directory);
                foreach ($directories as $directory) {
                    $this->file->storage->deleteDirectory($directory);
                }
                $this->update(['conversions' => []]);
            }
            if (method_exists($this->model, 'mediaConverter')) {
                $this->model->mediaConverter($this);
            }
        } else {
            if ($this->file->convertible) {
                $queue = config('media.queue');
                if ($queue) {
                    MediaConvert::dispatch($this, $force)->onQueue($queue);
                } else {
                    MediaConvert::dispatchNow($this, $force);
                }
            }
        }

        return $this;
    }

    /** @return static */
    public function converter($name, $callback)
    {
        if ($this->file->convertible) {
            if (static::$force_convert || $name && !in_array($name, $this->conversions)) {
                $file = $this->file->copyTemp();

                $image = $file->image()->optimize();
                if (is_callable($callback)) {
                    $image = $callback($image);
                }
                $image->save();

                $file->move([$this->file->directory, $name, $this->file->name], $this->disk);

                $conversions = list_cleanup(array_merge($this->conversions, [$name]));
                $this->update(compact('conversions'));
            }
        }

        return $this;
    }

    /** @return static */
    public function optimize()
    {
        return $this->converter(null, null);
    }

    /** @return Conversion|static|null */
    public function conversion($names)
    {
        $names = array_wrap($names) ?: [null];

        foreach ($names as $name) {
            if (!$name) {
                return $this;
            }

            if (in_array($name, $this->conversions)) {
                return new Conversion($this, $this->file->neighbor([$name, $this->file->name()]));
            }
        }

        return null;
    }

    /** @return string|null */
    public function conversionUrl($name, $expiration = null)
    {
        if ($conversion = $this->conversion($name)) {
            return $conversion->file->url($expiration);
        } else {
            return null;
        }
    }

    /** @return string|null */
    public function conversionView($name, $expiration = null)
    {
        if ($conversion = $this->conversion($name)) {
            return $conversion->file->view($expiration);
        } else {
            return null;
        }
    }
}
