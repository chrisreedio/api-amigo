<?php

namespace ChrisReedIO\APIAmigo\Middleware\Guzzle\Response;

use ChrisReedIO\APIAmigo\Models\AmigoResponse;
use Psr\Http\Message\ResponseInterface;
use Saloon\Contracts\ResponseMiddleware;
use Saloon\Http\Response;

class LogGuzzleResponse
{
    public function __invoke(ResponseInterface $response): ResponseInterface
    {
        dump('== API Amigo LogGuzzleResponse middleware invoked ==');

        // AmigoResponse::track($response);

        return $response;
    }
}
