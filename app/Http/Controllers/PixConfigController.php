<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use App\Models\Tenant;

class PixConfigController extends Controller
{
    public function show(Tenant $company)
    {
        $row = DB::table('pix_config')->first();

        return response()->json($row);
    }

    public function store(Request $request, Tenant $company)
    {
        $data = $request->validate([
            'key' => ['required', 'string'],
            'key_type' => ['required', 'string', 'max:20'],
            'holder_name' => ['nullable', 'string'],
            'city' => ['nullable', 'string'],
        ]);

        $existing = DB::table('pix_config')->first();

        if ($existing) {
            DB::table('pix_config')->where('id', $existing->id)->update([
                'key' => $data['key'],
                'key_type' => $data['key_type'],
                'holder_name' => $data['holder_name'] ?? null,
                'city' => $data['city'] ?? null,
                'updated_at' => Carbon::now(),
            ]);

            $row = DB::table('pix_config')->where('id', $existing->id)->first();
        } else {
            $id = Str::uuid()->toString();

            DB::table('pix_config')->insert([
                'id' => $id,
                'key' => $data['key'],
                'key_type' => $data['key_type'],
                'holder_name' => $data['holder_name'] ?? null,
                'city' => $data['city'] ?? null,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            $row = DB::table('pix_config')->where('id', $id)->first();
        }

        return response()->json($row, 201);
    }
}
