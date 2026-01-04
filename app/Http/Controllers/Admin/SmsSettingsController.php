<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SmsSettingsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $settings = DB::table('sms_settings')->get();

        return response()->json($settings);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $eventType)
    {
        $validator = Validator::make($request->all(), [
            'is_active' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $updated = DB::table('sms_settings')
            ->where('event_type', $eventType)
            ->update(['is_active' => $request->is_active, 'updated_at' => now()]);

        if ($updated) {
            return response()->json(['message' => 'Configuração de SMS atualizada com sucesso.']);
        }

        return response()->json(['message' => 'Tipo de evento não encontrado.'], 404);
    }
}
