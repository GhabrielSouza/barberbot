<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use App\Models\Product;
use App\Models\Service;
use App\Models\Barber;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;

class SalesController extends Controller
{
    public function index(Tenant $company)
    {
        $sales = DB::table('sales')->orderBy('date', 'desc')->paginate(15);

        return response()->json($sales);
    }

    public function store(Request $request, Tenant $company)
    {
        $data = $request->validate([
            'kind' => ['required', 'in:service,product'],
            'service_id' => ['nullable', 'uuid', 'exists:tenant.services,id'],
            'product_id' => ['nullable', 'uuid', 'exists:tenant.products,id'],
            'qty' => ['required', 'integer', 'min:1'],
            'unit_price' => ['required', 'numeric'],
            'total' => ['required', 'numeric'],
            'payment_method' => ['nullable', 'string'],
            'team_member_id' => ['required', 'uuid', Rule::exists('tenant.team_members', 'id')],
            'date' => ['required', 'date_format:Y-m-d'],
        ]);

        if ($data['kind'] === 'service' && empty($data['service_id'])) {
            return response()->json(['message' => 'service_id is required for service sales'], 422);
        }

        if ($data['kind'] === 'product' && empty($data['product_id'])) {
            return response()->json(['message' => 'product_id is required for product sales'], 422);
        }

        $id = Str::uuid()->toString();

        try {
            DB::beginTransaction();

            if ($data['kind'] === 'product') {
                $productId = $data['product_id'];
                $qty = (int) $data['qty'];

                $updated = Product::whereKey($productId)
                    ->where('stock', '>=', $qty)
                    ->decrement('stock', $qty);

                if (! $updated) {
                    DB::rollBack();
                    return response()->json(['message' => 'Insufficient stock'], 422);
                }

                $itemName = Product::whereKey($productId)->value('name');
            } else {
                $itemName = Service::whereKey($data['service_id'])->value('name');
            }

            DB::table('sales')->insert([
                'id' => $id,
                'kind' => $data['kind'],
                'service_id' => $data['service_id'] ?? null,
                'product_id' => $data['product_id'] ?? null,
                'item_name' => $itemName ?? ($data['item_name'] ?? ''),
                'qty' => $data['qty'],
                'unit_price' => $data['unit_price'],
                'total' => $data['total'],
                'payment_method' => $data['payment_method'] ?? null,
                'team_member_id' => $data['team_member_id'],
                'date' => $data['date'],
                'created_at' => Carbon::now(),
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Could not save sale', 'error' => $e->getMessage()], 500);
        }

        $sale = DB::table('sales')->where('id', $id)->first();

        return response()->json($sale, 201);
    }

    public function show(Tenant $company, $sale)
    {
        $row = DB::table('sales')->where('id', $sale)->first();

        if (! $row) {
            return response()->json(['message' => 'Sale not found'], 404);
        }

        return response()->json($row);
    }

    public function destroy(Tenant $company, $sale): JsonResponse
    {
        DB::table('sales')->where('id', $sale)->delete();

        return response()->json(['success' => true, 'message' => 'Sale deleted']);
    }
}
