<?php

namespace ChrisReedIO\APIAmigo\Middleware\Saloon\Response;

use ChrisReedIO\APIAmigo\Models\AmigoResponse;
use Saloon\Contracts\ResponseMiddleware;
use Saloon\Http\Response;

class LogResponse implements ResponseMiddleware
{
    public function __invoke(Response $response): void
    {
        // dump('== API Amigo - LogResponse middleware invoked ==');

        AmigoResponse::track($response);

        // dd('Done Tracking Response');
    }
}
