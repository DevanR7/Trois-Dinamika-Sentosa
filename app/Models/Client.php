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

// 1. TAMBAHKAN DUA 'USE' INI
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Auth\Passwords\CanResetPassword as CanResetPasswordTrait;

/**
 * App\Models\Client
 *
 * @property int $client_id
 * @property string|null $google_id
 * @property string $client_name
 * @property string|null $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property mixed|null $password
 * @property bool $is_approved
 * @property int $is_locked
 * @property string|null $remember_token
 * @property string|null $person_in_charge
 * @property string|null $address
 * @property string|null $phone_number
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Announcement> $announcements
 * @property-read int|null $announcements_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Order> $clientOrders
 * @property-read int|null $client_orders_count
 * @property-read float $balance
 * @property-read float $pending_balance
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ClientLedger> $ledgers
 * @property-read int|null $ledgers_count
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Order> $orders
 * @property-read int|null $orders_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SalesInvoice> $salesInvoices
 * @property-read int|null $sales_invoices_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Laravel\Sanctum\PersonalAccessToken> $tokens
 * @property-read int|null $tokens_count
 * @method static \Illuminate\Database\Eloquent\Builder|Client newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Client newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Client onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Client query()
 * @method static \Illuminate\Database\Eloquent\Builder|Client whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Client whereClientId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Client whereClientName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Client whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Client whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Client whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Client whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Client whereGoogleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Client whereIsApproved($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Client whereIsLocked($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Client wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Client wherePersonInCharge($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Client wherePhoneNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Client whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Client whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Client withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Client withoutTrashed()
 * @mixin \Eloquent
 */
class Client extends Authenticatable implements CanResetPassword
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, CanResetPasswordTrait;

    protected $primaryKey = 'client_id';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'client_name',
        'email',
        'password',
        'person_in_charge',
        'address',
        'phone_number',
        'is_approved',
        'is_locked',
        'google_id', // Make sure google_id is fillable if you set it during registration/login
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_approved' => 'boolean',
    ];

    /**
     * Mengirim notifikasi reset password kustom.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ClientResetPasswordNotification($token));
    }

    /**
     * Get all of the sales invoices for the Client.
     */
    public function salesInvoices(): HasMany
    {
        return $this->hasMany(SalesInvoice::class, 'client_id', 'client_id');
    }

    /**
     * Get all of the orders for the Client.
     * ✅ BERUBAH: Nama method dan model yang dirujuk.
     */
    public function orders(): HasMany // ✅ BERUBAH: Nama method
    {
        // ✅ BERUBAH: Model yang dirujuk
        return $this->hasMany(Order::class, 'client_id', 'client_id');
    }

    // You might also want a relationship for client-created orders specifically
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

    /**
     * Accessor untuk mendapatkan saldo kredit saat ini.
     * Panggil ini di view/controller: $client->balance
     *
     * @return float
     */
    public function getBalanceAttribute(): float
    {
        // ✅ DIUBAH: Hanya menjumlahkan yang statusnya 'available'
        return $this->ledgers()->where('status', 'available')->sum('amount');
    }

    /**
     * ✅ BARU: Accessor untuk mendapatkan saldo kredit yang DITAHAN (pending).
     *
     * @return float
     */
    public function getPendingBalanceAttribute(): float
    {
        // Hanya menjumlahkan kredit 'pending' (debit tidak pernah pending)
        return $this->ledgers()
                    ->where('status', 'pending')
                    ->where('type', 'credit')
                    ->sum('amount');
    }
}