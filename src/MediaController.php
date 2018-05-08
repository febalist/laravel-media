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

        $urls = [];
        $media->each(function (Media $media) use (&$urls) {
            $thumb = $media->conversion([request('thumb'), null]);
            if ($thumb) {
                $urls[$thumb->file->url] = $media->file->url;
            } else {
                $urls[] = $media->file->url;
            }
        });

        $url = File::galleryUrl($urls);

        return redirect($url);
    }

    public function zip($ids, $name)
    {
        $media = Media::findMany(explode(',', $ids));

        $files = $media->pluck('file');

        $url = File::zipUrl($files, $name);

        return redirect($url);
    }
}
