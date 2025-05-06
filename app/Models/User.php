<?php

declare(strict_types=1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

final class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the user's formatted phone details
     */
    protected function getPhoneDetailsAttribute()
    {
        if (empty($this->phone)) {
            return null;
        }

        $util = PhoneNumberUtil::getInstance();
        $number = $util->parse($this->phone, null);

        return (object) [
            'raw' => $this->phone,
            'e164' => $util->format($number, PhoneNumberFormat::E164),
            'international' => $util->format($number, PhoneNumberFormat::INTERNATIONAL),
            'national' => $util->format($number, PhoneNumberFormat::NATIONAL),
            'rfc' => $util->format($number, PhoneNumberFormat::RFC3966),
            'country_code' => $number->getCountryCode(),
            'region' => $util->getRegionCodeForNumber($number),
        ];
    }
}
