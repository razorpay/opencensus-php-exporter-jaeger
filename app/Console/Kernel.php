<?php

namespace App\Console;

use App\Console\Commands\ClientMigrate;
use App\Console\Commands\PublicTokenMigrate;
use App\Console\Commands\BulkApplicationCreate;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        ClientMigrate::class,
        PublicTokenMigrate::class,
        BulkApplicationCreate::class
    ];
}
