<?php

namespace Febalist\Laravel\Media\Commands;

use Febalist\Laravel\Media\Media;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class MediaClear extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'media:clear {--check}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete media without models or files';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $check = $this->option('check');

        Media::chunk(500, function (Collection $media) use ($check) {
            $media->each(function (Media $media) use ($check) {
                if ($media->model_id && !$media->model) {
                    return $media->delete();
                }
                if ($check && !$media->file->exists()) {
                    return $media->delete();
                }
            });
        });
    }
}
