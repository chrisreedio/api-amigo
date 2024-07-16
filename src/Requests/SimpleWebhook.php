<?php

namespace ChrisReedIO\APIAmigo\Requests;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\SoloRequest;
use Saloon\Traits\Body\HasJsonBody;

class SimpleWebhook extends SoloRequest implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(protected string $url, protected array $data, protected ?string $secret = null) {}

    // public static function make(string $url, array $data, ?string $secret = null): static
    // {
    //     return new static($url, $data, $secret);
    // }

    // protected function defaultAuth(): HmacAuthenticator
    // {
    //     return new HmacAuthenticator('secret');
    // }

    /**
     * {@inheritDoc}
     */
    public function defaultBody(): array
    {
        return $this->data;
    }

    /**
     * {@inheritDoc}
     */
    public function resolveEndpoint(): string
    {
        return $this->url;
    }
}
