<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Carbon\Carbon;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    private const OTP_LENGTH = 6;
    private const OTP_EXPIRY_MINUTES = 10;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'login_otp',
        'login_otp_expires_at',
        'is_login_verified',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'login_otp',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'login_otp_expires_at' => 'datetime',
        'is_login_verified' => 'boolean',
        'password' => 'hashed',
    ];

    /**
     * Generate OTP for login verification
     */
    public function generateLoginOTP(): string
    {
        $this->login_otp = str_pad((string)random_int(0, 999999), self::OTP_LENGTH, '0', STR_PAD_LEFT);
        $this->login_otp_expires_at = Carbon::now()->addMinutes(self::OTP_EXPIRY_MINUTES);
        $this->is_login_verified = false;
        $this->save();
        
        return $this->login_otp;
    }

    /**
     * Verify login OTP code
     */
    public function verifyLoginOTP(string $otp): bool
    {
        if ($this->login_otp === $otp && 
            $this->login_otp_expires_at !== null && 
            $this->login_otp_expires_at->isFuture()) {
            
            $this->is_login_verified = true;
            $this->login_otp = null;
            $this->login_otp_expires_at = null;
            $this->save();
            
            return true;
        }
        
        return false;
    }

    /**
     * Check if login OTP is still valid
     */
    public function hasValidLoginOTP(): bool
    {
        return $this->login_otp !== null && 
               $this->login_otp_expires_at !== null && 
               $this->login_otp_expires_at->isFuture();
    }
}