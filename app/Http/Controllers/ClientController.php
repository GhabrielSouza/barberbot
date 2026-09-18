<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Http\Resources\ClientResource;
use App\Models\Client;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ClientController extends Controller
{
    public function index(Tenant $company): AnonymousResourceCollection
    {
        return ClientResource::collection(Client::orderBy('name')->paginate(15));
    }

    public function store(CreateClientRequest $request, Tenant $company): ClientResource
    {
        return new ClientResource(Client::create($request->validated()));
    }

    public function show(Tenant $company, Client $client): ClientResource
    {
        return new ClientResource($client);
    }

    public function update(UpdateClientRequest $request, Tenant $company, Client $client): ClientResource
    {
        $client->update($request->validated());

        return new ClientResource($client->fresh());
    }

    public function destroy(Tenant $company, Client $client): JsonResponse
    {
        $client->delete();

        return response()->json(['success' => true, 'message' => 'Cliente excluído com sucesso.']);
    }
}
