<?php

namespace Febalist\Laravel\Media\Jobs;

use Febalist\Laravel\Media\Media;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class MediaUpdateSha1 implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels {
        getRestoredPropertyValue as getRestoredPropertyValueTrait;
    }

    protected $media;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Media $media)
    {
        $this->media = $media;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if ($this->media) {
            $this->media->updateSha1(true);
        }
    }

    protected function getRestoredPropertyValue($value)
    {
        try {
            return $this->getRestoredPropertyValueTrait($value);
        } catch (ModelNotFoundException $exception) {
            return null;
        }
    }
}
