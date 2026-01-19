<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Order;
use App\Notifications\ClientResetPasswordNotification;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Auth\Passwords\CanResetPassword as CanResetPasswordTrait;
use App\Traits\LogsActivity;

class Client extends Authenticatable implements CanResetPassword
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, CanResetPasswordTrait, LogsActivity;
    protected $primaryKey = 'client_id';

    protected $fillable = [
        'client_name',
        'email',
        'password',
        'person_in_charge',
        'address',
        'phone_number',
        'is_approved',
        'is_locked',
        'google_id', 
        'avatar_path',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_approved' => 'boolean',
    ];

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ClientResetPasswordNotification($token));
    }

    public function salesInvoices(): HasMany
    {
        return $this->hasMany(SalesInvoice::class, 'client_id', 'client_id');
    }

    public function orders(): HasMany 
    {
        return $this->hasMany(Order::class, 'client_id', 'client_id');
    }

    public function clientOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'client_id', 'client_id')->where('order_source', 'client');
    }

    public function announcements(): BelongsToMany
    {
        return $this->belongsToMany(Announcement::class, 'announcement_client', 'client_id', 'announcement_id');
    }

    public function ledgers(): HasMany
    {
        return $this->hasMany(ClientLedger::class, 'client_id', 'client_id');
    }

    public function getBalanceAttribute(): float
    {
        return $this->ledgers()->where('status', 'available')->sum('amount');
    }

    public function getPendingBalanceAttribute(): float
    {
        return $this->ledgers()
                    ->where('status', 'pending')
                    ->where('type', 'credit')
                    ->sum('amount');
    }

    public function getAvatarUrlAttribute()
    {
        if ($this->avatar_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($this->avatar_path)) {
            return asset('storage/' . $this->avatar_path);
        }
        $name = urlencode($this->client_name);
        return "https://ui-avatars.com/api/?name={$name}&color=7F9CF5&background=EBF4FF";
    }
}