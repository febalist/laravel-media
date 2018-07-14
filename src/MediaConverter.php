<?php

namespace Febalist\Laravel\Media;

use Febalist\Laravel\File\File;
use Febalist\Laravel\File\Image;

/** @mixin Image */
class MediaConverter
{
    public $name;
    /** @var File $file */
    public $file;
    /** @var Image|null $image */
    protected $image;
    /** @var self[] */
    protected $converters = [];

    public function __construct(File $source, $name = null)
    {
        $this->name = $name;
        $this->file = $source->copyTemp();
        $this->image = $this->file->image();
    }

    public function conversion($name)
    {
        $this->save();

        $converter = new static($this->file, $name);
        $this->converters[] = $converter;

        return $converter;
    }

    public function __call($method, $arguments)
    {
        $output = optional($this->image)->$method(...$arguments);

        if (starts_with($method, 'get')) {
            return $output;
        }

        return $this;
    }

    /** @return self[] */
    public function results()
    {
        $this->save();

        $results = [$this];
        foreach ($this->converters as $converter) {
            $results = array_merge($results, $converter->results());
        }

        return $results;
    }
}
