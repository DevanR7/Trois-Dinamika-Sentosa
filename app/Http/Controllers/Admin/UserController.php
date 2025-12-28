<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
        $this->middleware('can:manage-users');
    }

    public function index(Request $request)
    {
        $query = User::query();

        if ($request->get('status') === 'deleted') {
            $query->onlyTrashed();
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->latest('user_id')->paginate(10);
        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        $roles = Role::all();
        return view('admin.users.create', compact('roles'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', 'string', 'exists:roles,name'],
            'sales_code' => ['nullable', 'string', 'max:10', 'unique:users,sales_code'],
            'nik' => ['nullable', 'string', 'max:20', Rule::unique('users')],
            'address' => ['nullable', 'string'],
            'phone_number' => ['nullable', 'string', 'max:20'],
        ]);

        $user = User::create([
            'full_name' => $request->full_name,
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'sales_code' => $request->sales_code,
            'is_approved' => true,
            'nik' => $request->nik,
            'address' => $request->address,
            'phone_number' => $request->phone_number,
        ]);

        $user->assignRole($request->role);

        return redirect()->route('admin.users.index')->with('success', 'User baru berhasil dibuat.');
    }

    public function edit(User $user)
    {
        $roles = Role::all();
        return view('admin.users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', Rule::unique('users')->ignore($user->user_id, 'user_id')],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->user_id, 'user_id')],
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', 'string', 'exists:roles,name'],
            'sales_code' => ['nullable', 'string', 'max:10', Rule::unique('users')->ignore($user->user_id, 'user_id')],
            'nik' => ['nullable', 'string', 'max:20', Rule::unique('users')->ignore($user->user_id, 'user_id')],
            'address' => ['nullable', 'string'],
            'phone_number' => ['nullable', 'string', 'max:20'],
        ]);

        $userData = $request->except('password', 'role');

        if ($request->filled('password')) {
            $userData['password'] = Hash::make($request->password);
        }

        $user->update($userData);
        $user->syncRoles($request->role);

        return redirect()->route('admin.users.index')->with('success', 'Data user berhasil diupdate.');
    }

    public function approve(User $user): RedirectResponse
    {
        if ($user->user_id === Auth::id()) {
            return back()->with('error', 'Anda tidak bisa menyetujui akun Anda sendiri.');
        }

        $user->update(['is_approved' => true]);

        return back()->with('success', 'Akun staf ' . $user->full_name . ' telah disetujui.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->user_id === Auth::id()) {
            return back()->with('error', 'Anda tidak bisa menghapus akun Anda sendiri.');
        }

        if ($user->hasRole(['admin', 'superadmin']) && User::role(['admin', 'superadmin'])->count() === 1) {
            return back()->with('error', 'Tidak bisa menghapus satu-satunya user dengan role Admin atau Superadmin.');
        }

        if ($user->salesInvoices()->exists()) {
            return back()->with('error', 'User ini tidak bisa dihapus karena memiliki data invoice penjualan.');
        }

        if ($user->purchaseOrders()->exists()) {
            return back()->with('error', 'User ini tidak bisa dihapus karena memiliki data pesanan pembelian.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'User berhasil dihapus.');
    }

    public function restore(User $user): RedirectResponse
    {
        if ($user->trashed()) {
            $user->restore();
            return back()->with('success', 'Akun user ' . $user->full_name . ' telah dipulihkan.');
        }

        return back()->with('error', 'User tidak terhapus.');
    }
}