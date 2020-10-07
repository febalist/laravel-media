<?php

namespace Febalist\Laravel\Media\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read \Febalist\Laravel\Media\Media $resource
 */
class MediaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'extension' => $this->resource->extension,
            'view_url' => $this->resource->viewUrl(),
            'input_signature' => $this->resource->input_signature,
        ];
    }
}
