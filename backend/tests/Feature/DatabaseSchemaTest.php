<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_migrations_create_users_rooms_chat(): void
    {
        $this->assertTrue(Schema::hasTable('users'));
        $this->assertTrue(Schema::hasTable('rooms'));
        $this->assertTrue(Schema::hasTable('chat'));
        $this->assertTrue(Schema::hasTable('private_messages'));
        $this->assertTrue(Schema::hasTable('friendships'));
        $this->assertTrue(Schema::hasTable('user_ignores'));
        $this->assertTrue(Schema::hasTable('images'));
        $this->assertTrue(Schema::hasTable('banned_ips'));
        $this->assertTrue(Schema::hasTable('filter_words'));
    }
}
