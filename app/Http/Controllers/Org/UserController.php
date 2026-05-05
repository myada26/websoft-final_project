<?php

namespace App\Http\Controllers\Org;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        $users = User::where('organization_id', auth()->user()->organization_id)
            ->when(request('search'), fn($q, $s) => $q->where('username', 'like', "%$s%"))
            ->orderBy('username')
            ->paginate(20);

        return view('org.users.index', compact('users'));
    }

    public function create()
    {
        return view('org.users.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'username'   => 'required|string|max:100|unique:users,username',
            'password'   => 'required|string|min:8',
            'role'       => 'required|in:CHAIRPERSON,TREASURER,COLLECTOR,AUDITOR',
        ]);

        $data['password_hash'] = Hash::make($data['password']);
        $data['organization_id'] = auth()->user()->organization_id;
        $data['is_active'] = true;
        
        unset($data['password']);

        $user = User::create($data);
        $this->syncRolePermissions($user);

        return redirect()->route('org.users.index')->with('success', 'User invited successfully.');
    }

    public function edit(User $user)
    {
        if ($user->organization_id !== auth()->user()->organization_id) {
            abort(403);
        }

        return view('org.users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        if ($user->organization_id !== auth()->user()->organization_id) {
            abort(403);
        }

        $data = $request->validate([
            'username'   => 'required|string|max:100|unique:users,username,'.$user->id,
            'password'   => 'nullable|string|min:8',
            'role'       => 'required|in:CHAIRPERSON,TREASURER,COLLECTOR,AUDITOR',
            'is_active'  => 'nullable|boolean',
        ]);

        if (!empty($data['password'])) {
            $data['password_hash'] = Hash::make($data['password']);
        }
        $data['is_active'] = $request->boolean('is_active', true);
        unset($data['password']);

        $user->update($data);
        $this->syncRolePermissions($user);

        return redirect()->route('org.users.index')->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        if ($user->organization_id !== auth()->user()->organization_id) {
            abort(403);
        }

        if (auth()->id() === $user->id) {
            return redirect()->route('org.users.index')
                ->with('error', 'You cannot remove your own account while signed in.');
        }

        try {
            $user->delete();
        } catch (\Throwable) {
            return redirect()->route('org.users.index')
                ->with('error', 'User cannot be removed because it is linked to transactions, approvals, or audit logs.');
        }

        return redirect()->route('org.users.index')->with('success', 'User removed.');
    }

    private function syncRolePermissions(User $user): void
    {
        $rolePermissions = [
            'CHAIRPERSON' => ['students:view', 'students:enroll', 'users:manage', 'void:approve', 'audit:view'],
            'TREASURER' => ['students:view', 'transactions:view', 'pos:create', 'void:request', 'remit:view', 'remit:create'],
            'COLLECTOR' => ['students:view', 'pos:create', 'void:request'],
            'AUDITOR' => ['transactions:view', 'remit:view', 'remit:verify', 'remit:accept', 'void:review', 'audit:view'],
        ];

        $permissionIds = DB::table('permissions')
            ->whereIn('slug', $rolePermissions[$user->role] ?? [])
            ->pluck('id');

        $user->permissions()->sync($permissionIds->mapWithKeys(fn ($id) => [$id => ['granted_at' => now()]])->all());
    }
}
