<?php

namespace ChrisReedIO\APIAmigo\Traits;

use ChrisReedIO\APIAmigo\Models\AmigoRecording;
use ChrisReedIO\APIAmigo\Models\AmigoRequest;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

trait HasAmigoRecordings
{
    public function recordings(): BelongsToMany
    {
        // return $this->belongsToMany(config('api-amigo.models.recording'), 'user_id');
        // return $this->belongsToMany(AmigoRecording::class, 'user_id');
        return $this->belongsToMany(AmigoRecording::class,
            'amigo_recording_amigo_request',
            'request_id',
            'recording_id'
        );
    }
}
