<?php

namespace Febalist\Laravel\Media;

use Febalist\Laravel\File\File;

class Conversion
{
    public $name;
    public $file;
    protected $media;

    public function __construct($name, Media $media, File $file)
    {
        $this->media = $media;
        $this->file = $file;
    }
}
