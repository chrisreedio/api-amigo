<?php

namespace ChrisReedIO\APIAmigo\Resources\AmigoEndpointResource\Widgets;

use Filament\Widgets\ChartWidget;

class EndpointResponsesChart extends ChartWidget
{
    protected static ?string $heading = 'Chart';

    protected function getData(): array
    {
        return [
            //
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
