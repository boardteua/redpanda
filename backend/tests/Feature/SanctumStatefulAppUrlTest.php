<?php

namespace Tests\Feature;

use Tests\TestCase;

class SanctumStatefulAppUrlTest extends TestCase
{
    public function test_sanctum_stateful_includes_app_url_host(): void
    {
        $host = parse_url((string) config('app.url'), PHP_URL_HOST);
        $this->assertIsString($host);
        $this->assertNotSame('', $host);

        $stateful = config('sanctum.stateful');
        $this->assertIsArray($stateful);
        $this->assertContains(
            $host,
            $stateful,
            'APP_URL host must be in sanctum.stateful so SPA session cookies apply to /api/* (see AppServiceProvider).',
        );
    }
}
