<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\College;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index()
    {
        $departments = Department::with('college')
            ->when(request('search'), fn($q, $s) => $q->where(function($query) use ($s) {
                $query->where('name', 'like', "%$s%")
                      ->orWhere('code', 'like', "%$s%");
            }))
            ->when(request('college_id'), fn($q, $c) => $q->where('college_id', $c))
            ->orderBy('name')
            ->paginate(20);

        $colleges = College::orderBy('name')->get();

        return view('admin.departments.index', compact('departments', 'colleges'));
    }

    public function create()
    {
        return view('admin.departments.create', ['colleges' => College::orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code'       => 'required|string|max:20|unique:departments,code',
            'name'       => 'required|string|max:255',
            'college_id' => 'required|exists:colleges,id',
            'is_active'  => 'nullable|boolean',
        ]);
        $data['is_active'] = $request->boolean('is_active', true);
        Department::create($data);
        return redirect()->route('admin.departments.index')->with('success', 'Department created.');
    }

    public function edit(Department $department)
    {
        return view('admin.departments.edit', [
            'department' => $department,
            'colleges'   => College::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Department $department)
    {
        $data = $request->validate([
            'code'       => 'required|string|max:20|unique:departments,code,'.$department->id,
            'name'       => 'required|string|max:255',
            'college_id' => 'required|exists:colleges,id',
            'is_active'  => 'nullable|boolean',
        ]);
        $data['is_active'] = $request->boolean('is_active', true);
        $department->update($data);
        return redirect()->route('admin.departments.index')->with('success', 'Department updated.');
    }

    public function destroy(Department $department)
    {
        try {
            $department->delete();
        } catch (\Throwable) {
            return redirect()->route('admin.departments.index')
                ->with('error', 'Department cannot be deleted because it is linked to programs or organizations.');
        }

        return redirect()->route('admin.departments.index')->with('success', 'Department deleted.');
    }
}
