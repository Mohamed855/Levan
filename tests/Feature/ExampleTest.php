<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_application_returns_a_successful_response(): void
    {
        $this->get('/')->assertOk();
    }
}
