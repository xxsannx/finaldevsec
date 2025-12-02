@extends('layouts.app')

@section('title', 'Pembayaran - Pineus Tilu')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-green-50 to-emerald-100 py-12">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Progress Bar -->
        <div class="mb-8">
            <div class="flex items-center justify-center">
                <div class="flex items-center">
                    <div class="bg-green-500 text-white rounded-full w-10 h-10 flex items-center justify-center font-bold shadow-lg">
                        ✓
                    </div>
                    <div class="w-24 h-1 bg-green-500"></div>
                </div>
                <div class="flex items-center">
                    <div class="bg-green-500 text-white rounded-full w-10 h-10 flex items-center justify-center font-bold shadow-lg">
                        ✓
                    </div>
                    <div class="w-24 h-1 bg-green-500"></div>
                </div>
                <div class="flex items-center">
                    <div class="bg-green-500 text-white rounded-full w-10 h-10 flex items-center justify-center font-bold shadow-lg">
                        3
                    </div>
                    <div class="w-24 h-1 bg-gray-300"></div>
                </div>
                <div class="flex items-center">
                    <div class="bg-gray-300 text-gray-600 rounded-full w-10 h-10 flex items-center justify-center font-bold">
                        4
                    </div>
                </div>
            </div>
            <div class="flex justify-between mt-2 text-sm font-medium px-4">
                <span class="text-green-600">Pilih Spot</span>
                <span class="text-green-600">Verifikasi</span>
                <span class="text-green-600">Bayar</span>
                <span class="text-gray-500">Selesai</span>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-xl p-8">
            <!-- Header -->
            <div class="text-center mb-8">
                <div class="bg-green-100 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Pembayaran Booking</h1>
                <p class="text-gray-600">Selesaikan pembayaran untuk mengkonfirmasi booking Anda</p>
            </div>

            <!-- Booking Summary -->
            <div class="bg-gradient-to-r from-green-500 to-emerald-600 text-white rounded-xl p-6 mb-6 shadow-lg">
                <h3 class="font-bold text-lg mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Ringkasan Booking
                </h3>
                
                <div class="space-y-3">
                    <div class="flex justify-between text-sm bg-white/20 rounded-lg p-3">
                        <span>Booking ID:</span>
                        <span class="font-bold">#{{ str_pad($booking->id, 6, '0', STR_PAD_LEFT) }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span>Spot Camping:</span>
                        <span class="font-semibold">{{ $booking->room->name }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span>Check-in:</span>
                        <span class="font-semibold">{{ $booking->check_in->format('d M Y') }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span>Check-out:</span>
                        <span class="font-semibold">{{ $booking->check_out->format('d M Y') }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span>Durasi:</span>
                        <span class="font-semibold">{{ $booking->duration }} malam</span>
                    </div>
                    <div class="border-t border-white/30 pt-3 mt-3">
                        <div class="flex justify-between text-lg font-bold">
                            <span>Total Pembayaran:</span>
                            <span>Rp {{ number_format($booking->total_price, 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment Instructions -->
            <div class="bg-blue-50 border-2 border-blue-200 rounded-xl p-6 mb-6">
                <h3 class="font-bold text-gray-900 mb-3 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                    </svg>
                    Cara Pembayaran
                </h3>
                <ol class="text-sm text-gray-700 space-y-2 ml-7">
                    <li><strong>1.</strong> Klik tombol "Proses Pembayaran" di bawah</li>
                    <li><strong>2.</strong> Kode OTP akan dikirim ke email Anda</li>
                    <li><strong>3.</strong> Masukkan kode OTP untuk konfirmasi pembayaran</li>
                    <li><strong>4.</strong> Pembayaran Anda akan segera diproses</li>
                </ol>
            </div>

            <!-- Payment Method Info -->
            <div class="bg-gray-50 border border-gray-200 rounded-xl p-6 mb-6">
                <h3 class="font-bold text-gray-900 mb-4">Metode Pembayaran</h3>
                <div class="space-y-3">
                    <div class="flex items-center p-3 bg-white rounded-lg border border-green-200">
                        <div class="bg-green-100 p-2 rounded-lg mr-3">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <p class="font-semibold text-gray-900">Transfer Bank</p>
                            <p class="text-sm text-gray-600">Verifikasi OTP via Email</p>
                        </div>
                        <div class="text-green-600">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment Button -->
            <form action="{{ route('booking.processPayment', $booking->id) }}" method="POST">
                @csrf
                <button type="submit" 
                        class="w-full bg-green-600 text-white py-4 rounded-xl text-lg font-semibold hover:bg-green-700 transition duration-150 shadow-lg hover:shadow-xl flex items-center justify-center space-x-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    <span>Proses Pembayaran</span>
                </button>
            </form>

            <!-- Security Info -->
            <div class="mt-6 text-center bg-gray-50 rounded-lg p-4">
                <div class="flex items-center justify-center space-x-2 text-gray-600">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    <span class="text-sm font-medium">Transaksi aman & terenkripsi</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection