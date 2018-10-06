<?php

namespace Febalist\Laravel\Media\Commands;

use Febalist\Laravel\Media\Media;
use Illuminate\Console\Command;

class MediaClear extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'media:clear {--check : Delete media models without files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete media with fully deleted models';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $check = $this->option('check');

        $query = Media::query();

        $bar = $this->output->createProgressBar($query->count());

        $query->each(function (Media $media) use ($check, $bar) {
            if ($media->model_id && $media->abandoned) {
                return $media->delete();
            }
            if ($check && !$media->file->exists()) {
                return $media->delete();
            }

            $bar->advance();
        }, 500);

        $bar->finish();
    }
}
