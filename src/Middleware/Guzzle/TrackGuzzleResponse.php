<?php

namespace ChrisReedIO\APIAmigo\Middleware\Guzzle;

use Psr\Http\Message\ResponseInterface;

class TrackGuzzleResponse
{
    public function __invoke(ResponseInterface $response): ResponseInterface
    {
        dump('== API Amigo LogGuzzleResponse middleware invoked ==');

        // AmigoResponse::track($response);

        return $response;
    }
}
