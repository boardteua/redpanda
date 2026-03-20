<?php

namespace Tests;

use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Після зміни default broadcaster або purge() новий драйвер не має зареєстрованих каналів — підвантажити routes/channels.php знову.
     */
    protected function configureReverbBroadcasterForTesting(): void
    {
        config([
            'broadcasting.default' => 'reverb',
            'broadcasting.connections.reverb.key' => 'test-key',
            'broadcasting.connections.reverb.secret' => 'test-secret',
            'broadcasting.connections.reverb.app_id' => 'test-app',
            'broadcasting.connections.reverb.options' => [
                'host' => '127.0.0.1',
                'port' => 8080,
                'scheme' => 'http',
                'useTLS' => false,
            ],
        ]);

        $this->app->make(BroadcastManager::class)->purge();
        require $this->app->basePath('routes/channels.php');
    }
}
