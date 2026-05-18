<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\College;
use App\Models\Department;
use App\Models\User;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('organization')
            ->when(request('search'), fn($q, $s) => $q->where('username', 'like', "%$s%"))
            ->when(request('filter_college'), fn($q, $c) => $q->whereHas('organization', function ($oq) use ($c) {
                $oq->where(function ($w) use ($c) {
                    $w->where('linked_college_id', $c)
                      ->orWhereHas('department', fn($dq) => $dq->where('college_id', $c));
                });
            }))
            ->when(request('filter_dept'), fn($q, $d) => $q->whereHas('organization', fn($oq) => $oq->where('linked_department_id', $d)))
            ->orderBy('username')
            ->paginate(20)
            ->withQueryString();

        $organizations  = Organization::orderBy('name')->get();
        $colleges       = College::orderBy('name')->get();
        $allDepartments = Department::orderBy('name')->get(['id', 'name', 'college_id']);

        return view('admin.users.index', compact('users', 'organizations', 'colleges', 'allDepartments'));
    }

    public function create()
    {
        return view('admin.users.create', [
            'organizations' => Organization::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'username'        => 'required|string|max:100|unique:users,username',
            'password'        => 'required|string|min:8',
            'role'            => 'required|in:SSC_ADMIN,CHAIRPERSON,TREASURER,COLLECTOR,AUDITOR,SECRETARY',
            'organization_id' => 'required|exists:organizations,id',
            'is_active'       => 'nullable|boolean',
        ]);

        $organization = Organization::find($data['organization_id']);
        if ($organization->type === 'UNIVERSITY_WIDE' && !in_array($data['role'], ['SSC_ADMIN', 'CHAIRPERSON'])) {
            return back()->with('error', 'Only SSC_ADMIN or CHAIRPERSON can be assigned to a University-Wide organization.');
        }

        $data['password_hash'] = Hash::make($data['password']);
        $data['is_active']     = $request->boolean('is_active', true);
        unset($data['password']);
        User::create($data);
        return redirect()->route('admin.users.index')->with('success', 'User created.');
    }

    public function edit(User $user)
    {
        return view('admin.users.edit', [
            'user'          => $user,
            'organizations' => Organization::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'username'        => 'required|string|max:100|unique:users,username,'.$user->id,
            'role'            => 'required|in:SSC_ADMIN,CHAIRPERSON,TREASURER,COLLECTOR,AUDITOR,SECRETARY',
            'organization_id' => 'required|exists:organizations,id',
            'is_active'       => 'nullable|boolean',
            'password'        => 'nullable|string|min:8',
        ]);

        $organization = Organization::find($data['organization_id']);
        if ($organization->type === 'UNIVERSITY_WIDE' && !in_array($data['role'], ['SSC_ADMIN', 'CHAIRPERSON'])) {
            return back()->with('error', 'Only SSC_ADMIN or CHAIRPERSON can be assigned to a University-Wide organization.');
        }

        if (!empty($data['password'])) {
            $data['password_hash'] = Hash::make($data['password']);
        }
        $data['is_active'] = $request->boolean('is_active', true);
        unset($data['password']);
        $user->update($data);
        return redirect()->route('admin.users.index')->with('success', 'User updated.');
    }

    public function destroy(User $user)
    {
        if (auth()->user()->id === $user->id) {
            return redirect()->route('admin.users.index')
                ->with('error', 'You cannot delete your own account while signed in.');
        }

        try {
            $user->delete();
        } catch (\Throwable) {
            return redirect()->route('admin.users.index')
                ->with('error', 'User cannot be deleted because it is linked to transactions, approvals, or audit logs.');
        }

        return redirect()->route('admin.users.index')->with('success', 'User deleted.');
    }
}
