<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FeeProfile;
use App\Models\Organization;
use Illuminate\Http\Request;

class FeeProfileController extends Controller
{
    public function index()
    {
        $feeProfiles = FeeProfile::with('organization')
            ->when(request('organization_id'), fn($q, $org) => $q->where('organization_id', $org))
            ->when(request('category'), fn($q, $cat) => $q->where('category', $cat))
            ->when(request('status') === 'active', fn($q) => $q->where('is_active', true))
            ->when(request('status') === 'inactive', fn($q) => $q->where('is_active', false))
            ->orderBy('name')
            ->paginate(20);

        $organizations = Organization::orderBy('name')->get();

        return view('admin.fee-profiles.index', compact('feeProfiles', 'organizations'));
    }

    public function create()
    {
        $organizations = Organization::orderBy('name')->get();
        return view('admin.fee-profiles.create', compact('organizations'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'organization_id' => 'required|exists:organizations,id',
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'category' => 'required|in:REGULAR,IRREGULAR,EXTENDEE,EXEMPTED',
            'is_active' => 'nullable|boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        FeeProfile::create($data);

        return redirect()->route('admin.fee-profiles.index')->with('success', 'Fee profile created.');
    }

    public function edit(FeeProfile $feeProfile)
    {
        $organizations = Organization::orderBy('name')->get();
        return view('admin.fee-profiles.edit', compact('feeProfile', 'organizations'));
    }

    public function update(Request $request, FeeProfile $feeProfile)
    {
        $data = $request->validate([
            'organization_id' => 'required|exists:organizations,id',
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'category' => 'required|in:REGULAR,IRREGULAR,EXTENDEE,EXEMPTED',
            'is_active' => 'nullable|boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        $feeProfile->update($data);

        return redirect()->route('admin.fee-profiles.index')->with('success', 'Fee profile updated.');
    }

    public function destroy(FeeProfile $feeProfile)
    {
        try {
            $feeProfile->delete();
        } catch (\Throwable) {
            return redirect()->route('admin.fee-profiles.index')
                ->with('error', 'Fee profile cannot be deleted because it is linked to transactions.');
        }

        return redirect()->route('admin.fee-profiles.index')->with('success', 'Fee profile deleted.');
    }
}