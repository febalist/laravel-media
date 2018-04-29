<?php

namespace Febalist\Laravel\Media;

use Febalist\Laravel\File\File;
use URL;

/**
 * @property File $file
 */
trait HasFile
{
    public function url($expiration = null)
    {
        $url = $this->file->url($expiration);
        if (!starts_with($url, ['http://', 'https://'])) {
            $name = File::slug($this->file->name());

            return URL::signedRoute('media.download', [$this, $name], $expiration);
        }

        return $url;
    }

    public function preview($embedded = false)
    {
        $extension = $this->extension;
        $url = $this->url;

        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'ico', 'mp3', 'mp4', 'webm', 'txt'])) {
            return $url;
        } elseif (in_array($extension, ['ppt', 'pptx', 'doc', 'docx', 'xls', 'xlsx'])) {
            return 'https://view.officeapps.live.com/op/'.($embedded ? 'embed' : 'view').'.aspx?src='.urlencode($url);
        } elseif (in_array($extension, ['ods', 'sxc', 'csv', 'tsv'])) {
            return "https://sheet.zoho.com/sheet/view.do?&name=$this->name&url=".urlencode($url);
        } else {
            return 'https://docs.google.com/viewer?'.($embedded ? 'embedded=true&' : '').'url='.urlencode($url);
        }
    }

    public function response($filename = null, $headers = [])
    {
        return $this->file->response($filename, $headers);
    }

    public function stream()
    {
        return $this->file->stream();
    }
}
