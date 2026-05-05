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
            'type'          => 'required|in:SSC,COLLEGE_COUNCIL,DEPT_SOCIETY',
            'college_id'    => 'nullable|exists:colleges,id',
            'department_id' => 'nullable|exists:departments,id',
        ]);
        Organization::create($data);
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
            'type'          => 'required|in:SSC,COLLEGE_COUNCIL,DEPT_SOCIETY',
            'college_id'    => 'nullable|exists:colleges,id',
            'department_id' => 'nullable|exists:departments,id',
        ]);
        $organization->update($data);
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
