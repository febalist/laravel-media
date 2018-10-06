<?php

namespace Febalist\Laravel\Media\Commands;

use Febalist\Laravel\Media\Media;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class MediaDelete extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'media:delete {disk? : Only on this disk}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete all media';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $disk = $this->argument('disk');

        $query = Media::when($disk, function (Builder $query, $disk) {
            return $query->where('disk', $disk);
        });

        $bar = $this->output->createProgressBar($query->count());

        $query->each(function (Media $media) use ($bar) {
            $media->delete();

            $bar->advance();
        }, 500);

        $bar->finish();
    }
}
