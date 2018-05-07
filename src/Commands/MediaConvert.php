<?php

namespace Febalist\Laravel\Media\Commands;

use Febalist\Laravel\Media\Media;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class MediaConvert extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'media:convert {--force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Convert media';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $force = $this->option('force');

        Media::chunk(500, function (Collection $media) use ($force) {
            $media->each(function (Media $media) use ($force) {
                $media->convert($force);
            });
        });
    }
}
