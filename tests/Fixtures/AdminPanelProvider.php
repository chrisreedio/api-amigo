<?php

namespace ChrisReedIO\APIAmigo\Tests\Fixtures;

use ChrisReedIO\APIAmigo\APIAmigoPlugin;
use Filament\Panel;
use Filament\PanelProvider;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->plugin(APIAmigoPlugin::make());
    }
}
