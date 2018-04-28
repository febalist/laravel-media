<?php

namespace Febalist\Laravel\Media;

use Febalist\Laravel\File\File;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Model as Eloquent;
use URL;

/**
 * @mixin \Eloquent
 * @property-read Model $model
 * @property-read File  $file
 * @property string     $collection
 * @property string     $name
 * @property string     $slug
 * @property string     $extension
 * @property integer    $size
 * @property string     $mime
 * @property string     $disk
 * @property string     $path
 * @property string     $directory
 * @property boolean    $local
 * @property string     $url
 * @property string     $preview
 * @property string     $embedded
 * @property array|null $manipulations
 */
class Media extends Model
{
    protected $guarded = [];
    protected $hidden = [];
    protected $casts = [
        'manipulations' => 'array',
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

    protected static function slug($filename)
    {
        $name = str_slug(pathinfo($filename, PATHINFO_FILENAME), '_') ?: '_';
        $extension = pathinfo($filename, PATHINFO_EXTENSION);

        return $name.($extension ? ".$extension" : '');
    }

    protected static function disk()
    {
        return config('media.disk') ?: config('filesystems.default');
    }

    protected static function path($name = '')
    {
        return File::join(config('media.path'), str_uuid(true), $name);
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

        $clone = $this->replicate(['model_type', 'model_id', 'collection', 'manipulations']);
        $clone->fill(compact('disk', 'path'))->save();

        return $clone;
    }

    public function move($disk = null)
    {
        $disk = $disk ?: static::disk();
        $path = static::path($this->name);

        $this->file->move($path, $disk);
        $this->deleteFiles();

        $this->fill(compact('disk', 'path'))->save();

        return $this;
    }

    public function cloud()
    {
        return $this->move('cloud');
    }

    public function url($expiration = null)
    {
        $url = $this->file->url($expiration);
        if (!starts_with($url, ['http://', 'https://'])) {
            return URL::signedRoute('media.download', [$this, $this->slug], $expiration);
        }

        return $url;
    }

    public function preview($embedded = false)
    {
        $extension = $this->extension;
        $url = $this->url;

        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'ico', 'mp3', 'mp4', 'webm', 'txt'])) {
            return $url;
        } elseif (in_array($extension, ['ppt', 'pptx', 'doc', 'docx', 'xls', 'xlsx'])) {
            return 'https://view.officeapps.live.com/op/'.($embedded ? 'embed' : 'view').'.aspx?src='.urlencode($url);
        } elseif (in_array($extension, ['ods', 'sxc', 'csv', 'tsv'])) {
            return "https://sheet.zoho.com/sheet/view.do?&name=$this->name&url=".urlencode($url);
        } else {
            return 'https://docs.google.com/viewer?'.($embedded ? 'embedded=true&' : '').'url='.urlencode($url);
        }
    }

    public function getFileAttribute()
    {
        return $this->file();
    }

    public function getNameAttribute()
    {
        return $this->file->name();
    }

    public function getSlugAttribute()
    {
        return static::slug($this->name);
    }

    public function getExtensionAttribute()
    {
        return $this->file->extension();
    }

    public function getDirectoryAttribute()
    {
        return $this->file->directory();
    }

    public function getLocalAttribute()
    {
        return $this->file->local();
    }

    public function getUrlAttribute()
    {
        return $this->url();
    }

    public function getPreviewAttribute()
    {
        return $this->preview();
    }

    public function getEmbeddedAttribute()
    {
        return $this->preview(true);
    }

    public function response($filename = null, $headers = [])
    {
        return $this->file->response($filename, $headers);
    }

    public function stream()
    {
        return $this->file->stream();
    }

    public function deleteFiles($directory = null)
    {
        return $this->file->storage()->deleteDir($this->directory);
    }

}
