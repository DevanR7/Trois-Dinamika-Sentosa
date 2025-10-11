<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $primaryKey = 'key'; // Gunakan 'key' sebagai primary key
    public $incrementing = false; // Primary key bukan auto-increment
    protected $keyType = 'string'; // Tipe data primary key adalah string

    protected $fillable = ['key', 'value'];
}