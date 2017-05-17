<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;

class RzpMigrate extends Command
{
    use ConfirmableTrait;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'rzp:migrate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Runs migrations. (from dependant packages)';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        if ($this->confirmToProceed() === false)
        {
            return;
        }

        //
        // TODO: Remove this after upgrading to laravel >= 5.3
        // that supports https://laravel.com/docs/5.3/packages#migrations
        //
        $this->info('<info>Running OAuth migrations on Auth DB</info>');

        $this->call('migrate',
                    [
                        '--path'     => 'vendor/razorpay/oauth/database/migrations',
                    ]);
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return parent::getOptions();
    }
}
