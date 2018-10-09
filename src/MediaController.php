<?php

namespace Febalist\Laravel\Media;

use App\Http\Controllers\Controller;
use Febalist\Laravel\File\File;

class MediaController extends Controller
{
    public function __construct()
    {
        $this->middleware('signed')->only('gallery', 'zip');
    }

    public function gallery($ids)
    {
        $media = Media::findMany(explode(',', $ids));

        $files = $media->pluck('file');

        $url = File::galleryUrl($files);

        return redirect($url);
    }

    public function zip($ids, $name)
    {
        $media = Media::findMany(explode(',', $ids));

        $files = $media->pluck('file');

        $url = File::zipUrl($files, $name);

        return redirect($url);
    }

    public function upload()
    {
        $media = Media::fromRequest();

        if (request()->has('model_type', 'model_id')) {
            $class = request('model_type');
            $id = request('model_id');
            if (class_exists($class) && $id) {
                $model = $class::find($id);
                if ($model) {
                    $media->each(function (Media $media) use ($model) {
                        $media->associate($model);
                    });
                }
            }
        }

        return $media->map(function (Media $media) {
            return $media->only(['id', 'size', 'mime', 'name']);
        });
    }
}
