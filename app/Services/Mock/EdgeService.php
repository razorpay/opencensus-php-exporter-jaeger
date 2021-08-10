<?php

namespace App\Services\Mock;

class EdgeService
{
    public function __construct($app)
    {
    }

    public function __call($name, $arguments)
    {
        return;
    }
}
