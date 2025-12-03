<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    /**
     * Konstruktor: menerapkan middleware otorisasi untuk seluruh action.
     */
    public function __construct()
    {
        $this->middleware('can:manage-roles');
    }

    /**
     * Menampilkan daftar role dengan pagination.
     */
    public function index(): View
    {
        $roles = Role::with('permissions')->paginate(10);
        return view('admin.roles.index', compact('roles'));
    }

    /**
     * Menampilkan form untuk membuat role baru.
     */
    public function create(): View
    {
        $permissions = Permission::all()->groupBy(function ($permission) {
            // Kelompokkan permission berdasarkan prefix (misal: 'manage-clients' → 'manage')
            return explode('-', $permission->name)[0];
        });

        return view('admin.roles.create', compact('permissions'));
    }

    /**
     * Menyimpan role baru ke database.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:roles,name',
            'permissions' => 'nullable|array',
        ]);

        $role = Role::create(['name' => $validated['name']]);

        if (!empty($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        return redirect()->route('admin.roles.index')->with('success', 'Role baru berhasil dibuat.');
    }

    /**
     * Menampilkan form edit untuk role yang ada.
     */
    public function edit(Role $role): View
    {
        $permissions = Permission::all()->groupBy(function ($permission) {
            return explode('-', $permission->name)[0];
        });

        $roleHasPermissions = $role->permissions->pluck('name')->all();

        return view('admin.roles.edit', compact('role', 'permissions', 'roleHasPermissions'));
    }

    /**
     * Memperbarui data role yang ada.
     */
    public function update(Request $request, Role $role): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', Rule::unique('roles')->ignore($role->id)],
            'permissions' => 'nullable|array',
        ]);

        $role->update(['name' => $validated['name']]);
        $role->syncPermissions($validated['permissions'] ?? []);

        return redirect()->route('admin.roles.index')->with('success', 'Role berhasil diperbarui.');
    }

    /**
     * Menghapus role dari sistem dengan validasi keamanan.
     */
    public function destroy(Role $role): RedirectResponse
    {
        // Mencegah penghapusan role admin
        if ($role->name === 'admin') {
            return back()->with('error', 'Role Admin tidak boleh dihapus.');
        }

        // Mencegah penghapusan role yang masih digunakan
        $userCount = $role->users()->count();
        if ($userCount > 0) {
            return back()->with('error', 'Role "' . $role->name . '" tidak bisa dihapus karena masih digunakan oleh ' . $userCount . ' user.');
        }

        $role->delete();

        return redirect()->route('admin.roles.index')->with('success', 'Role berhasil dihapus.');
    }
}