<?php

namespace ChrisReedIO\APIAmigo\Traits;

use ChrisReedIO\APIAmigo\Models\AmigoRecording;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/** @mixin Model */
trait HasAmigoRecordings
{
    public function recordings(): BelongsToMany
    {
        // return $this->belongsToMany(config('api-amigo.models.recording'), 'user_id');
        // return $this->belongsToMany(AmigoRecording::class, 'user_id');
        return $this->belongsToMany(
            AmigoRecording::class,
            'amigo_recording_amigo_request',
            'request_id',
            'recording_id'
        );
    }
}
