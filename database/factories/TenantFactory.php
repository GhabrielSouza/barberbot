<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Tenant>
 */
class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->company();
        $slug = Str::slug($name).'-'.fake()->unique()->numberBetween(1000, 9999);

        return [
            'name' => $name,
            'slug' => $slug,
            'schema_name' => 'tenant_'.Str::snake($slug),
            'segment' => fake()->randomElement(['barbershop', 'beauty', 'other']),
            'phone' => fake()->phoneNumber(),
            'city' => fake()->city(),
            'status' => 'active',
        ];
    }
}
