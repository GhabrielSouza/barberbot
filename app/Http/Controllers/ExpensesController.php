<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;

class ExpensesController extends Controller
{
    public function index(Tenant $company)
    {
        $expenses = DB::table('expenses')->orderBy('date', 'desc')->paginate(15);

        return response()->json($expenses);
    }

    public function store(Request $request, Tenant $company)
    {
        $data = $request->validate([
            'label' => ['required', 'string'],
            'note' => ['nullable', 'string'],
            'value' => ['required', 'numeric'],
            'date' => ['required', 'date_format:Y-m-d'],
        ]);

        $id = Str::uuid()->toString();

        DB::table('expenses')->insert([
            'id' => $id,
            'label' => $data['label'],
            'note' => $data['note'] ?? null,
            'value' => $data['value'],
            'date' => $data['date'],
            'created_at' => Carbon::now(),
        ]);

        $row = DB::table('expenses')->where('id', $id)->first();

        return response()->json($row, 201);
    }

    public function show(Tenant $company, $expense)
    {
        $row = DB::table('expenses')->where('id', $expense)->first();

        if (! $row) {
            return response()->json(['message' => 'Expense not found'], 404);
        }

        return response()->json($row);
    }

    public function destroy(Tenant $company, $expense): JsonResponse
    {
        DB::table('expenses')->where('id', $expense)->delete();

        return response()->json(['success' => true, 'message' => 'Expense deleted']);
    }
}
