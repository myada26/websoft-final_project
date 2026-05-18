<?php

namespace App\Http\Controllers\Org;

use App\Http\Controllers\Controller;
use App\Mail\TemporaryPasswordMail;
use App\Models\Organization;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('student')
            ->where('organization_id', auth()->user()->organization_id)
            ->when(request('search'), fn($q, $s) => $q->where(function ($w) use ($s) {
                $w->where('username', 'like', "%$s%")
                    ->orWhereHas('student', fn($sq) => $sq
                        ->where('student_number', 'like', "%$s%")
                        ->orWhere('first_name', 'like', "%$s%")
                        ->orWhere('last_name', 'like', "%$s%"));
            }))
            ->orderBy('username')
            ->paginate(20);

        return view('org.users.index', compact('users'));
    }

    public function create()
    {
        return view('org.users.create');
    }

    public function lookupStudent(Request $request)
    {
        $data = $request->validate([
            'student_number' => ['required', 'string', 'max:100'],
        ]);

        $student = Student::where('student_number', $data['student_number'])->first();

        if (! $student) {
            return response()->json(['message' => 'Student not found.'], 404);
        }

        return response()->json([
            'student_number' => $student->student_number,
            'first_name' => $student->first_name,
            'last_name' => $student->last_name,
            'middle_name' => $student->middle_name,
            'full_name' => $student->full_name,
            'email' => $student->email,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'student_number' => 'required|string|max:100|exists:students,student_number',
            'username'   => 'nullable|string|max:100|unique:users,username',
            'password'   => 'nullable|string|min:8',
            'role'       => 'required|in:CHAIRPERSON,TREASURER,COLLECTOR,AUDITOR,SECRETARY',
        ]);

        $student = Student::where('student_number', $data['student_number'])->firstOrFail();
        $organization = auth()->user()->organization;

        if (User::where('student_id', $student->id)->where('organization_id', $organization->id)->exists()) {
            throw ValidationException::withMessages([
                'student_number' => 'This student already has an account in your organization.',
            ]);
        }

        $username = $data['username'] ?: $this->generateUsername($student, $organization);
        if (User::where('username', $username)->exists()) {
            throw ValidationException::withMessages([
                'username' => 'The generated username is already in use. Please enter a unique username.',
            ]);
        }

        $passwordWasGenerated = blank($data['password'] ?? null);
        if ($passwordWasGenerated && blank($student->email)) {
            throw ValidationException::withMessages([
                'password' => 'Enter a temporary password manually or add an email address to the student profile so FCATS can send a generated one.',
            ]);
        }

        $temporaryPassword = $data['password'] ?: Str::password(12);
        $user = null;

        try {
            $user = User::create([
                'student_id' => $student->id,
                'organization_id' => $organization->id,
                'username' => $username,
                'password_hash' => Hash::make($temporaryPassword),
                'requires_password_change' => true,
                'role' => $data['role'],
                'is_active' => true,
            ]);

            $this->syncRolePermissions($user);

            if ($passwordWasGenerated) {
                Mail::to($student->email)->send(
                    new TemporaryPasswordMail($student, $organization, $username, $temporaryPassword)
                );
            }
        } catch (\Throwable $e) {
            $user?->delete();
            report($e);

            return back()
                ->withInput($request->except('password'))
                ->with('error', 'User could not be created. Please verify mail settings and try again.');
        }

        $message = $passwordWasGenerated
            ? 'User invited. A temporary password was sent to the student email.'
            : 'User invited with a temporary password. They must change it at first login.';

        return redirect()->route('org.users.index')->with('success', $message);
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
            'role'       => 'required|in:CHAIRPERSON,TREASURER,COLLECTOR,AUDITOR,SECRETARY',
            'is_active'  => 'nullable|boolean',
        ]);

        if (!empty($data['password'])) {
            $data['password_hash'] = Hash::make($data['password']);
            $data['requires_password_change'] = true;
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

        if (auth()->user()->id === $user->id) {
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
            'CHAIRPERSON' => ['students:view', 'students:enroll', 'users:manage', 'void:approve', 'audit:view', 'attendance:view', 'event:create', 'event:approve'],
            'TREASURER'   => ['students:view', 'transactions:view', 'pos:create', 'void:request', 'remit:view', 'remit:create'],
            'COLLECTOR'   => ['students:view', 'pos:create', 'void:request'],
            'AUDITOR'     => ['transactions:view', 'remit:view', 'remit:verify', 'remit:accept', 'void:review', 'audit:view', 'attendance:view', 'event:approve'],
            'SECRETARY'   => ['attendance:record', 'attendance:view'],
        ];

        $permissionIds = DB::table('permissions')
            ->whereIn('slug', $rolePermissions[$user->role] ?? [])
            ->pluck('id');

        $user->permissions()->sync($permissionIds->mapWithKeys(fn ($id) => [$id => ['granted_at' => now()]])->all());
    }

    private function generateUsername(Student $student, Organization $organization): string
    {
        return Str::limit(
            $student->student_number . '-ORG' . str_pad((string) $organization->id, 3, '0', STR_PAD_LEFT),
            100,
            ''
        );
    }
}
