<?php

namespace Febalist\Laravel\Media;

use Febalist\Laravel\File\File;

class Conversion
{
    use HasFile;

    public $file;
    protected $media;

    public function __construct(Media $media, File $file)
    {
        $this->media = $media;
        $this->file = $file;
    }
}
