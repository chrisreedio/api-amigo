<?php

namespace ChrisReedIO\APIAmigo\Clusters;

use BackedEnum;
use ChrisReedIO\APIAmigo\APIAmigoPlugin;
use Filament\Clusters\Cluster;
use Filament\Panel;
use Illuminate\Contracts\Support\Htmlable;

class APIManagement extends Cluster
{
    // protected static string | BackedEnum | null $navigationIcon = 'far-truck-bolt';
    public static function getNavigationIcon(): string | BackedEnum | Htmlable | null
    {
        return (string) config('api-amigo.filament.navigation.icon', 'far-truck-fast');
    }

    public static function getNavigationSort(): ?int
    {
        return (int) config('api-amigo.filament.navigation.sort', 6000);
    }

    public static function getNavigationLabel(): string
    {
        return (string) config('api-amigo.filament.navigation.label', 'API Management');
    }

    public static function getNavigationGroup(): ?string
    {
        return config('api-amigo.filament.navigation.group');
    }

    public static function getClusterBreadcrumb(): ?string
    {
        return config('api-amigo.filament.breadcrumb', 'API Management');
    }

    public static function getSlug(?Panel $panel = null): string
    {
        return config('api-amigo.filament.slug', 'api-management');
    }

    public static function canAccess(): bool
    {
        return APIAmigoPlugin::get()->isAuthorized();
    }
}
