<?php

namespace Tests\Feature;

use App\Models\Computer;
use App\Services\ComputerImageStorage;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ComputerImageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');

        Schema::create('computers', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->string('brand');
            $table->unsignedBigInteger('teacher_id')->nullable();
            $table->timestamps();
        });

        $migration = require database_path('migrations/2026_10_26_100000_add_image_path_to_computers_table.php');
        $migration->up();

        $serialMigration = require database_path('migrations/2026_10_26_110000_add_serial_to_computers_table.php');
        $serialMigration->up();

        $imagePublicIdMigration = require database_path('migrations/2026_10_26_120000_add_image_public_id_to_computers_table.php');
        $imagePublicIdMigration->up();

        Schema::create('apprentices', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('last_name')->nullable();
            $table->string('document_number')->nullable();
            $table->unsignedBigInteger('computer_id')->nullable();
            $table->timestamps();
        });

        Schema::create('teachers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('document_number')->nullable();
        });

        Storage::fake('public');
        $this->withoutVite();
    }

    public function test_computer_image_can_be_uploaded_replaced_and_deleted(): void
    {
        $images = \Mockery::mock(ComputerImageStorage::class);
        $images->shouldReceive('upload')
            ->once()
            ->andReturn(['url' => 'https://res.cloudinary.com/demo/image/upload/first.png', 'public_id' => 'adminsena/computers/first']);
        $images->shouldReceive('upload')
            ->once()
            ->andReturn(['url' => 'https://res.cloudinary.com/demo/image/upload/replacement.png', 'public_id' => 'adminsena/computers/replacement']);
        $images->shouldReceive('delete')
            ->once()
            ->with('adminsena/computers/first');
        $images->shouldReceive('delete')
            ->once()
            ->with('adminsena/computers/replacement');
        $this->app->instance(ComputerImageStorage::class, $images);

        $this->post(route('computers.store'), [
            'serial' => '12345',
            'brand' => 'Lenovo',
            'image' => $this->fakeImage('computer.png'),
        ])->assertRedirect(route('computers.index'));

        $computer = Computer::firstOrFail();
        $this->assertSame('1', (string) $computer->number);
        $firstImagePath = $computer->image_path;
        $this->assertSame('https://res.cloudinary.com/demo/image/upload/first.png', $firstImagePath);
        $this->assertSame('adminsena/computers/first', $computer->image_public_id);

        $this->post(route('computers.image.upload', $computer), [
            'image' => $this->fakeImage('replacement.png'),
        ])->assertRedirect(route('computers.index'));

        $computer->refresh();
        $replacementImagePath = $computer->image_path;
        $this->assertNotSame($firstImagePath, $replacementImagePath);
        $this->assertSame('https://res.cloudinary.com/demo/image/upload/replacement.png', $replacementImagePath);
        $this->assertSame('adminsena/computers/replacement', $computer->image_public_id);

        $this->delete(route('computers.image.destroy', $computer))
            ->assertRedirect(route('computers.index'));

        $this->assertNull($computer->fresh()->image_path);
        $this->assertNull($computer->fresh()->image_public_id);
    }

    public function test_computer_pages_offer_image_upload_and_use_requested_assignment_label(): void
    {
        $images = \Mockery::mock(ComputerImageStorage::class);
        $images->shouldReceive('upload')
            ->once()
            ->andReturn(['url' => 'https://res.cloudinary.com/demo/image/upload/replacement.png', 'public_id' => 'adminsena/computers/replacement']);
        $images->shouldReceive('delete')
            ->once()
            ->with('adminsena/computers/computer');
        $this->app->instance(ComputerImageStorage::class, $images);

        $computer = Computer::create([
            'serial' => '12345',
            'number' => 1,
            'brand' => 'Lenovo',
            'image_path' => 'https://res.cloudinary.com/demo/image/upload/computer.png',
            'image_public_id' => 'adminsena/computers/computer',
        ]);
        $this->post(route('computers.image.upload', $computer), [
            'image' => $this->fakeImage('computer.png'),
        ])->assertRedirect(route('computers.index'));

        $this->get(route('computers.create'))
            ->assertOk()
            ->assertSee('name="serial"', false)
            ->assertSee('pattern="[0-9]{5,20}"', false)
            ->assertSee('enctype="multipart/form-data"', false)
            ->assertSee('name="image"', false)
            ->assertDontSee('name="number"', false);

        $this->get(route('computers.index'))
            ->assertOk()
            ->assertSeeInOrder(['ID', 'Marca', 'Foto', 'Asignado a', 'Funciones'])
            ->assertDontSee('<th>#</th>', false)
            ->assertSee('optgroup label="Aprendices"', false)
            ->assertSee('optgroup label="Instructores"', false)
            ->assertDontSee('Persona asignada')
            ->assertDontSee('<th>Eliminar</th>', false)
            ->assertDontSee('<th>Guardar</th>', false)
            ->assertSee('computer-image-upload')
            ->assertSee('Quitar imagen')
            ->assertSee('https://res.cloudinary.com/demo/image/upload/replacement.png', false)
            ->assertDontSee('Asignar a</th>', false);
    }

    public function test_legacy_local_computer_images_use_a_host_relative_url(): void
    {
        $computer = Computer::create([
            'serial' => '12345',
            'number' => 1,
            'brand' => 'Lenovo',
            'image_path' => 'images/computers/legacy.webp',
        ]);

        $this->assertSame('/storage/images/computers/legacy.webp', $computer->image_url);
    }

    public function test_serial_is_required_to_create_and_can_be_saved_with_assignment(): void
    {
        $this->post(route('computers.store'), [
            'serial' => '1234',
            'brand' => 'Dell',
        ])->assertSessionHasErrors('serial');

        $computer = Computer::create(['serial' => '54321', 'number' => 2, 'brand' => 'Dell']);

        $this->post(route('computers.store'), [
            'serial' => '67890',
            'brand' => 'Lenovo',
        ])->assertRedirect(route('computers.index'));

        $this->assertSame('3', (string) Computer::where('serial', '67890')->value('number'));

        $this->put(route('computers.assignment', $computer), [
            'serial' => '77777',
            'assigned_to' => '',
        ])->assertRedirect(route('computers.index'));

        $this->assertSame('77777', $computer->fresh()->serial);
    }

    private function fakeImage(string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            $name,
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/Dk4AAAAASUVORK5CYII=')
        );
    }
}
