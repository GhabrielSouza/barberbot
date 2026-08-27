<?php

namespace Tests\Feature;

use App\Models\Service;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ServiceCrudTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('services');

        Schema::create('services', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->integer('duration_min');
            $table->decimal('price', 10, 2);
            $table->string('category')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function test_service_can_be_created_and_read(): void
    {
        $service = Service::create([
            'name' => 'Corte Clássico',
            'duration_min' => 45,
            'price' => 60.00,
            'category' => 'Cabelo',
            'active' => true,
        ]);

        $this->assertDatabaseHas('services', [
            'id' => $service->id,
            'name' => 'Corte Clássico',
        ]);

        $this->assertSame(45, Service::find($service->id)->duration_min);
    }
}
