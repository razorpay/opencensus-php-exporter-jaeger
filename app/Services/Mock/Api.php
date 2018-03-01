<?php

namespace App\Services\Mock;

class Api
{
    public function __call($name, $arguments)
    {
        return;
    }
}
