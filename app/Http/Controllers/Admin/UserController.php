<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\TemporaryPasswordMail;
use App\Models\College;
use App\Models\Department;
use App\Models\Student;
use App\Models\User;
use App\Models\Organization;
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
        $users = User::with(['organization', 'student'])
            ->when(request('search'), fn($q, $s) => $q->where(function ($w) use ($s) {
                $w->where('username', 'like', "%$s%")
                    ->orWhereHas('student', fn($sq) => $sq
                        ->where('student_number', 'like', "%$s%")
                        ->orWhere('first_name', 'like', "%$s%")
                        ->orWhere('last_name', 'like', "%$s%"));
            }))
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
            'student_number'  => 'required|string|max:100|exists:students,student_number',
            'username'        => 'nullable|string|max:100|unique:users,username',
            'password'        => 'nullable|string|min:8',
            'role'            => 'required|in:SSC_ADMIN,CHAIRPERSON,TREASURER,COLLECTOR,AUDITOR,SECRETARY',
            'organization_id' => 'required|exists:organizations,id',
            'is_active'       => 'nullable|boolean',
        ]);

        $student = Student::where('student_number', $data['student_number'])->firstOrFail();
        $organization = Organization::findOrFail($data['organization_id']);

        if ($organization->type === 'UNIVERSITY_WIDE' && !in_array($data['role'], ['SSC_ADMIN', 'CHAIRPERSON'])) {
            return back()->with('error', 'Only SSC_ADMIN or CHAIRPERSON can be assigned to a University-Wide organization.');
        }

        if (User::where('student_id', $student->id)->where('organization_id', $organization->id)->exists()) {
            throw ValidationException::withMessages([
                'student_number' => 'This student already has an account in the selected organization.',
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
                'is_active' => $request->boolean('is_active', true),
            ]);

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
            ? 'User created. A temporary password was sent to the student email.'
            : 'User created with a temporary password. They must change it at first login.';

        return redirect()->route('admin.users.index')->with('success', $message);
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
            $data['requires_password_change'] = true;
        }
        $data['is_active'] = $request->boolean('is_active', true);
        unset($data['password']);
        $user->update($data);
        return redirect()->route('admin.users.index')->with('success', 'User updated.');
    }

    public function resetPassword(User $user)
    {
        $user->loadMissing(['student', 'organization']);

        if (! $user->student?->email) {
            return redirect()->route('admin.users.index')
                ->with('error', 'Password reset failed because the linked student has no email address.');
        }

        $temporaryPassword = Str::password(12);

        try {
            DB::transaction(function () use ($user, $temporaryPassword) {
                $user->update([
                    'password_hash' => Hash::make($temporaryPassword),
                    'requires_password_change' => true,
                    'failed_login_attempts' => 0,
                    'locked_until' => null,
                ]);

                Mail::to($user->student->email)->send(
                    new TemporaryPasswordMail($user->student, $user->organization, $user->username, $temporaryPassword)
                );
            });
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('admin.users.index')
                ->with('error', 'Password reset email could not be sent. The password was not changed.');
        }

        return redirect()->route('admin.users.index')
            ->with('success', 'Temporary password sent. The user must change it at next login.');
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

    private function generateUsername(Student $student, Organization $organization): string
    {
        $orgCode = $organization->type === 'UNIVERSITY_WIDE'
            ? 'SSC'
            : 'ORG' . str_pad((string) $organization->id, 3, '0', STR_PAD_LEFT);

        return Str::limit($student->student_number . '-' . $orgCode, 100, '');
    }
}
