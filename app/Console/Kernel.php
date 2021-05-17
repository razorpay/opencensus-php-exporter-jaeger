<?php

namespace App\Console;

use App\Console\Commands\ClientMigrate;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        ClientMigrate::class
    ];
}
