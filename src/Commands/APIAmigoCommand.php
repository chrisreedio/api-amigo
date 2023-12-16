<?php

namespace ChrisReedIO\APIAmigo\Commands;

use Illuminate\Console\Command;

class APIAmigoCommand extends Command
{
    public $signature = 'api-amigo';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
