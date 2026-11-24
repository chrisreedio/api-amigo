<?php

namespace ChrisReedIO\APIAmigo\Tests\Fixtures\Integrations\Acme\Requests;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

class CreateWidget extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(protected array $widget) {}

    public function resolveEndpoint(): string
    {
        return '/widgets';
    }

    protected function defaultBody(): array
    {
        return $this->widget;
    }

    protected function defaultHeaders(): array
    {
        return ['Accept' => 'application/json', 'Authorization' => 'Bearer secret', 'X-Signature' => 'abc123'];
    }
}
