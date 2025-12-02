<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Booking extends Model
{
    use HasFactory;

    private const OTP_LENGTH = 6;
    private const OTP_EXPIRY_MINUTES = 10;

    protected $fillable = [
        'user_id',
        'room_id',
        'check_in',
        'check_out',
        'duration',
        'total_price',
        'otp',
        'otp_expires_at',
        'otp_verified',
        'payment_otp',
        'payment_otp_expires_at',
        'payment_verified',
        'status'
    ];

    protected $casts = [
        'check_in' => 'date',
        'check_out' => 'date',
        'otp_expires_at' => 'datetime',
        'payment_otp_expires_at' => 'datetime',
        'otp_verified' => 'boolean',
        'payment_verified' => 'boolean',
        'total_price' => 'decimal:2'
    ];

    /**
     * Get the user that owns the booking
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the room associated with the booking
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * Generate OTP for booking verification
     */
    public function generateOTP(): string
    {
        $this->otp = str_pad((string)random_int(0, 999999), self::OTP_LENGTH, '0', STR_PAD_LEFT);
        $this->otp_expires_at = Carbon::now()->addMinutes(self::OTP_EXPIRY_MINUTES);
        $this->save();
        
        return $this->otp;
    }

    /**
     * Verify booking OTP code
     */
    public function verifyOTP(string $otp): bool
    {
        if ($this->otp === $otp && 
            $this->otp_expires_at !== null && 
            $this->otp_expires_at->isFuture()) {
            
            $this->otp_verified = true;
            $this->status = 'confirmed';
            $this->save();
            
            return true;
        }
        
        return false;
    }

    /**
     * Generate OTP for payment verification
     */
    public function generatePaymentOTP(): string
    {
        $this->payment_otp = str_pad((string)random_int(0, 999999), self::OTP_LENGTH, '0', STR_PAD_LEFT);
        $this->payment_otp_expires_at = Carbon::now()->addMinutes(self::OTP_EXPIRY_MINUTES);
        $this->save();
        
        return $this->payment_otp;
    }

    /**
     * Verify payment OTP code
     */
    public function verifyPaymentOTP(string $otp): bool
    {
        if ($this->payment_otp === $otp && 
            $this->payment_otp_expires_at !== null && 
            $this->payment_otp_expires_at->isFuture()) {
            
            $this->payment_verified = true;
            $this->status = 'paid';
            $this->save();
            
            return true;
        }
        
        return false;
    }

    /**
     * Check if payment OTP is still valid
     */
    public function hasValidPaymentOTP(): bool
    {
        return $this->payment_otp !== null && 
               $this->payment_otp_expires_at !== null && 
               $this->payment_otp_expires_at->isFuture();
    }
}