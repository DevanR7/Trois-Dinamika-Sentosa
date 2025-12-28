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
    public function __construct()
    {
        $this->middleware('can:manage-roles');
    }

    public function index(): View
    {
        $roles = Role::with('permissions')->paginate(10);
        return view('admin.roles.index', compact('roles'));
    }

    public function create(): View
    {
        $permissions = Permission::all()->groupBy(function ($permission) {
            return explode('-', $permission->name)[0];
        });

        return view('admin.roles.create', compact('permissions'));
    }

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

    public function edit(Role $role): View
    {
        $permissions = Permission::all()->groupBy(function ($permission) {
            return explode('-', $permission->name)[0];
        });

        $roleHasPermissions = $role->permissions->pluck('name')->all();

        return view('admin.roles.edit', compact('role', 'permissions', 'roleHasPermissions'));
    }

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

    public function destroy(Role $role): RedirectResponse
    {
        if ($role->name === 'admin') {
            return back()->with('error', 'Role Admin tidak boleh dihapus.');
        }

        $userCount = $role->users()->count();
        if ($userCount > 0) {
            return back()->with('error', 'Role "' . $role->name . '" tidak bisa dihapus karena masih digunakan oleh ' . $userCount . ' user.');
        }

        $role->delete();

        return redirect()->route('admin.roles.index')->with('success', 'Role berhasil dihapus.');
    }
}