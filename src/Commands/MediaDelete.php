<?php

namespace Febalist\Laravel\Media\Commands;

use Febalist\Laravel\Media\Media;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

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

        Media::when($disk, function (Builder $query, $disk) {
            return $query->where('disk', $disk);
        })->chunk(500, function (Collection $media) {
            $media->each(function (Media $media) {
                $media->delete();
            });
        });
    }
}
