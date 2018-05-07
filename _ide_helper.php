<?php

namespace Febalist\Laravel\Media {

    use Febalist\Laravel\File\Image;

    class Model extends \Eloquent
    {
        use HasMedia;

        public function mediaConverter(Media $media)
        {
            $media->optimize();

            $media->converter('thumb', function (Image $image) {
                return $image->fit_crop(400, 300);
            });
        }
    }
}
