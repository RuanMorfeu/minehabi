<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlockedIp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IpBlockController extends Controller
{
    /**
     * Bloqueia um endereço IP
     */
    public function store(Request $request)
    {
        $request->validate([
            'ip_address' => 'required|ip',
        ]);

        // Verifica se o IP já está bloqueado
        $existingBlock = BlockedIp::where('ip', $request->ip_address)
            ->where('active', true)
            ->first();

        if ($existingBlock) {
            return redirect()->back()->with('warning', 'Este IP já está bloqueado.');
        }

        // Cria um novo bloqueio
        BlockedIp::create([
            'ip' => $request->ip_address,
            'reason' => $request->reason ?? 'Bloqueio manual via painel de administração',
            'blocked_by' => Auth::id(),
            'blocked_at' => now(),
            'active' => true,
        ]);

        return redirect()->back()->with('success', 'IP bloqueado com sucesso.');
    }

    /**
     * Remove o bloqueio de um IP
     */
    public function destroy($id)
    {
        $blockedIp = BlockedIp::findOrFail($id);
        $blockedIp->update(['active' => false]);

        return redirect()->back()->with('success', 'Bloqueio de IP removido com sucesso.');
    }
}
