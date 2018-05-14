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
    protected $signature = 'media:clear {--deep}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear media models';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $deep = $this->option('deep');

        Media::chunk(500, function (Collection $media) use ($deep) {
            $media->each(function (Media $media) use ($deep) {
                if ($media->model_id && !$media->model) {
                    return $media->delete();
                }
                if ($deep && !$media->file->exists()) {
                    return $media->delete();
                }
            });
        });
    }
}
