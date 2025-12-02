@extends('layouts.app')

@section('title', 'Verifikasi Pembayaran - Pineus Tilu')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-green-50 to-emerald-100 py-12">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-2xl shadow-xl p-8">
            <!-- Header -->
            <div class="text-center mb-8">
                <div class="bg-green-100 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Verifikasi Pembayaran</h1>
                <p class="text-gray-600">Masukkan kode OTP untuk menyelesaikan pembayaran</p>
            </div>

            <!-- Success Message -->
            @if(session('success'))
            <div class="bg-green-50 border-2 border-green-200 rounded-xl p-4 mb-6">
                <div class="flex items-start">
                    <svg class="w-6 h-6 text-green-600 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <div class="text-sm text-green-800">
                        <p class="font-semibold">{{ session('success') }}</p>
                    </div>
                </div>
            </div>
            @endif

            <!-- Email Info -->
            <div class="bg-blue-50 border-2 border-blue-200 rounded-xl p-4 mb-6">
                <div class="flex items-start">
                    <svg class="w-6 h-6 text-blue-600 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    <div class="text-sm text-blue-800">
                        <p class="font-semibold mb-1">Kode OTP telah dikirim!</p>
                        <p>Periksa email Anda di <strong>{{ $booking->user->email }}</strong></p>
                        <p class="text-xs mt-2 text-blue-600">Jangan lupa cek folder spam jika tidak menemukan emailnya</p>
                    </div>
                </div>
            </div>

            <!-- Payment Summary -->
            <div class="bg-gray-50 border border-gray-200 rounded-xl p-6 mb-6">
                <h3 class="font-bold text-gray-900 mb-4">Detail Pembayaran</h3>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Booking ID:</span>
                        <span class="font-semibold text-gray-900">#{{ str_pad($booking->id, 6, '0', STR_PAD_LEFT) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Spot Camping:</span>
                        <span class="font-semibold text-gray-900">{{ $booking->room->name }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Durasi:</span>
                        <span class="font-semibold text-gray-900">{{ $booking->duration }} malam</span>
                    </div>
                    <div class="border-t border-gray-300 pt-3 mt-3">
                        <div class="flex justify-between text-lg font-bold">
                            <span class="text-gray-900">Total Pembayaran:</span>
                            <span class="text-green-600">Rp {{ number_format($booking->total_price, 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- OTP Form -->
            <form action="{{ route('booking.verifyPaymentOtp', $booking->id) }}" method="POST" class="mb-6">
                @csrf
                <div class="mb-6">
                    <label for="payment_otp" class="block text-sm font-medium text-gray-700 mb-2 text-center">
                        Masukkan 6 Digit Kode OTP Pembayaran
                    </label>
                    <input type="text" 
                           id="payment_otp" 
                           name="payment_otp" 
                           maxlength="6" 
                           pattern="[0-9]{6}"
                           required
                           class="w-full px-4 py-4 text-center text-2xl font-bold tracking-widest border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 bg-white @error('payment_otp') border-red-500 @enderror"
                           placeholder="000000"
                           autocomplete="off"
                           autofocus>
                    @error('payment_otp')
                        <p class="mt-2 text-sm text-red-600 text-center">{{ $message }}</p>
                    @enderror
                    <p class="text-sm text-gray-500 text-center mt-2">
                        Masukkan kode OTP yang dikirim ke email Anda
                    </p>
                </div>

                <button type="submit" 
                        class="w-full bg-green-600 text-white py-4 rounded-xl text-lg font-semibold hover:bg-green-700 transition duration-150 shadow-lg hover:shadow-xl flex items-center justify-center space-x-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>Konfirmasi Pembayaran</span>
                </button>
            </form>

            <!-- Resend OTP -->
            <div class="text-center mb-6">
                <p class="text-sm text-gray-600 mb-3">
                    Tidak menerima kode OTP?
                </p>
                <form action="{{ route('booking.resendPaymentOtp', $booking->id) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" 
                            class="text-green-600 hover:text-green-700 font-semibold text-sm underline">
                        Kirim Ulang OTP
                    </button>
                </form>
            </div>

            <!-- Timer Warning -->
            <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
                <div class="flex items-center justify-center space-x-2 text-orange-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="text-sm font-medium">⏰ Kode OTP berlaku selama 10 menit</span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-format OTP input (only numbers)
document.getElementById('payment_otp').addEventListener('input', function(e) {
    this.value = this.value.replace(/[^0-9]/g, '');
});
</script>
@endsection