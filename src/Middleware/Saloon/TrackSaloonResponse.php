<?php

namespace ChrisReedIO\APIAmigo\Middleware\Saloon;

use ChrisReedIO\APIAmigo\Models\AmigoResponse;
use Saloon\Contracts\ResponseMiddleware;
use Saloon\Http\Response;

class TrackSaloonResponse implements ResponseMiddleware
{
    public function __invoke(Response $response): void
    {
        // dump('== API Amigo - LogResponse middleware invoked ==');
        // dd($response);
        AmigoResponse::track($response);

        // dd('Done Tracking Response');
    }
}
