<?php

namespace App\Services\Mock;

class Dashboard
{
    protected $config;
    protected $trace;

    public function getTokenData(string $token)
    {
        $this->testDataFilePath = __DIR__ . '/../../../tests/Functional/OAuthTestData.php';

        $this->testData = require $this->testDataFilePath;

        switch ($token)
        {
            case 'success':
                return $this->correctResponse();

            case 'invalid':
                return $this->invalidTokenResponse();
            
            default:
                # code...
                break;
        }
    }

    public function correctResponse()
    {
        $content = $this->testData['testGetTokenData']['request']['content'];

        $data = [
            'success' => true,
            'data'    => $content,
        ];
        return $data;
    }

    public function invalidTokenResponse()
    {
        $content = $this->testData['testGetTokenDataWithInvalidToken']['request']['content'];

        $data = [
            'success' => false,
            'errors'    => $content,
        ];
        return $data;
    }
}
