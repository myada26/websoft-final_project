<?php

namespace App\Http\Controllers\Org;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\FeeProfile;
use Illuminate\Http\Request;

class FeeProfileController extends Controller
{
    public function index()
    {
        $feeProfiles = FeeProfile::where('organization_id', auth()->user()->organization_id)
            ->orderBy('name')
            ->paginate(15);
        $semesters = AcademicYear::orderByDesc('is_active')->orderByDesc('id')->get();

        return view('org.fee-profiles.index', compact('feeProfiles', 'semesters'));
    }

    public function create()
    {
        return redirect()->route('org.fee-profiles.index');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'applies_to' => 'nullable|in:all,regular,irregular',
        ]);

        FeeProfile::create([
            'organization_id' => auth()->user()->organization_id,
            'name' => $data['name'],
            'amount' => $data['amount'],
            'category' => match ($data['applies_to'] ?? 'regular') {
                'irregular' => 'IRREGULAR',
                default => 'REGULAR',
            },
            'is_active' => true,
        ]);

        return redirect()->route('org.fee-profiles.index')->with('success', 'Fee profile created.');
    }

    public function edit(FeeProfile $feeProfile)
    {
        if ($feeProfile->organization_id !== auth()->user()->organization_id) {
            abort(403);
        }

        return view('org.fee-profiles.edit', compact('feeProfile'));
    }

    public function update(Request $request, FeeProfile $feeProfile)
    {
        if ($feeProfile->organization_id !== auth()->user()->organization_id) {
            abort(403);
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'category' => 'required|in:REGULAR,IRREGULAR,EXTENDEE,EXEMPTED',
            'is_active' => 'nullable|boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $feeProfile->update($data);

        return redirect()->route('org.fee-profiles.index')->with('success', 'Fee profile updated.');
    }

    public function destroy(FeeProfile $feeProfile)
    {
        if ($feeProfile->organization_id !== auth()->user()->organization_id) {
            abort(403);
        }

        try {
            $feeProfile->delete();
        } catch (\Throwable) {
            return redirect()->route('org.fee-profiles.index')
                ->with('error', 'Fee profile cannot be deleted because it is linked to transactions.');
        }

        return redirect()->route('org.fee-profiles.index')->with('success', 'Fee profile deleted.');
    }
}
