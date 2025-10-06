<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Spatie\Permission\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{

    public function __construct()
    {
        // Terapkan Gate 'view-user-management' ke semua method di controller ini
        $this->middleware('can:view-user-management');
    }
    public function index()
    {
        $users = User::latest()->paginate(10);
        return view('users.index', compact('users'));
    }

    public function create()
    {
         $roles = Role::all(); // <-- TAMBAHKAN INI: Ambil semua role
        return view('users.create', compact('roles')); // <-- TAMBAHKAN 'roles'
    }

    public function store(Request $request): RedirectResponse
{
    $request->validate([
        'full_name' => ['required', 'string', 'max:255'],
        'username' => ['required', 'string', 'max:255', 'unique:'.User::class],
        'email' => ['required', 'string', 'email', 'max:255', 'unique:'.User::class],
        'password' => ['required', 'confirmed', Rules\Password::defaults()],
        'role' => ['required', 'string', 'exists:roles,name'], // Validasi baru
        'sales_code' => ['nullable', 'string', 'max:10', 'unique:users,sales_code'],
    ]);

    $user = User::create([
        'full_name' => $request->full_name,
        'username' => $request->username,
        'email' => $request->email,
        'password' => Hash::make($request->password),
        'sales_code' => $request->sales_code,
    ]);

    // Gunakan method dari Spatie untuk memberikan role
    $user->assignRole($request->role);

    return redirect()->route('users.index')->with('success', 'User baru berhasil dibuat.');
}

    public function edit(User $user)
    {
         $roles = Role::all(); // <-- TAMBAHKAN INI: Ambil semua role
        return view('users.edit', compact('user', 'roles')); // <-- TAMBAHKAN 'roles'
    }

    public function update(Request $request, User $user): RedirectResponse
{
    $request->validate([
        'full_name' => ['required', 'string', 'max:255'],
        'username' => ['required', 'string', 'max:255', Rule::unique('users')->ignore($user->user_id, 'user_id')],
        'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->user_id, 'user_id')],
        'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
        'role' => ['required', 'string', 'exists:roles,name'], // Validasi baru
        'sales_code' => ['nullable', 'string', 'max:10', Rule::unique('users')->ignore($user->user_id, 'user_id')],
    ]);

    $userData = $request->except('password', 'role');

    if ($request->filled('password')) {
        $userData['password'] = Hash::make($request->password);
    }

    $user->update($userData);

    // Gunakan syncRoles untuk update. Ini akan menghapus role lama dan memberi role baru.
    $user->syncRoles($request->role);

    return redirect()->route('users.index')->with('success', 'Data user berhasil diupdate.');
}
    public function destroy(User $user): RedirectResponse
{
    // [SANGAT PENTING] Cek agar tidak menghapus user yang sedang login
    if ($user->user_id === Auth::id()) {
        return back()->with('error', 'Anda tidak bisa menghapus akun Anda sendiri.');
    }

    // Cek agar tidak menghapus user admin terakhir
    if ($user->hasRole('admin') && User::role('admin')->count() === 1) {
        return back()->with('error', 'Tidak bisa menghapus satu-satunya user dengan role Admin.');
    }

    // Cek relasi ke sales invoice
    if ($user->salesInvoices()->exists()) {
        return back()->with('error', 'User ini tidak bisa dihapus karena memiliki data invoice penjualan.');
    }

    // Cek relasi ke purchase order
    if ($user->purchaseOrders()->exists()) {
        return back()->with('error', 'User ini tidak bisa dihapus karena memiliki data pesanan pembelian.');
    }

    $user->delete();
    
    return redirect()->route('users.index')->with('success', 'User berhasil dihapus.');
}
}