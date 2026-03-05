<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ClientLocationController extends Controller
{
    public function store(Request $request)
    {
        $lat = (float) $request->input('latitude');
        $lng = (float) $request->input('longitude');
        
        // lưu session (đủ dùng)
        session([
            'client_lat' => $lat,
            'client_lng' => $lng,
        ]);

        return response()->noContent(200);
    }
}