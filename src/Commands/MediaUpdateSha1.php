<?php

namespace Febalist\Laravel\Media\Commands;

use Febalist\Laravel\Media\Media;
use Illuminate\Console\Command;

class MediaUpdateSha1 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'media:update-sha1 {--sync} {--force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update SHA-1';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $sync = $this->option('sync');
        $force = $this->option('force');

        $query = Media::query();

        $bar = $this->output->createProgressBar($query->count());

        $query->each(function (Media $media) use ($sync, $force, $bar) {
            if ($force || !$media->sha1) {
                $media->updateSha1($sync);
            }

            $bar->advance();
        }, 500);

        $bar->finish();
    }
}
