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
            $media->each(function (Media $media) {
                $media->model_type = request('model_type');
                $media->model_id = request('model_id');
                $media->save();
            });
        }

        return $media->map(function (Media $media) {
            return $media->only(['id', 'size', 'mime', 'name']);
        });
    }
}
