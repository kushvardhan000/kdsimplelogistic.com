<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\FuelStation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

class InlineEntityController extends Controller
{
    public function storeBranch(Request $request): JsonResponse
    {
        $request->merge(['code' => strtoupper(trim($request->input('code')))]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:20', 'unique:branches,code', 'regex:/^[A-Z0-9\-]{2,20}$/'],
            'address' => ['nullable', 'string'],
        ]);

        $branch = Branch::create($validated);

        return response()->json([
            'success' => true,
            'entity' => ['id' => $branch->id, 'name' => $branch->name, 'code' => $branch->code],
        ]);
    }

    public function storeFuelStation(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'contact_info' => ['nullable', 'string'],
            'address' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $slug = Str::slug($validated['name']);
        $baseSlug = $slug;
        $suffix = 2;
        while (FuelStation::where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$suffix++;
        }

        $station = FuelStation::create(array_merge($validated, [
            'slug' => $slug,
            'is_active' => $request->has('is_active'),
        ]));

        return response()->json([
            'success' => true,
            'entity' => [
                'id' => $station->id,
                'name' => $station->name,
                'branch' => $station->branch?->name,
                'current_balance' => 0.0,
            ],
        ]);
    }
}
