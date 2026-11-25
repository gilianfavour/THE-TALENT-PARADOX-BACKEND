<?php

namespace App\Console\Commands;

use App\Services\RediscoveryService;
use Illuminate\Console\Command;

class RediscoverTalent extends Command
{
    protected $signature = 'talent:rediscover';

    protected $description = 'Scan applications that are weak matches and queue alternative vacancies for recruiter review';

    public function handle(RediscoveryService $service): int
    {
        $n = $service->scanAll();
        $this->info("{$n} alternative recommendations queued for recruiter review.");

        return self::SUCCESS;
    }
}
