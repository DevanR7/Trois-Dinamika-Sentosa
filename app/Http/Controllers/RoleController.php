<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    public function index(): View
    {
        $roles = Role::with('permissions')->paginate(10);
        return view('roles.index', compact('roles'));
    }

    public function create(): View
    {
        $permissions = Permission::all()->groupBy(function($permission) {
            return explode('-', $permission->name)[0]; // Mengelompokkan permission, misal: 'manage-clients' -> 'manage'
        });
        return view('roles.create', compact('permissions'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:roles,name',
            'permissions' => 'nullable|array'
        ]);

        $role = Role::create(['name' => $validated['name']]);
        if (!empty($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        return redirect()->route('roles.index')->with('success', 'Role baru berhasil dibuat.');
    }

    public function edit(Role $role): View
    {
        $permissions = Permission::all()->groupBy(function($permission) {
            return explode('-', $permission->name)[0];
        });
        $roleHasPermissions = $role->permissions->pluck('name')->all();

        return view('roles.edit', compact('role', 'permissions', 'roleHasPermissions'));
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', Rule::unique('roles')->ignore($role->id)],
            'permissions' => 'nullable|array'
        ]);

        $role->update(['name' => $validated['name']]);
        $role->syncPermissions($validated['permissions'] ?? []);

        return redirect()->route('roles.index')->with('success', 'Role berhasil diperbarui.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        // Mencegah role super admin terhapus
        if ($role->name === 'admin') {
            return back()->with('error', 'Role Admin tidak boleh dihapus.');
        }

        if ($role->users()->count() > 0) {
        return back()->with('error', 'Role "' . $role->name . '" tidak bisa dihapus karena masih digunakan oleh ' . $role->users()->count() . ' user.');
    }

        $role->delete();
        return redirect()->route('roles.index')->with('success', 'Role berhasil dihapus.');
    }
}