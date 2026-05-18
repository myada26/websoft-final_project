<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\College;
use App\Models\Program;
use App\Models\Department;
use Illuminate\Http\Request;

class ProgramController extends Controller
{
    public function index()
    {
        $programs = Program::with('department.college')
            ->when(request('search'), fn($q, $s) => $q->where(function($query) use ($s) {
                $query->where('name', 'like', "%$s%")
                      ->orWhere('code', 'like', "%$s%");
            }))
            ->when(request('filter_college'), fn($q, $c) => $q->whereHas('department', fn($dq) => $dq->where('college_id', $c)))
            ->when(request('filter_dept'),    fn($q, $d) => $q->where('department_id', $d))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $departments    = Department::orderBy('name')->get();
        $colleges       = College::orderBy('name')->get();
        $allDepartments = Department::orderBy('name')->get(['id', 'name', 'college_id']);

        return view('admin.programs.index', compact('programs', 'departments', 'colleges', 'allDepartments'));
    }

    public function create()
    {
        return view('admin.programs.create', ['departments' => Department::with('college')->orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code'          => 'required|string|max:20|unique:programs,code',
            'name'          => 'required|string|max:255',
            'department_id' => 'required|exists:departments,id',
        ]);
        Program::create($data);
        return redirect()->route('admin.programs.index')->with('success', 'Program created.');
    }

    public function edit(Program $program)
    {
        return view('admin.programs.edit', [
            'program'     => $program,
            'departments' => Department::with('college')->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Program $program)
    {
        $data = $request->validate([
            'code'          => 'required|string|max:20|unique:programs,code,'.$program->id,
            'name'          => 'required|string|max:255',
            'department_id' => 'required|exists:departments,id',
        ]);
        $program->update($data);
        return redirect()->route('admin.programs.index')->with('success', 'Program updated.');
    }

    public function destroy(Program $program)
    {
        try {
            $program->delete();
        } catch (\Throwable) {
            return redirect()->route('admin.programs.index')
                ->with('error', 'Program cannot be deleted because it is linked to enrollments or other records.');
        }

        return redirect()->route('admin.programs.index')->with('success', 'Program deleted.');
    }
}
