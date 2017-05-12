<?php

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testExample()
    {
        $this->get('/');

        $expected = '{"message":"Welcome to Razorpay Auth!"}';

        $this->assertEquals(
            $expected, $this->response->getContent()
        );
    }
}
