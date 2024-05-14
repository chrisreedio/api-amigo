<?php

namespace ChrisReedIO\APIAmigo\Clusters;

use Filament\Clusters\Cluster;

class APIManagement extends Cluster
{
    // protected static ?string $navigationIcon = 'far-truck-bolt';
    protected static ?string $navigationIcon = 'far-wagon-covered';
    protected static ?int $navigationSort = 6000;
    protected static ?string $navigationLabel = 'API Management';
    protected static ?string $clusterBreadcrumb = 'API Management';
    protected static ?string $slug = 'api-management';
}
