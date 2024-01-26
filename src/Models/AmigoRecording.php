<?php

namespace ChrisReedIO\APIAmigo\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

use function config;

/**
 * AmigoRecording
 *
 * Represents a recording of an API request
 *
 * @property int $id
 * @property int $user_id
 * @property int $connector_id
 * @property string $name
 * @property string $description
 * @property int $started_at
 * @property int $ended_at
 * @property bool $global
 * @property bool $capture_body
 * @property AmigoConnector $connector
 * @property AmigoRequest[] $requests
 */
class AmigoRecording extends Model
{
    protected $fillable = [
        'user_id',
        'connector_id',
        'name',
        'description',
        'started_at',
        'ended_at',
        'global',
        'capture_body',
    ];

    protected $casts = [
        'started_at' => 'timestamp',
        'ended_at' => 'timestamp',
        'global' => 'boolean',
        'capture_body' => 'boolean',
    ];

    // Set the user id on creating
    protected static function booted(): void
    {
        static::creating(function (AmigoRecording $recording) {
            $recording->user_id = auth()->id();
        });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNotNull('started_at')->whereNull('ended_at');
    }

    public function scopeGlobal(Builder $query, bool $isGlobal = true): Builder
    {
        return $query->where('global', $isGlobal);
    }

    public function user(): BelongsTo
    {
        // return $this->belongsTo(User::class, 'user_id');
        return $this->belongsTo(config('api-amigo.models.user'), 'user_id');
    }

    public function connector(): BelongsTo
    {
        return $this->belongsTo(AmigoConnector::class, 'connector_id');
    }

    public function requests(): BelongsToMany
    {
        return $this->belongsToMany(
            AmigoRequest::class,
            'amigo_recording_amigo_request',
            'recording_id',
            'request_id'
        );
    }

    public function start(): bool
    {
        if ($this->started_at) {
            return false;
        }

        $this->started_at = now();
        $this->save();

        return true;
    }

    public function stop(): bool
    {
        if (! $this->started_at || $this->ended_at) {
            return false;
        }

        $this->ended_at = now();
        $this->save();

        return true;
    }
}
