<?php

namespace Febalist\Laravel\Media;

use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

/**
 * @mixin Model
 * @property-read \Illuminate\Database\Eloquent\Collection|Media[] media
 */
trait HasMedia
{
    public static function bootHasMedia()
    {
        static::deleted(function (Eloquent $model) {
            if (in_array(SoftDeletes::class, class_uses_recursive($model)) && !$model->forceDeleting) {
                return;
            }
            $model->media->each(function (Media $media) {
                $media->delete();
            });
        });
    }

    public function mediaConverter(Media $media)
    {

    }

    public function media()
    {
        return $this->morphMany(Media::class, 'model');
    }

    /** @return Media */
    public function addMedia(Media $media, $collection = null)
    {
        $media->associate($this, $collection);

        return $media;
    }

    /** @return Media */
    public function addMediaFromFile($file, $collection = null, $disk = null)
    {
        $media = Media::fromFile($file, $disk);
        $this->addMedia($media, $collection);

        return $media;
    }

    /** @return Collection|Media[] */
    public function addMediaFromRequest($keys = null, $collection = null, $disk = null)
    {
        $media = Media::fromRequest($keys, $disk);
        $media->each(function (Media $media) use ($collection) {
            $this->addMedia($media, $collection);
        });

        return $media;
    }

    /** @return Media */
    public function addMediaFromUrl($url, $collection = null, $disk = null)
    {
        $media = Media::fromUrl($url, $disk);
        $this->addMedia($media, $collection);

        return $media;
    }

    /** @return Collection|Media[] */
    public function getAllMedia()
    {
        return $this->media;
    }

    /** @return Collection|Media[] */
    public function getMedia($collection = null)
    {
        return $this->getAllMedia()->where('collection', $collection);
    }

    /** @return Collection|Media[] */
    public function getMediaImages($collection = null)
    {
        return $this->getMedia($collection)->filter(function (Media $media) {
            return $media->file->type == 'image';
        });
    }

    /** @return Media|null */
    public function getFirstMedia($collection = null)
    {
        return $this->getMedia($collection)->first();
    }

    /** @return string|null */
    public function getFirstMediaUrl($collection = null, $expiration = null)
    {
        if ($media = $this->getFirstMedia($collection)) {
            return $media->file->url($expiration);
        } else {
            return null;
        }
    }

    /** @return string|null */
    public function getFirstMediaPreview($collection = null, $embedded = false)
    {
        if ($media = $this->getFirstMedia($collection)) {
            return $media->file->preview($embedded);
        } else {
            return null;
        }
    }

    /** @return Conversion|null */
    public function getFirstMediaConversion($collection = null, $name)
    {
        if ($media = $this->getFirstMedia($collection)) {
            return $media->conversion($name);
        } else {
            return null;
        }
    }

    /** @return string|null */
    public function getFirstMediaConversionUrl($collection = null, $name, $expiration = null)
    {
        if ($media = $this->getFirstMedia($collection)) {
            return $media->conversionUrl($name, $expiration);
        } else {
            return null;
        }
    }

    /** @return string|null */
    public function getFirstMediaConversionPreview($collection = null, $name, $embedded = false)
    {
        if ($media = $this->getFirstMedia($collection)) {
            return $media->conversionPreview($name, $embedded);
        } else {
            return null;
        }
    }

    public function mediaConvert($collection = null, $force = false)
    {
        $this->getMedia($collection)->each(function (Media $media) use ($force) {
            $media->convert($force);
        });
    }

    /** @return string */
    public function mediaGalleryUrl($collection = null, $thumb_conversion = null)
    {
        return Media::galleryUrl($this->getMediaImages($collection), $thumb_conversion);
    }
}
