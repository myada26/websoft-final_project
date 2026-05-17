<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use SplFileObject;

class CmuCollegeProgramSeeder extends Seeder
{
    public function run(): void
    {
        $csvPath = base_path('cmu_college_program_seed_with_departments.csv');

        if (! is_file($csvPath)) {
            $csvPath = base_path('cmu_college_program_seed.csv');
        }

        if (! is_file($csvPath)) {
            throw new RuntimeException("CSV file not found: {$csvPath}");
        }

        DB::transaction(function () use ($csvPath): void {
            $csv = new SplFileObject($csvPath, 'r');
            $csv->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY);

            $headers = null;
            $expectedDepartmentCodes = [];
            $seededCollegeIds = [];

            foreach ($csv as $row) {
                if ($row === [null] || $row === false) {
                    continue;
                }

                if ($headers === null) {
                    $headers = array_map(
                        fn ($header) => trim(Str::of((string) $header)->replace("\xEF\xBB\xBF", '')->toString()),
                        $row
                    );

                    continue;
                }

                $row = array_pad($row, count($headers), null);
                $data = array_combine($headers, array_slice($row, 0, count($headers)));

                $collegeCode = trim((string) $data['College Code']);
                $collegeName = trim((string) $data['College Name']);
                $programCode = trim((string) $data['Program Code']);
                $programName = trim((string) $data['Program Name']);
                $parentDepartment = trim((string) ($data['Parent Department'] ?? ''));

                if ($collegeCode === '' || $collegeName === '' || $programCode === '' || $programName === '') {
                    continue;
                }

                DB::table('colleges')->updateOrInsert(
                    ['code' => $collegeCode],
                    [
                        'name' => $collegeName,
                        'is_active' => true,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );

                $collegeId = DB::table('colleges')->where('code', $collegeCode)->value('id');
                $seededCollegeIds[] = $collegeId;

                $departmentCode = $parentDepartment !== ''
                    ? $this->departmentCode($parentDepartment)
                    : $collegeCode;
                $departmentName = $parentDepartment !== ''
                    ? $parentDepartment
                    : $collegeName;
                $expectedDepartmentCodes[$collegeId][] = $departmentCode;

                DB::table('departments')->updateOrInsert(
                    [
                        'college_id' => $collegeId,
                        'code' => $departmentCode,
                    ],
                    [
                        'name' => $departmentName,
                        'is_active' => true,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );

                $departmentId = DB::table('departments')
                    ->where('college_id', $collegeId)
                    ->where('code', $departmentCode)
                    ->value('id');

                $existingProgram = DB::table('programs')->where('code', $programCode)->first();

                if ($existingProgram) {
                    DB::table('programs')
                        ->where('id', $existingProgram->id)
                        ->update([
                            'department_id' => $departmentId,
                            'name' => $programName,
                            'is_active' => true,
                            'updated_at' => now(),
                        ]);

                    continue;
                }

                DB::table('programs')->insert([
                    'department_id' => $departmentId,
                    'code' => $programCode,
                    'name' => $programName,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $this->removeUnusedSeedDepartments($expectedDepartmentCodes, $seededCollegeIds);
        });
    }

    /**
     * Remove empty departments created by older seed runs once the CSV provides
     * a more specific department structure.
     */
    private function removeUnusedSeedDepartments(array $expectedDepartmentCodes, array $seededCollegeIds): void
    {
        $seededCollegeIds = array_values(array_unique($seededCollegeIds));

        if ($seededCollegeIds === []) {
            return;
        }

        $unusedDepartments = DB::table('departments')
            ->whereIn('departments.college_id', $seededCollegeIds)
            ->whereNotExists(function ($query): void {
                $query->select(DB::raw(1))
                    ->from('programs')
                    ->whereColumn('programs.department_id', 'departments.id');
            })
            ->whereNotExists(function ($query): void {
                $query->select(DB::raw(1))
                    ->from('organizations')
                    ->whereColumn('organizations.linked_department_id', 'departments.id');
            })
            ->get(['departments.id', 'departments.college_id', 'departments.code'])
            ->reject(function ($department) use ($expectedDepartmentCodes): bool {
                return in_array(
                    $department->code,
                    array_unique($expectedDepartmentCodes[$department->college_id] ?? []),
                    true
                );
            })
            ->pluck('id');

        if ($unusedDepartments->isNotEmpty()) {
            DB::table('departments')->whereIn('id', $unusedDepartments)->delete();
        }
    }

    private function departmentCode(string $name): string
    {
        $words = Str::of($name)
            ->upper()
            ->replaceMatches('/[^A-Z0-9\s]/', ' ')
            ->explode(' ')
            ->filter();

        $code = $words
            ->map(fn (string $word) => Str::substr($word, 0, 1))
            ->join('');

        return Str::limit($code !== '' ? $code : Str::upper(Str::slug($name, '')), 20, '');
    }
}
