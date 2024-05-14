<?php

namespace ChrisReedIO\APIAmigo\Traits;

use Carbon\Carbon;
use ChrisReedIO\APIAmigo\Jobs\ProcessWebhookJob;
use ChrisReedIO\APIAmigo\Models\AmigoListener;
use Filament\Support\Colors\Color;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

use function class_basename;
use function get_class;
use function implode;
use function is_subclass_of;

/** @mixin Model */
trait HasTransientWebhooks
{
    public function listeners(): MorphMany
    {
        return $this->morphMany(AmigoListener::class, 'listenable');
    }

    /**
     * Prune any listeners that have expired
     *
     * @param  string|null  $handler  The handler to prune for, or null to prune all
     */
    public function pruneUnusedListeners(?string $handler = null): void
    {
        // Expire any listeners that have no uses for the given handler
        $this->listeners()
            ->where('uses', 0)
            ->when($handler, function (Builder $query) use ($handler) {
                $query->where('handler', $handler);
            })
            ->update([
                'expires_at' => Carbon::now(),
            ]);
    }

    public function createTransientListener(string $handler, ?Carbon $expires_at = null, ?int $uses = null): AmigoListener
    {
        if (! is_subclass_of($handler, ProcessWebhookJob::class)) {
            throw new \InvalidArgumentException('Handler must be a subclass of '.ProcessWebhookJob::class);
        }

        $targetName = class_basename($this);
        if ($this->name) {
            $targetName .= ': '.$this->name;
        }

        $name = implode(' ', [
            class_basename($handler),
            'for',
            $targetName,
        ]);

        if (! $expires_at) {
            $expires_at = Carbon::now()->addDay();
        }

        // dd($handler);
        $color = Color::Yellow[500];
        $colorHex = sprintf('#%02x%02x%02x', $color[0], $color[1], $color[2]);

        return $this->listeners()->create([
            'display_name' => $name,
            // 'handler' => get_class($handler),
            'handler' => $handler,
            'max_uses' => $uses ?? 1,
            'color' => $colorHex,
            'expires_at' => $expires_at,
        ]);
    }
}
