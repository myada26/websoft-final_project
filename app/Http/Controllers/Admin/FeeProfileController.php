<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FeeProfile;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class FeeProfileController extends Controller
{
    private const GRID_CATEGORIES = ['REGULAR', 'EXTENDEE', 'IRREGULAR'];

    public function index(Request $request)
    {
        $filters = $request->validate([
            'organization_type' => ['nullable', Rule::in(['UNIVERSITY_WIDE', 'COLLEGE_COUNCIL', 'CLASS_ORG', 'RESERVED'])],
        ]);

        $organizations = Organization::query()
            ->with(['feeProfiles' => fn ($query) => $query
                ->whereIn('category', self::GRID_CATEGORIES)
                ->where('is_active', true)
                ->orderBy('category')
                ->orderByDesc('updated_at')])
            ->when($filters['organization_type'] ?? null, fn ($query, $type) => $query->where('type', $type))
            ->orderByRaw("CASE type WHEN 'UNIVERSITY_WIDE' THEN 0 WHEN 'COLLEGE_COUNCIL' THEN 1 WHEN 'CLASS_ORG' THEN 2 ELSE 3 END")
            ->orderBy('name')
            ->get()
            ->map(function (Organization $organization) {
                $organization->setRelation(
                    'feeProfilesByCategory',
                    $organization->feeProfiles->keyBy('category')
                );

                return $organization;
            });

        return view('admin.fee-profiles.index', [
            'organizations' => $organizations,
            'selectedType' => $filters['organization_type'] ?? '',
            'typeOptions' => [
                ''                => 'All',
                'UNIVERSITY_WIDE' => 'University-Wide',
                'COLLEGE_COUNCIL' => 'College Councils',
                'CLASS_ORG'       => 'Class Organizations',
                'RESERVED'        => 'Reserved',
            ],
        ]);
    }

    public function updateGridRow(Request $request)
    {
        $data = $request->validate([
            'organization_id' => ['required', 'exists:organizations,id'],
            'regular_fee' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'extendee_fee' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'irregular_fee' => ['required', 'numeric', 'min:0', 'max:999999.99'],
        ]);

        $organization = Organization::findOrFail($data['organization_id']);

        DB::transaction(function () use ($organization, $data) {
            $amounts = [
                'REGULAR' => $data['regular_fee'],
                'EXTENDEE' => $data['extendee_fee'],
                'IRREGULAR' => $data['irregular_fee'],
            ];

            foreach ($amounts as $category => $amount) {
                $profile = FeeProfile::query()
                    ->where('organization_id', $organization->id)
                    ->where('category', $category)
                    ->where('is_active', true)
                    ->orderByDesc('updated_at')
                    ->first();

                if (! $profile) {
                    $profile = FeeProfile::query()
                        ->where('organization_id', $organization->id)
                        ->where('category', $category)
                        ->orderByDesc('updated_at')
                        ->first();
                }

                if ($profile) {
                    $profile->update([
                        'name' => $this->profileName($category),
                        'amount' => $amount,
                        'is_active' => true,
                    ]);
                } else {
                    $profile = FeeProfile::create([
                        'organization_id' => $organization->id,
                        'name' => $this->profileName($category),
                        'amount' => $amount,
                        'category' => $category,
                        'is_active' => true,
                    ]);
                }

                FeeProfile::query()
                    ->where('organization_id', $organization->id)
                    ->where('category', $category)
                    ->where('id', '!=', $profile?->id ?? 0)
                    ->update(['is_active' => false]);
            }
        });

        return redirect()
            ->route('admin.fee-profiles.index', $request->only('organization_type'))
            ->with('success', "Fee configuration updated for {$organization->name}.");
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

    private function profileName(string $category): string
    {
        return match ($category) {
            'REGULAR' => 'Regular Fee',
            'EXTENDEE' => 'Extendee Fee',
            'IRREGULAR' => 'Irregular Fee',
            default => "{$category} Fee",
        };
    }
}
