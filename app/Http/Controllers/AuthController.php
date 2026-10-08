<?php

namespace App\Http\Controllers;

use App\Models\DeviceRecord;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function show(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $data = $request->validate(['username' => 'required|string|max:100', 'password' => 'required|string|max:200']);
        $key = 'login:'.Str::lower($data['username']).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['username' => __('ui.login_throttled', ['seconds' => RateLimiter::availableIn($key)])]);
        }
        $user = User::where('username', $data['username'])->first();
        if (! $user || ! Hash::check($data['password'], $user->password) || ! $user->active) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages(['username' => __('ui.invalid_login')]);
        }
        $token = $request->cookie('ep_device') ?: Str::random(64);
        $hash = hash('sha256', $token);
        DB::transaction(function () use ($user, $hash, $request) {
            $locked = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            if ($locked->device_lock && $locked->device_hash && ! hash_equals($locked->device_hash, $hash)) {
                throw ValidationException::withMessages(['username' => __('ui.device_denied')]);
            }
            if ($locked->device_lock && ! $locked->device_hash) {
                $locked->update(['device_hash' => $hash]);
            }
            $agent = $request->userAgent() ?? '';
            $browser = str_contains($agent, 'Edg/') ? 'Edge' : (str_contains($agent, 'Firefox/') ? 'Firefox' : (str_contains($agent, 'Chrome/') ? 'Chrome' : 'Safari / Browser'));
            $os = str_contains($agent, 'Android') ? 'Android' : (str_contains($agent, 'iPhone') ? 'iPhone' : (str_contains($agent, 'Windows') ? 'Windows' : (str_contains($agent, 'Mac') ? 'macOS' : 'Other')));
            DeviceRecord::updateOrCreate(['user_id' => $user->id, 'device_hash' => $hash], ['label' => "$browser / $os", 'ip_address' => $request->ip(), 'last_seen_at' => now(), 'revoked_at' => null]);
        });
        RateLimiter::clear($key);
        Auth::login($user->fresh());
        $request->session()->regenerate();
        $request->session()->put('session_version', $user->session_version);
        $request->session()->put('locale', $user->locale);
        Cookie::queue(cookie('ep_device', $token, 60 * 24 * 365, '/', null, $request->isSecure(), true, false, 'lax'));

        return redirect()->route('dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
