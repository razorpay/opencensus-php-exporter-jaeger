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

            case 'incorrect_response_type':
                return $this->invalidResponseTypeResponse();
            
            default:
                # code...
                break;
        }
    }

    public function correctResponse()
    {
        return $this->testData['testGetTokenData']['request']['content'];
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

    public function invalidResponseTypeResponse()
    {
        return $this->testData['testGetTokenDataWrongResponseType']['request']['content'];
    }
}
