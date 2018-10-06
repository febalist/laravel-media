<?php

namespace Febalist\Laravel\Media\Commands;

use Febalist\Laravel\Media\Media;
use Illuminate\Console\Command;

class MediaConvert extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'media:convert {--sync}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reconvert all media';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $sync = $this->option('sync');

        $query = Media::query();

        $bar = $this->output->createProgressBar($query->count());

        $query->each(function (Media $media) use ($sync, $bar) {
            $media->convert($sync);

            $bar->advance();
        }, 500);

        $bar->finish();
    }
}
