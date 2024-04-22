<?php

namespace ChrisReedIO\APIAmigo\Enums;

use Filament\Support\Contracts\HasLabel;
use Illuminate\Support\Carbon;

use function now;

enum ChartFilters: string implements HasLabel
{
    case Today = 'today';
    case Yesterday = 'yesterday';
    case Week = 'week';
    case Month = 'month';
    case ThreeMonths = 'three_months';
    case SixMonths = 'six_months';
    case Year = 'year';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Today => 'Today',
            self::Yesterday => 'Yesterday',
            self::Week => 'Last week',
            self::Month => 'Last month',
            self::ThreeMonths => 'Three months',
            self::SixMonths => 'Six months',
            self::Year => 'Last year',
        };
    }

    public static function filterArray(): array
    {
        return [
            self::Today->value => self::Today->getLabel(),
            self::Yesterday->value => self::Yesterday->getLabel(),
            self::Week->value => self::Week->getLabel(),
            self::Month->value => self::Month->getLabel(),
            self::ThreeMonths->value => self::ThreeMonths->getLabel(),
            self::SixMonths->value => self::SixMonths->getLabel(),
            self::Year->value => self::Year->getLabel(),

            // self::FiveYears->value => self::FiveYears->getLabel(),
        ];
    }

    public function getStartDate(): Carbon
    {
        return match ($this) {
            self::Today => now()->startOfDay(),
            self::Yesterday => now()->subDay()->startOfDay(),
            self::Week => now()->subWeek()->startOfDay(),
            self::Month => now()->subMonth()->startOfDay(),
            self::ThreeMonths => now()->subMonths(3)->startOfDay(),
            self::SixMonths => now()->subMonths(6)->startOfDay(),
            self::Year => now()->subYear()->startOfDay(),
        };
    }
}
