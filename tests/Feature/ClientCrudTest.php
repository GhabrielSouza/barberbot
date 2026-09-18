<?php

namespace Tests\Feature;

use App\Models\User;

class ClientCrudTest extends TenantCrudTestCase
{
    public function test_complete_client_crud_and_validation(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $base = '/api/companies/'.$user->tenant_id.'/clients';
        $this->postJson($base, [])->assertUnprocessable()->assertJsonValidationErrors('name');
        $id = $this->postJson($base, ['name' => 'Cliente', 'phone' => '11999999999', 'rating' => 4.5])
            ->assertCreated()->json('data.id');
        $this->getJson($base)->assertOk()->assertJsonPath('meta.total', 1);
        $this->getJson($base.'/'.$id)->assertOk()->assertJsonPath('data.rating', '4.5');
        $this->putJson($base.'/'.$id, ['notes' => 'Preferência', 'name' => 'Novo'])->assertOk()->assertJsonPath('data.name', 'Novo');
        $this->patchJson($base.'/'.$id, ['rating' => 6])->assertUnprocessable();
        $other = User::factory()->create();
        $this->getJson('/api/companies/'.$other->tenant_id.'/clients/'.$id)->assertForbidden();
        $this->deleteJson($base.'/'.$id)->assertOk();
        $this->getJson($base.'/'.$id)->assertNotFound();
        $this->app['auth']->forgetGuards();
        $this->getJson($base)->assertUnauthorized();
    }
}
