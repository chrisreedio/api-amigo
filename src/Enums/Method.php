<?php

namespace ChrisReedIO\APIAmigo\Enums;

use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum Method: string implements HasColor, HasLabel
{
    case GET = 'GET';
    case HEAD = 'HEAD';
    case POST = 'POST';
    case PUT = 'PUT';
    case PATCH = 'PATCH';
    case DELETE = 'DELETE';
    case OPTIONS = 'OPTIONS';
    case CONNECT = 'CONNECT';
    case TRACE = 'TRACE';

    public function getColor(): string | array | null
    {
        return match ($this) {
            self::GET => Color::Green,
            self::HEAD => Color::Gray,
            self::POST => Color::Blue,
            self::PUT => Color::Yellow,
            self::PATCH => Color::Purple,
            self::DELETE => Color::Red,
            self::OPTIONS => Color::Indigo,
            self::CONNECT => Color::Pink,
            self::TRACE => Color::Teal,
        };
    }

    public function getLabel(): ?string
    {
        return $this->name;
    }
}
