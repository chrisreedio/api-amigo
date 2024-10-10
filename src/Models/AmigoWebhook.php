<?php

namespace ChrisReedIO\APIAmigo\Models;

use const JSON_PRETTY_PRINT;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

use function json_encode;
use function now;

/**
 * Class AmigoWebhook
 *
 * @property int $id
 * @property int $listener_id
 * @property string $unique_id
 * @property string $url
 * @property string $sender
 * @property array $headers
 * @property array $payload
 * @property int $status
 * @property string $status_message
 * @property string $processed_at
 * @property ?array $error
 * @property ?AmigoListener $listener
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property ?Model $webhookable
 * @property string $type
 */
class AmigoWebhook extends AmigoModel
{
    protected $fillable = [
        'listener_id',
        'unique_id',
        'url',
        'sender',
        'headers',
        'payload',
        'status',
        'status_message',
        'processed_at',
        'error',
        'webhookable_id',
        'webhookable_type',
        'type',
    ];

    protected $casts = [
        'headers' => 'array',
        'payload' => 'array',
        'error' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (AmigoWebhook $webhook) {
            $webhook->unique_id = Str::ulid()->toBase58();
        });
    }

    public function getProcessingTimeAttribute(): int | float | null
    {
        if ($this->processed_at === null) {
            return null;
        }

        return $this->created_at->diffInSeconds($this->processed_at);
    }

    public function listener(): BelongsTo
    {
        return $this->belongsTo(AmigoListener::class, 'listener_id');
    }

    public function webhookable(): MorphTo
    {
        return $this->morphTo();
    }

    public function complete(): void
    {
        $this->processed_at = now();
        $this->error = null;
        $this->save();

        // Increment the listener's uses count
        if ($this->listener()->exists()) {
            $this->listener->increment('uses');
        }
    }

    public function fail(int $code = 500, ?string $message = null, ?string $trace = null): void
    {
        $this->processed_at = now();
        $this->error = array_filter([
            'code' => $code,
            'message' => $message,
            'trace' => $trace,
        ]);
        $this->save();
    }

    public function failWithException(Exception $exception): void
    {
        $this->fail($exception->getCode(), $exception->getMessage(), $exception->getTraceAsString());
    }

    public function getEncodedBodyAttribute(): string
    {
        return json_encode($this->payload, JSON_PRETTY_PRINT);
    }
}
