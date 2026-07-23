<?php

namespace Tests\Feature\App;

use Tests\TestCase;

class AppConsoleTest extends TestCase
{
    public function test_the_app_console_page_is_reachable(): void
    {
        $response = $this->get('/app');

        $response->assertOk();
        $response->assertSee('SoudaCore ERP');
        $response->assertSee('Sign In');
    }
}
