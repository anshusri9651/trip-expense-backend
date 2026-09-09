<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View { return view('profile.edit', ['user' => $request->user()]); }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$request->user()->id], 'currency' => ['required', 'string', 'max:10'], 'currency_symbol' => ['required', 'string', 'max:5'], 'avatar_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'], 'notifications_enabled' => ['nullable', 'boolean']]);
        $request->user()->update([...$data, 'notifications_enabled' => $request->boolean('notifications_enabled')]);
        return back()->with('status', 'Profile preferences saved.');
    }

    public function password(Request $request): RedirectResponse
    {
        $data = $request->validate(['current_password' => ['required', 'string'], 'password' => ['required', 'string', 'min:8', 'confirmed']]);
        if (! Hash::check($data['current_password'], $request->user()->password)) return back()->withErrors(['current_password' => 'Your current password is incorrect.']);
        $request->user()->update(['password' => Hash::make($data['password'])]);
        $request->session()->regenerate();
        return back()->with('status', 'Password changed successfully.');
    }
}
