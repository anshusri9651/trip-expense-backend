<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View { return view('auth.login'); }

    public function showRegister(): View { return view('auth.register'); }

    public function showForgotPassword(): View { return view('auth.forgot-password'); }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email']]);
        $status = Password::sendResetLink($data);
        if ($status !== Password::RESET_LINK_SENT) return back()->withErrors(['email' => __($status)])->onlyInput('email');
        return back()->with('status', 'We sent a password reset link to your email address.');
    }

    public function showResetPassword(string $token): View { return view('auth.reset-password', ['token' => $token, 'email' => request('email')]); }

    public function resetPassword(Request $request): RedirectResponse
    {
        $data = $request->validate(['token' => ['required'], 'email' => ['required', 'email'], 'password' => ['required', 'confirmed', 'min:8']]);
        $status = Password::reset($data, function (User $user, string $password): void {
            $user->forceFill(['password' => Hash::make($password), 'remember_token' => null])->save();
        });
        if ($status !== Password::PASSWORD_RESET) return back()->withErrors(['email' => __($status)])->withInput($request->only('email'));
        return redirect()->route('login')->with('status', 'Your password has been reset. You can sign in now.');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);
        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Those credentials do not match our records.'])->onlyInput('email');
        }
        if (! $request->user()->is_active) {
            Auth::logout();
            return back()->withErrors(['email' => 'This account has been disabled. Contact an administrator.'])->onlyInput('email');
        }
        $request->session()->regenerate();
        return redirect()->intended(route('dashboard'));
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);
        $colors = ['#4F46E5', '#0D9488', '#D97706', '#7C3AED', '#DB2777'];
        $isFirstAdmin = ! User::where('is_admin', true)->exists();
        $user = User::create([
            'name' => $data['name'], 'email' => $data['email'],
            'password' => Hash::make($data['password']), 'avatar_color' => $colors[array_rand($colors)], 'is_admin' => $isFirstAdmin,
        ]);
        Auth::login($user);
        $request->session()->regenerate();
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
