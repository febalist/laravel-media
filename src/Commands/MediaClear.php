<?php

namespace Febalist\Laravel\Media\Commands;

use Febalist\Laravel\Media\Media;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class MediaClear extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'media:clear {disk? : Only media in this disk} {--check : Delete media models without files} {--d|delay= : Ignore recently updated files}';

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

        $query = Media::query()
            ->when($this->argument('disk'), function (Builder $builder, $disk) {
                return $builder->where('disk', $disk);
            })
            ->when($this->option('delay'), function (Builder $builder, $delay) {
                return $builder->where('updated_at', '<', now()->subMinutes($delay));
            });

        $bar = $this->output->createProgressBar($query->count());

        $query->each(function (Media $media) use ($check, $bar) {
            if ($media->model_id && $media->abandoned) {
                $media->delete();
            } elseif ($check && !$media->file->exists()) {
                $media->delete();
            }

            $bar->advance();
        }, 500);

        $bar->finish();
    }
}
