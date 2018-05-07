<?php

namespace Febalist\Laravel\Media;

use Febalist\Laravel\File\File;
use Febalist\Laravel\Media\Model as HasMediaModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * @mixin \Eloquent
 * @property-read HasMediaModel $model
 * @property-read File          $file
 * @property string             $collection
 * @property string             $name
 * @property string             $extension
 * @property string             $mime
 * @property string             $disk
 * @property string             $path
 * @property array              $conversions
 */
class Media extends Model
{
    use HasFile;

    protected $guarded = [];
    protected $hidden = [];
    protected $casts = [
        'conversions' => 'array',
    ];

    public static function boot()
    {
        parent::boot();

        static::deleted(function (Media $model) {
            $model->deleteFiles();
        });
    }

    /** @return static */
    public static function fromFile($file, $disk = null)
    {
        $name = File::filename($file);
        $path = static::path($name);
        $disk = $disk ?: static::disk();

        $file = File::put($file, $path, $disk);

        $size = $file->size();
        $mime = $file->mime();

        return static::create(compact('size', 'mime', 'disk', 'path'));
    }

    public static function fromRequest($keys = null, $disk = null)
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
                $media = static::fromFile($file, $disk);
                $result->push($media);
            }
        }

        return $result;
    }

    public static function fromUrl($url, $disk = null)
    {
        return static::fromFile($url, $disk);
    }

    protected static function disk()
    {
        return config('media.disk') ?: config('filesystems.default');
    }

    protected static function path($name = '')
    {
        return File::path(config('media.path'), str_uuid(true), $name);
    }

    public function model()
    {
        return $this->morphTo();
    }

    public function associate(Eloquent $model, $collection = null)
    {
        $this->model()->associate($model);
        $this->collection = $collection;
        $this->save();

        $this->convert();

        return $this;
    }

    public function file()
    {
        return new File($this->path, $this->disk);
    }

    public function copy($disk = null)
    {
        $disk = $disk ?: static::disk();
        $path = static::path($this->name);

        $this->file->copy($path, $disk);

        $clone = $this->replicate(['model_type', 'model_id', 'collection', 'conversions']);
        $clone->fill(compact('disk', 'path'))->save();

        return $clone;
    }

    public function move($disk = null)
    {
        $disk = $disk ?: static::disk();
        $path = static::path($this->name);

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
        return $this->file();
    }

    public function getNameAttribute()
    {
        return $this->file->name();
    }

    public function getExtensionAttribute()
    {
        return $this->file->extension();
    }

    public function deleteFiles($directory = null)
    {
        return $this->file->storage()->deleteDir($this->directory);
    }

    public function getConversionsAttribute($value)
    {
        return list_cleanup(json_decode($value) ?: []);
    }

    public function convert()
    {
        $queue = config('media.queue');
        MediaConvert::dispatch($this)->onQueue($queue);

        return $this;
    }

    /** @return static */
    public function converter($name, $callback)
    {
        $file = $this->file->copy(File::temp('jpg'), 'local');

        $image = $file->image()->optimize();
        if (is_callable($callback)) {
            $image = $callback($image);
        }
        $image->save();

        $file->move([$this->file->directory(), $name, $this->name], $this->disk);

        $conversions = list_cleanup(array_merge($this->conversions, [$name]));
        $this->update(compact('conversions'));

        return $this;
    }

    /** @return static */
    public function optimize()
    {
        return $this->converter(null, null);
    }

    /** @return Conversion|static|null */
    public function conversion($name)
    {
        foreach (array_wrap($name) as $name) {
            if (!$name) {
                return $this;
            }

            if (in_array($name, $this->conversions)) {
                return new Conversion($this, $this->file->neighbor([$name, $this->name]));
            }
        }

        return null;
    }

    public function conversionUrl($name, $expiration = null)
    {
        return optional($this->conversion($name))->url($expiration);
    }
}
