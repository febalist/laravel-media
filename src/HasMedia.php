<?php

namespace Febalist\Laravel\Media;

use Illuminate\Database\Eloquent\Model;
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
        static::deleted(function (Model $model) {
            if (in_array(SoftDeletes::class, class_uses_recursive($model)) && !$model->forceDeleting) {
                return;
            }
            $model->media->each(function (Media $media) {
                $media->delete();
            });
        });
    }

    public function mediaConvert(MediaConverter $converter)
    {

    }

    public function media()
    {
        return $this->morphMany(Media::class, 'model');
    }

    /** @return Media */
    public function addMedia(Media $media)
    {
        $media->associate($this);

        return $media;
    }

    /** @return Media */
    public function addMediaFromFile($file, $disk = null, $name = null, $delete = false)
    {
        $media = Media::fromFile($file, $disk, $name, $delete);
        $this->addMedia($media);

        return $media;
    }

    /** @return Collection|Media[] */
    public function addMediaFromRequest($key = null, $disk = null, $name = null)
    {
        $media = Media::fromRequest($key, $disk, $name);
        $media->each(function (Media $media) {
            $this->addMedia($media);
        });

        return $media;
    }

    /** @return Media */
    public function addMediaFromUrl($url, $disk = null, $name = null)
    {
        $media = Media::fromUrl($url, $disk, $name);
        $this->addMedia($media);

        return $media;
    }

    public function deleteMedia()
    {
        $this->getMedia()->each(function (Media $media) {
            $media->delete();
        });
    }

    public function deleteMediaExceptLast()
    {
        $this->getMedia()->sortByDesc('created_at')->slice(1)
            ->each(function (Media $media) {
                $media->delete();
            });
    }

    /** @return Collection|Media[] */
    public function getMedia()
    {
        return $this->media;
    }

    /** @return Collection|Media[] */
    public function getMediaImages()
    {
        return $this->getMedia()->filter(function (Media $media) {
            return $media->file->type() == 'image';
        });
    }

    /** @return Media|null */
    public function getFirstMedia()
    {
        return $this->getMedia()->first();
    }

    /** @return string|null */
    public function getFirstMediaUrl($expiration = null)
    {
        if ($media = $this->getFirstMedia()) {
            return $media->file->url($expiration);
        } else {
            return null;
        }
    }

    /** @return string|null */
    public function getFirstMediaView($expiration = null)
    {
        if ($media = $this->getFirstMedia()) {
            return $media->file->view($expiration);
        } else {
            return null;
        }
    }

    /** @return Conversion|null */
    public function getFirstMediaConversion($name)
    {
        if ($media = $this->getFirstMedia()) {
            return $media->getConversion($name);
        } else {
            return null;
        }
    }

    /** @return string|null */
    public function getFirstMediaConversionUrl($name, $expiration = null)
    {
        if ($media = $this->getFirstMedia()) {
            return $media->conversionUrl($name, $expiration);
        } else {
            return null;
        }
    }

    /** @return string|null */
    public function getFirstMediaConversionView($name)
    {
        if ($media = $this->getFirstMedia()) {
            return $media->conversionView($name);
        } else {
            return null;
        }
    }

    /** @return string|null */
    public function getMediaGalleryUrl()
    {
        $media = $this->getMedia();
        return $media->count() ? Media::galleryUrl($media) : null;
    }

    /** @return string|null */
    public function getMediaZipUrl($name = 'files.zip')
    {
        $media = $this->getMedia();
        return $media->count() ? Media::zipUrl($media, $name) : null;
    }
}
