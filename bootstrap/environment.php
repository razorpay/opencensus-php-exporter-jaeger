<?php

/*
 | ----------------------------------------------------------------------------------
 | Detect The Application Environment
 | ----------------------------------------------------------------------------------
 |
 */
use Dotenv\Dotenv;

$envDir = __DIR__.'/../environment';

//
// By default we assume environment is prod.
// During testing, laravel sets APP_ENV to 'testing'
// Otherwise, we get the environment from the file
// environment/env.php
//
$env = 'production';

if (env('APP_ENV') === 'testing')
{
    $env = 'testing';
}
else if (file_exists(__DIR__ . '/../environment/env.php'))
{
    $env = require __DIR__ . '/../environment/env.php';
}

putenv("APP_ENV=$env");

$cascadingEnvFile = '.env.' . $env;

//
// Environment variable files are loaded in the order
// * Vault env file
// * Cascaded environment based env file
// * Default env file
//
// Note that of the above 3, first two are committed in git
// while last one comes into the folder when baking AMI's via brahma
//
if (function_exists('read_env_file') === false)
{
    function read_env_file(string $envDir, string $fileName)
    {
        $file = $envDir . '/' . $fileName;

        if (file_exists($file) === false)
        {
            return;
        }

        $dotEnv = Dotenv::create($envDir, $fileName);

        $dotEnv->load();
    }
}

read_env_file($envDir, '.env.vault');
read_env_file($envDir, $cascadingEnvFile);
read_env_file($envDir, '.env.defaults');
