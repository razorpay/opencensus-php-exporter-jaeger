<?php

namespace App\Console;

use App\Console\Commands\ClientMigrate;
use App\Console\Commands\PublicOAuthCredentialMigrate;
use App\Console\Commands\PublicTokenMigrate;
use App\Console\Commands\BulkApplicationCreate;
use App\Console\Commands\BulkApplicationValidate;
use App\Console\Commands\PublicTokenPatch;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        ClientMigrate::class,
        PublicTokenMigrate::class,
        BulkApplicationCreate::class,
        PublicOAuthCredentialMigrate::class,
        BulkApplicationValidate::class,
        PublicTokenPatch::class
    ];
}
