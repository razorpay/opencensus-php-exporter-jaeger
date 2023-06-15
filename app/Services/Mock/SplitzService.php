<?php

namespace App\Services\Mock;

class SplitzService
{
    public function __call($name, $arguments)
    {
        return;
    }
    
    public function isExperimentEnabled(array $properties, string $checkVariant = 'enable') : bool
    {
        return true;
    }

    public function evaluateRequest(array $input)
    {
        $code = 200;

        $body = json_encode([
            "id" => "A",
            "project_id" => "HHhdsBjOfdFmSR",
            "experiment" => [
                "id" => "HHhiZYiI79mJbj",
                "name" => "splitz.feature.reporting.experiment",
                "environment_id" => "HHhdspxWzjWktQ"
            ],
            "variant" => [
                "id" => "HHhiZaJRJbcT9Y",
                "name" => "enable",
                "variables" => [[
                    "key" => "button_color",
                    "value" => "purple"
                ]],
                "experiment_id" => "HHhiZYiI79mJbj"
            ],
            "Reason" => "bucketer",
            "steps" => ["sampler", "exclusion", "audience", "assign_bucket"]
        ]);

        $res = json_decode($body, true);

        return ['status_code' => $code, 'response' => $res];
    }
}
