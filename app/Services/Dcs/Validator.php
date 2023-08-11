<?php

namespace App\Services\Dcs;

use App\Models\Base\JitValidator;

class Validator extends \Razorpay\Spine\Validation\Validator
{
    public function validateMode(string $mode)
    {
        $rules = ['mode' => 'required|string|in:test,live'];

        $input = ['mode' => $mode];

        (new JitValidator)->rules($rules)
                          ->input($input)
                          ->strict(false)
                          ->validate();
    }
}
