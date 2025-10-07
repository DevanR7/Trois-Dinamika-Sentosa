<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class CreateAdminUser extends Command
{
    /**
     * Nama dan signature dari command.
     * Inilah yang akan Anda ketik di terminal.
     */
    protected $signature = 'app:create-admin';

    /**
     * Deskripsi dari command.
     */
    protected $description = 'Membuat user baru dengan role sebagai Admin';

    /**
     * Jalankan logic command.
     */
    public function handle()
    {
        $this->info('Memulai proses pembuatan user admin baru...');

        // 1. Minta input dari pengguna
        $fullName = $this->ask('Masukkan Nama Lengkap');
        $username = $this->ask('Masukkan Username');
        $email = $this->ask('Masukkan Email');
        $password = $this->secret('Masukkan Password (minimal 8 karakter)');

        // 2. Validasi input
        $validator = Validator::make([
            'full_name' => $fullName,
            'username' => $username,
            'email' => $email,
            'password' => $password,
        ], [
            'full_name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', Password::defaults()],
        ]);

        if ($validator->fails()) {
            $this->error('Validasi Gagal!');
            foreach ($validator->errors()->all() as $error) {
                $this->line('- ' . $error);
            }
            return 1; // Keluar dengan status error
        }

        // 3. Buat user baru
        $user = User::create([
            'full_name' => $fullName,
            'username' => $username,
            'email' => $email,
            'password' => Hash::make($password),
        ]);

        // 4. Berikan role 'admin'
        // firstOrCreate akan membuat role 'admin' jika belum ada
        Role::firstOrCreate(['name' => 'admin']);
        $user->assignRole('admin');

        // 5. Beri pesan sukses
        $this->info('User admin berhasil dibuat!');
        $this->table(
            ['Field', 'Value'],
            [
                ['Nama Lengkap', $user->full_name],
                ['Username', $user->username],
                ['Email', $user->email],
                ['Role', $user->getRoleNames()->first()],
            ]
        );

        return 0; // Selesai dengan status sukses
    }
}