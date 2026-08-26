<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProductCrudTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('products');

        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->decimal('price', 10, 2);
            $table->integer('stock')->default(0);
            $table->string('category')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function test_product_can_be_created_and_read(): void
    {
        $product = Product::create([
            'name' => 'Pomada Modeladora',
            'price' => 29.90,
            'stock' => 15,
            'category' => 'Higiene',
            'active' => true,
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Pomada Modeladora',
        ]);

        $this->assertSame('Pomada Modeladora', Product::find($product->id)->name);
    }
}
