@extends('layouts.app')

@section('title', 'Verifikasi OTP Login - Pineus Tilu')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-green-500 via-emerald-500 to-teal-500 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8 bg-white p-10 rounded-2xl shadow-2xl">
        <!-- Header -->
        <div class="text-center">
            <div class="bg-green-100 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
            </div>
            <h2 class="text-center text-4xl font-extrabold text-gray-900">
                Verifikasi OTP
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                Kode OTP telah dikirim ke email Anda
            </p>
        </div>

        <!-- Info Message -->
        <div class="bg-blue-50 border-2 border-blue-200 rounded-xl p-4">
            <div class="flex items-start">
                <svg class="w-6 h-6 text-blue-600 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                </svg>
                <div class="text-sm text-blue-800">
                    <p class="font-semibold mb-1">Periksa email Anda</p>
                    <p>Kami telah mengirim kode OTP 6 digit ke email Anda. Silakan periksa inbox atau folder spam.</p>
                </div>
            </div>
        </div>
        
        <!-- OTP Form -->
        <form class="mt-8 space-y-6" action="{{ route('login.verifyOtp') }}" method="POST">
            @csrf
            
            <div>
                <label for="otp" class="block text-sm font-medium text-gray-700 mb-2 text-center">
                    Masukkan 6 Digit Kode OTP
                </label>
                <input type="text" 
                       id="otp" 
                       name="otp" 
                       maxlength="6" 
                       pattern="[0-9]{6}"
                       required
                       class="w-full px-4 py-4 text-center text-2xl font-bold tracking-widest border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 bg-white @error('otp') border-red-500 @enderror"
                       placeholder="000000"
                       autocomplete="off"
                       autofocus>
                @error('otp')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <button type="submit" 
                        class="w-full bg-green-600 text-white py-4 rounded-xl text-lg font-semibold hover:bg-green-700 transition duration-150 shadow-lg hover:shadow-xl flex items-center justify-center space-x-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    <span>Verifikasi & Login</span>
                </button>
            </div>
        </form>

        <!-- Resend OTP -->
        <div class="text-center mt-6">
            <p class="text-sm text-gray-600 mb-3">
                Tidak menerima kode OTP?
            </p>
            <form action="{{ route('login.resendOtp') }}" method="POST" class="inline">
                @csrf
                <button type="submit" 
                        class="text-green-600 hover:text-green-700 font-semibold text-sm underline">
                    Kirim Ulang OTP
                </button>
            </form>
        </div>

        <!-- Timer Warning -->
        <div class="mt-6 text-center bg-orange-50 border border-orange-200 rounded-lg p-4">
            <div class="flex items-center justify-center space-x-2 text-orange-700">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="text-sm font-medium">Kode OTP berlaku selama 10 menit</span>
            </div>
        </div>

        <!-- Back to Login -->
        <div class="text-center">
            <a href="{{ route('login') }}" class="text-sm text-gray-600 hover:text-gray-900">
                ← Kembali ke halaman login
            </a>
        </div>
    </div>
</div>

<script>
// Auto-format OTP input (only numbers)
document.getElementById('otp').addEventListener('input', function(e) {
    this.value = this.value.replace(/[^0-9]/g, '');
});

// Auto-submit when 6 digits entered
document.getElementById('otp').addEventListener('input', function(e) {
    if (this.value.length === 6) {
        // Optional: Auto-submit form
        // this.form.submit();
    }
});
</script>
@endsection