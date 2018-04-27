<?php

namespace Febalist\Laravel\Media;

use App\Http\Controllers\Controller;

class MediaController extends Controller
{
    public function __construct()
    {
        $this->middleware('signed')->only('download');
    }

    public function download($id, $filename)
    {
        return Media::findOrFail($id)->response($filename);
    }
}
