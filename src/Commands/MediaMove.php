<?php

namespace Febalist\Laravel\Media\Commands;

use Febalist\Laravel\Media\Media;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class MediaMove extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'media:move {disk? : Target disk} {--force : Move files that are already in disk too} {--sync : Sync convert}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Move media to new locations';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $disk = $this->argument('disk');
        $force = $this->option('force');
        $sync = $this->option('sync');

        $query = Media::when(!$force, function (Builder $query) use ($disk) {
            return $query->where('disk', '!=', $disk);
        });

        $bar = $this->output->createProgressBar($query->count());

        $query->each(function (Media $media) use ($disk, $sync, $bar) {
            $media->move($disk);
            $media->convert($sync);

            $bar->advance();
        }, 500);

        $bar->finish();
    }
}
