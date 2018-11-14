<?php

namespace Febalist\Laravel\Media;

use Febalist\Laravel\Media\Resources\MediaResource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;

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
    public function getMedia($type = null)
    {
        return $this->media->when($type, function (Collection $media, $type) {
            return $media->where('type', $type);
        });
    }

    /** @return integer */
    public function hasMedia($type = null)
    {
        if ($type) {
            return $this->getMedia($type)->count();
        }

        return $this->relationLoaded('media') ? $this->media->count() : $this->media()->count();
    }

    /** @return Media|null */
    public function getFirstMedia()
    {
        return $this->getMedia()->first();
    }

    /** @return string|null */
    public function getFirstMediaUrl($conversion = null, $expiration = null)
    {
        if ($media = $this->getFirstMedia()) {
            return $media->url($conversion, $expiration);
        } else {
            return null;
        }
    }

    /** @return string|null */
    public function getFirstMediaView($conversion = null, $expiration = null)
    {
        if ($media = $this->getFirstMedia()) {
            return $media->view($conversion, $expiration);
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

    public function getMediaResourceCollection()
    {
        return MediaResource::collection($this->media);
    }

    public function mediaInput(array $options = [])
    {
        $options['name'] = $options['name'] ?? 'media';
        $options['value'] = json_parse(old($options['name']))
            ?? ($this->exists ? $this->getMediaResourceCollection()->jsonSerialize() : []);

        $data = e(json_stringify($options));

        return new HtmlString("<model-media-edit data=\"$data\"></model-media-edit>");
    }

    /** @return Collection|Media[] */
    public function updateMediaFromInput($name = 'media')
    {
        $resources = collect(json_parse(request($name), []))->keyBy('id');

        $old_ids = $this->media()->pluck('id')->toArray();

        $media = Media::findMany($resources->pluck('id'))
            ->map(function (Media $media) use ($resources, &$old_ids) {
                $resource = $resources[$media->id];
                $signature = $resource['input_signature'] ?? null;

                if ($media->checkInputSignature($signature)) {
                    $filename = filename_normalize($resource['filename'] ?? '');
                    $extension = $media->extension ? ".$media->extension" : '';
                    $media->name = $filename.$extension ?: '_';

                    if (in_array($media->id, $old_ids)) {
                        $old_ids = array_without($old_ids, $media->id);
                    } else {
                        $media->associate($this);
                    }
                }
            })->filter();

        if ($old_ids) {
            Media::whereIn('id', $old_ids)->update([
                'model_type' => null,
                'model_id' => null,
            ]);
        }

        return $media;
    }
}
