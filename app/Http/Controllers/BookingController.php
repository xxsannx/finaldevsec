<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\Booking;
use App\Mail\PaymentOtpMail;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class BookingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show booking creation form
     */
    public function create(int $roomId): View
    {
        $room = Room::findOrFail($roomId);
        return view('booking.create', compact('room'));
    }

    /**
     * Store a new booking
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'check_in' => 'required|date|after_or_equal:today',
            'check_out' => 'required|date|after:check_in',
        ]);

        $room = Room::findOrFail($validated['room_id']);
        
        $checkIn = Carbon::parse($validated['check_in']);
        $checkOut = Carbon::parse($validated['check_out']);
        $duration = $checkIn->diffInDays($checkOut);
        $totalPrice = $room->price * $duration;

        $booking = Booking::create([
            'user_id' => Auth::id(),
            'room_id' => $validated['room_id'],
            'check_in' => $validated['check_in'],
            'check_out' => $validated['check_out'],
            'duration' => $duration,
            'total_price' => $totalPrice,
            'status' => 'pending'
        ]);

        // Generate booking OTP
        $otp = $booking->generateOTP();

        return redirect()
            ->route('booking.confirm', $booking->id)
            ->with('success', 'Booking berhasil dibuat! Kode OTP: ' . $otp);
    }

    /**
     * Show booking confirmation page
     */
    public function confirm(int $id): View
    {
        $booking = Booking::with(['room', 'user'])->findOrFail($id);
        
        $this->authorizeBookingAccess($booking);

        return view('booking.confirm', compact('booking'));
    }

    /**
     * Verify OTP for booking
     */
    public function verifyOtp(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'otp' => 'required|digits:6'
        ]);

        $booking = Booking::findOrFail($id);
        $this->authorizeBookingAccess($booking);

        if ($booking->verifyOTP($validated['otp'])) {
            return redirect()
                ->route('booking.payment', $booking->id)
                ->with('success', 'Verifikasi OTP berhasil! Silakan lanjutkan ke pembayaran.');
        }

        return back()
            ->withErrors(['otp' => 'Kode OTP salah atau sudah kadaluarsa']);
    }

    /**
     * Show payment page
     */
    public function showPayment(int $id): View
    {
        $booking = Booking::with(['room', 'user'])->findOrFail($id);
        
        $this->authorizeBookingAccess($booking);

        if (!$booking->otp_verified) {
            return redirect()->route('booking.confirm', $booking->id);
        }

        return view('booking.payment', compact('booking'));
    }

    /**
     * Process payment and send payment OTP
     */
    public function processPayment(Request $request, int $id): RedirectResponse
    {
        $booking = Booking::with(['room', 'user'])->findOrFail($id);
        
        $this->authorizeBookingAccess($booking);

        if (!$booking->otp_verified) {
            return redirect()
                ->route('booking.confirm', $booking->id)
                ->withErrors(['error' => 'Silakan verifikasi booking terlebih dahulu.']);
        }

        // Generate payment OTP
        $paymentOtp = $booking->generatePaymentOTP();
        
        try {
            Mail::to($booking->user->email)->send(new PaymentOtpMail($booking, $paymentOtp));
        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => 'Gagal mengirim OTP pembayaran. Silakan coba lagi.']);
        }

        return redirect()
            ->route('booking.verifyPayment', $booking->id)
            ->with('success', 'Kode OTP pembayaran telah dikirim ke email Anda.');
    }

    /**
     * Show payment OTP verification page
     */
    public function showVerifyPayment(int $id): View
    {
        $booking = Booking::with(['room', 'user'])->findOrFail($id);
        
        $this->authorizeBookingAccess($booking);

        if (!$booking->hasValidPaymentOTP()) {
            return redirect()->route('booking.payment', $booking->id);
        }

        return view('booking.verify-payment', compact('booking'));
    }

    /**
     * Verify payment OTP
     */
    public function verifyPaymentOtp(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'payment_otp' => 'required|digits:6'
        ]);

        $booking = Booking::findOrFail($id);
        $this->authorizeBookingAccess($booking);

        if ($booking->verifyPaymentOTP($validated['payment_otp'])) {
            return redirect()
                ->route('booking.success', $booking->id)
                ->with('success', 'Pembayaran berhasil! Terima kasih telah booking di Pineus Tilu.');
        }

        return back()
            ->withErrors(['payment_otp' => 'Kode OTP pembayaran salah atau sudah kadaluarsa']);
    }

    /**
     * Resend payment OTP
     */
    public function resendPaymentOtp(int $id): RedirectResponse
    {
        $booking = Booking::with('user')->findOrFail($id);
        
        $this->authorizeBookingAccess($booking);

        $paymentOtp = $booking->generatePaymentOTP();
        
        try {
            Mail::to($booking->user->email)->send(new PaymentOtpMail($booking, $paymentOtp));
        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => 'Gagal mengirim ulang OTP pembayaran.']);
        }

        return back()
            ->with('success', 'Kode OTP pembayaran baru telah dikirim ke email Anda.');
    }

    /**
     * Show booking success page
     */
    public function success(int $id): View
    {
        $booking = Booking::with(['room', 'user'])->findOrFail($id);
        
        $this->authorizeBookingAccess($booking);

        if (!$booking->payment_verified) {
            return redirect()->route('booking.payment', $booking->id);
        }

        return view('booking.success', compact('booking'));
    }

    /**
     * Show user's bookings
     */
    public function myBookings(): View
    {
        $bookings = Booking::with('room')
            ->where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();

        return view('booking.my-bookings', compact('bookings'));
    }

    /**
     * Authorize booking access for current user
     */
    private function authorizeBookingAccess(Booking $booking): void
    {
        if ($booking->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }
    }
}