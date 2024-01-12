<?php

namespace ChrisReedIO\APIAmigo\Enums;

use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum Method: string implements HasColor, HasLabel
{
    case GET = 'GET';
    case POST = 'POST';
    case PUT = 'PUT';
    case DELETE = 'DELETE';
    case PATCH = 'PATCH';

    public function getColor(): string | array | null
    {
        return match ($this) {
            self::GET => Color::Green,
            self::POST => Color::Blue,
            self::PUT => Color::Yellow,
            self::DELETE => Color::Red,
            self::PATCH => Color::Purple,
        };
    }

    public function getLabel(): ?string
    {
        return $this->name;
    }
}
