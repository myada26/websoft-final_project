<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\College;
use App\Models\Department;
use Illuminate\Http\Request;

class OrganizationController extends Controller
{
    public function index()
    {
        $organizations = Organization::with(['college','department'])
            ->when(request('search'), fn($q, $s) => $q->where('name', 'like', "%$s%"))
            ->when(request('type'), fn($q, $t) => $q->where('type', $t))
            ->orderBy('name')
            ->paginate(20);

        $colleges = College::orderBy('name')->get();
        $departments = Department::orderBy('name')->get();

        return view('admin.organizations.index', compact('organizations', 'colleges', 'departments'));
    }

    public function create()
    {
        return view('admin.organizations.create', [
            'colleges'    => College::orderBy('name')->get(),
            'departments' => Department::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'          => 'required|string|max:255',
            'type'          => 'required|in:UNIVERSITY_WIDE,COLLEGE_COUNCIL,CLASS_ORG,RESERVED',
            'college_id'    => 'nullable|exists:colleges,id',
            'department_id' => 'nullable|exists:departments,id',
        ]);

        if ($data['type'] === 'COLLEGE_COUNCIL' && empty($data['college_id'])) {
            return back()->withErrors(['college_id' => 'A College Council must have a linked college.'])->withInput();
        }

        if ($data['type'] === 'CLASS_ORG' && empty($data['department_id'])) {
            return back()->withErrors(['department_id' => 'A Class Organization must have a linked department.'])->withInput();
        }

        Organization::create([
            'name'                 => $data['name'],
            'type'                 => $data['type'],
            'linked_college_id'    => $data['type'] === 'COLLEGE_COUNCIL' ? ($data['college_id'] ?? null) : null,
            'linked_department_id' => $data['type'] === 'CLASS_ORG' ? ($data['department_id'] ?? null) : null,
        ]);

        return redirect()->route('admin.organizations.index')->with('success', 'Organization created.');
    }

    public function edit(Organization $organization)
    {
        return view('admin.organizations.edit', [
            'organization' => $organization,
            'colleges'     => College::orderBy('name')->get(),
            'departments'  => Department::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Organization $organization)
    {
        $data = $request->validate([
            'name'          => 'required|string|max:255',
            'type'          => 'required|in:UNIVERSITY_WIDE,COLLEGE_COUNCIL,CLASS_ORG,RESERVED',
            'college_id'    => 'nullable|exists:colleges,id',
            'department_id' => 'nullable|exists:departments,id',
        ]);

        if ($data['type'] === 'COLLEGE_COUNCIL' && empty($data['college_id'])) {
            return back()->withErrors(['college_id' => 'A College Council must have a linked college.'])->withInput();
        }

        if ($data['type'] === 'CLASS_ORG' && empty($data['department_id'])) {
            return back()->withErrors(['department_id' => 'A Class Organization must have a linked department.'])->withInput();
        }

        $organization->update([
            'name'                 => $data['name'],
            'type'                 => $data['type'],
            'linked_college_id'    => $data['type'] === 'COLLEGE_COUNCIL' ? ($data['college_id'] ?? null) : null,
            'linked_department_id' => $data['type'] === 'CLASS_ORG' ? ($data['department_id'] ?? null) : null,
        ]);

        return redirect()->route('admin.organizations.index')->with('success', 'Organization updated.');
    }

    public function destroy(Organization $organization)
    {
        try {
            $organization->delete();
        } catch (\Throwable) {
            return redirect()->route('admin.organizations.index')
                ->with('error', 'Organization cannot be deleted because it has users, fees, transactions, or related records.');
        }

        return redirect()->route('admin.organizations.index')->with('success', 'Organization deleted.');
    }
}
