<?php

namespace Febalist\Laravel\Media\Commands;

use Febalist\Laravel\Media\Media;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class MediaMove extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'media:move {disk?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Move media';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $disk = $this->argument('disk');

        Media::chunk(500, function (Collection $media) use ($disk) {
            $media->each(function (Media $media) use ($disk) {
                $media->move($disk);
                $media->convert();
            });
        });
    }
}
