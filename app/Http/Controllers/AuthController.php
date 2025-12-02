<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Mail\LoginOtpMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AuthController extends Controller
{
    /**
     * Show registration form
     */
    public function showRegister(): View
    {
        return view('auth.register');
    }

    /**
     * Handle user registration
     */
    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        Auth::login($user);

        return redirect()
            ->route('home')
            ->with('success', 'Registrasi berhasil! Selamat datang di Pineus Tilu!');
    }

    /**
     * Show login form
     */
    public function showLogin(): View
    {
        return view('auth.login');
    }

    /**
     * Handle user login (Step 1: Verify credentials and send OTP)
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if ($user === null || !Hash::check($credentials['password'], $user->password)) {
            return back()
                ->withErrors(['email' => 'Email atau password salah.'])
                ->onlyInput('email');
        }

        // Generate and send OTP
        $otp = $user->generateLoginOTP();
        
        try {
            Mail::to($user->email)->send(new LoginOtpMail($user, $otp));
        } catch (\Exception $e) {
            return back()
                ->withErrors(['email' => 'Gagal mengirim OTP. Silakan coba lagi.'])
                ->onlyInput('email');
        }

        // Store user ID in session for OTP verification
        $request->session()->put('login_user_id', $user->id);

        return redirect()
            ->route('login.verify')
            ->with('success', 'Kode OTP telah dikirim ke email Anda. Silakan cek inbox atau spam folder.');
    }

    /**
     * Show OTP verification form
     */
    public function showVerifyOtp(): View
    {
        if (!session()->has('login_user_id')) {
            return redirect()->route('login');
        }

        return view('auth.verify-otp');
    }

    /**
     * Verify login OTP (Step 2: Verify OTP and complete login)
     */
    public function verifyOtp(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'otp' => 'required|digits:6'
        ]);

        $userId = $request->session()->get('login_user_id');
        
        if ($userId === null) {
            return redirect()
                ->route('login')
                ->withErrors(['otp' => 'Sesi login telah berakhir. Silakan login ulang.']);
        }

        $user = User::find($userId);

        if ($user === null) {
            return redirect()
                ->route('login')
                ->withErrors(['otp' => 'User tidak ditemukan.']);
        }

        if ($user->verifyLoginOTP($validated['otp'])) {
            Auth::login($user);
            $request->session()->forget('login_user_id');
            $request->session()->regenerate();

            return redirect()
                ->route('home')
                ->with('success', 'Login berhasil! Selamat datang kembali di Pineus Tilu!');
        }

        return back()
            ->withErrors(['otp' => 'Kode OTP salah atau sudah kadaluarsa.']);
    }

    /**
     * Resend login OTP
     */
    public function resendLoginOtp(Request $request): RedirectResponse
    {
        $userId = $request->session()->get('login_user_id');
        
        if ($userId === null) {
            return redirect()
                ->route('login')
                ->withErrors(['otp' => 'Sesi login telah berakhir.']);
        }

        $user = User::find($userId);

        if ($user === null) {
            return redirect()->route('login');
        }

        $otp = $user->generateLoginOTP();
        
        try {
            Mail::to($user->email)->send(new LoginOtpMail($user, $otp));
        } catch (\Exception $e) {
            return back()
                ->withErrors(['otp' => 'Gagal mengirim ulang OTP.']);
        }

        return back()
            ->with('success', 'Kode OTP baru telah dikirim ke email Anda.');
    }

    /**
     * Handle user logout
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect()
            ->route('login')
            ->with('success', 'Logout berhasil! Sampai jumpa di petualangan berikutnya!');
    }
}