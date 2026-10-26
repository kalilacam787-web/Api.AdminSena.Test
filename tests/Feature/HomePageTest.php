<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    public function test_home_page_uses_project_images_when_gallery_database_is_unavailable(): void
    {
        $this->withoutVite();
        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Bienvenido al SENA');
        $response->assertSee('imagene/Imagenes SENA/IMAGEN 2.webp');
    }
}
