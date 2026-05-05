<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\College;
use Illuminate\Http\Request;

class CollegeController extends Controller
{
    public function index()
    {
        $colleges = College::withCount('departments')
            ->when(request('search'), fn($q, $s) => $q->where('name','like',"%$s%")->orWhere('code','like',"%$s%"))
            ->when(request('status') === 'active',   fn($q) => $q->where('is_active', true))
            ->when(request('status') === 'inactive', fn($q) => $q->where('is_active', false))
            ->orderBy('name')
            ->paginate(20);

        return view('admin.colleges.index', compact('colleges'));
    }

    public function create()
    {
        return view('admin.colleges.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code'      => 'required|string|max:20|unique:colleges,code',
            'name'      => 'required|string|max:255',
            'is_active' => 'nullable|boolean',
        ]);
        $data['is_active'] = $request->boolean('is_active', true);
        College::create($data);
        return redirect()->route('admin.colleges.index')->with('success', 'College created successfully.');
    }

    public function edit(College $college)
    {
        return view('admin.colleges.edit', compact('college'));
    }

    public function update(Request $request, College $college)
    {
        $data = $request->validate([
            'code'      => 'required|string|max:20|unique:colleges,code,'.$college->id,
            'name'      => 'required|string|max:255',
            'is_active' => 'nullable|boolean',
        ]);
        $data['is_active'] = $request->boolean('is_active', true);
        $college->update($data);
        return redirect()->route('admin.colleges.index')->with('success', 'College updated.');
    }

    public function destroy(College $college)
    {
        try {
            $college->delete();
        } catch (\Throwable) {
            return redirect()->route('admin.colleges.index')
                ->with('error', 'College cannot be deleted because it is linked to departments or organizations.');
        }

        return redirect()->route('admin.colleges.index')->with('success', 'College deleted.');
    }
}
