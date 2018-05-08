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

        $conversion = request('conversion');

        $files = $media->map(function (Media $media) use ($conversion) {
            $conversion = $media->conversion($conversion);
            if ($conversion) {
                return $conversion->file;
            }
        })->filter();

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
}
