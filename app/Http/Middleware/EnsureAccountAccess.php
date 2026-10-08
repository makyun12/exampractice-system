<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $hash = hash('sha256', $request->cookie('ep_device', ''));
        $deviceValid = ! $user->device_lock || ($user->device_hash && hash_equals($user->device_hash, $hash));
        $revoked = $user->devices()->where('device_hash', $hash)->whereNotNull('revoked_at')->exists();
        if (! $user->active || ! $deviceValid || $revoked || $request->session()->get('session_version') !== $user->session_version) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['username' => __('ui.session_revoked')]);
        }
        $user->devices()->where('device_hash', $hash)->where('last_seen_at', '<', now()->subMinutes(2))->update(['last_seen_at' => now()]);

        return $next($request);
    }
}
