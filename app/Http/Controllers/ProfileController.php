<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile', ['user' => $request->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => 'required|string|max:100', 'locale' => 'required|in:en,ja,id']);
        $request->user()->update($data);
        $request->session()->put('locale', $data['locale']);

        return back()->with('success', __('ui.saved'));
    }

    public function password(Request $request): RedirectResponse
    {
        $data = $request->validate(['current_password' => 'required|current_password', 'password' => ['required', 'confirmed', Password::min(10)->letters()->numbers()]]);
        $request->user()->update(['password' => $data['password'], 'session_version' => $request->user()->session_version + 1]);
        $request->session()->put('session_version', $request->user()->session_version);
        $request->session()->regenerate();

        return back()->with('success', __('ui.password_updated'));
    }

    public function locale(Request $request): RedirectResponse
    {
        $data = $request->validate(['locale' => 'required|in:en,ja,id']);
        $request->session()->put('locale', $data['locale']);
        $request->user()?->update($data);

        return back();
    }
}
