<?php

if (! function_exists('millitime'))
{
    /**
     * Gets current unix timestamp in milliseconds
     * @return int
     */
    function millitime()
    {
        return round(microtime(true) * 1000);
    }
}
