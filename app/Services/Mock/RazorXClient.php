<?php

namespace App\Services\Mock;

class RazorXClient
{
    public function __construct()
    {
    }

    public function __call($name, $arguments)
    {
        return;
    }

    public function getTreatment(string $id='mock', string $featureFlag='mock', string $mode='mock', $retryCount = 0): string
    {
        return 'on';
    }
}
