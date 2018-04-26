<?php

namespace Febalist\Laravel\Media;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Http\UploadedFile;
use Storage;
use Symfony\Component\HttpFoundation\File\File;

/**
 * @mixin \Eloquent
 * @property-read Model $model
 * @property string     $collection
 * @property string     $name
 * @property string     $extension
 * @property integer    $size
 * @property string     $mime
 * @property string     $disk
 * @property string     $path
 * @property string     $dir
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
    public static function fromFile(File $file)
    {
        $name = $file->getFilename();
        $size = $file->getSize();
        $mime = $file->getMimeType();

        if ($file instanceof UploadedFile) {
            $name = $file->getClientOriginalName() ?: $name;
            $mime = $file->getClientMimeType() ?: $mime;
        }

        $disk = static::disk();
        $path = Storage::disk($disk)->putFileAs(static::path(), $file, $name);

        return static::create(compact('size', 'mime', 'disk', 'path'));
    }

    public static function fromRequest($keys = null)
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
                $media = static::fromFile($file);
                $result->push($media);
            }
        }

        return $result;
    }

    protected static function disk()
    {
        return config('media.disk') ?: config('filesystems.default');
    }

    protected static function path($name = '')
    {
        $prefix = config('media.path');
        $prefix = $prefix ? str_finish($prefix, '/') : '';
        $name = $name ? str_start($name, '/') : '';
        $uuid = str_uuid(true);

        return "$prefix$uuid$name";
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

    public function copy($disk = null)
    {
        $disk = $disk ?: static::disk();
        $path = $this->copyFile($disk);

        $clone = $this->replicate(['model_type', 'model_id', 'collection']);
        $clone->fill(compact('disk', 'path'))->save();

        return $clone;
    }

    public function move($disk = null)
    {
        $disk = $disk ?: static::disk();
        $path = $this->copyFile($disk);

        $this->deleteFiles();
        $this->fill(compact('disk', 'path'))->save();

        return $this;
    }

    public function cloud()
    {
        return $this->move(config('filesystems.cloud'));
    }

    public function url($expiration = null)
    {
        if ($expiration) {
            return $this->storage()->temporaryUrl($this->path, $expiration);
        }

        return $this->storage()->url($this->path);
    }

    public function preview($embedded = false)
    {
        $url = $this->url;

        if (in_array($this->extension, ['jpg', 'jpeg', 'png', 'gif', 'ico', 'mp3', 'mp4', 'webm', 'txt'])) {
            return $url;
        } elseif (in_array($this->extension, ['ppt', 'pptx', 'doc', 'docx', 'xls', 'xlsx'])) {
            return 'https://view.officeapps.live.com/op/'.($embedded ? 'embed' : 'view').'.aspx?src='.urlencode($url);
        } elseif (in_array($this->extension, ['ods', 'sxc', 'csv', 'tsv'])) {
            return "https://sheet.zoho.com/sheet/view.do?&name=$this->name&url=".urlencode($url);
        } else {
            return 'https://docs.google.com/viewer?'.($embedded ? 'embedded=true&' : '').'url='.urlencode($url);
        }
    }

    public function getNameAttribute()
    {
        return pathinfo($this->path, PATHINFO_BASENAME);
    }

    public function getExtensionAttribute()
    {
        return pathinfo($this->path, PATHINFO_EXTENSION);
    }

    public function getDirAttribute()
    {
        return pathinfo($this->path, PATHINFO_DIRNAME);
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

    public function response()
    {
        return $this->storage()->response($this->path);
    }

    public function stream()
    {
        return $this->storage()->readStream($this->path);
    }

    public function deleteFiles()
    {
        return $this->storage()->deleteDir($this->dir);
    }

    public function storage()
    {
        return Storage::disk($this->disk);
    }

    protected function copyFile($disk)
    {
        $path = static::path($this->name);
        Storage::disk($disk)->putStream($path, $this->stream());

        return $path;
    }
}
