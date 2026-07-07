<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Location;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    /**
     * Autocomplete lokasi berdasarkan keyword dan tipe.
     *
     * GET /api/locations?q=jak&type=airport
     * GET /api/locations?q=gam&type=train_station
     * GET /api/locations?q=band (tanpa filter type, cari semua)
     */
    public function search(Request $request): JsonResponse
    {
        $keyword = $request->q ?? '';
        $type    = $request->type; // 'airport' | 'train_station' | null

        $locations = Location::query()
            ->when($type, fn($q) => $q->where('type', $type))
            ->when($keyword, function ($q) use ($keyword) {
                $q->where(function ($q) use ($keyword) {
                    $q->where('code', 'like', "%{$keyword}%")
                      ->orWhere('name', 'like', "%{$keyword}%")
                      ->orWhere('city', 'like', "%{$keyword}%");
                });
            })
            ->orderBy('city')
            ->limit(8)
            ->get(['id', 'type', 'code', 'name', 'city']);

        return response()->json([
            'data' => $locations->map(fn($loc) => [
                'id'    => $loc->id,
                'type'  => $loc->type,
                'code'  => $loc->code,
                'name'  => $loc->name,
                'city'  => $loc->city,
                'label' => "{$loc->city} ({$loc->code})", // ditampilkan di dropdown
            ]),
        ]);
    }
}
