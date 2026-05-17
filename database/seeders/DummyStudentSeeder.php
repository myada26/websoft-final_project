<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DummyStudentSeeder extends Seeder
{
    public function run(): void
    {
        $academicYearId = DB::table('academic_years')->where('is_active', true)->value('id');
        $programId      = DB::table('programs')->value('id');

        if (!$academicYearId || !$programId) {
            $this->command->error('Run DatabaseSeeder first — no active academic year or program found.');
            return;
        }

        $firstNames = [
            'Juan', 'Maria', 'Jose', 'Ana', 'Carlos', 'Rosa', 'Miguel', 'Elena',
            'Rafael', 'Liza', 'Angelo', 'Carla', 'Benito', 'Sofia', 'Dante',
            'Patricia', 'Marco', 'Jasmine', 'Rico', 'Cristina', 'Aaron', 'Bianca',
            'Luis', 'Maricel', 'Felix', 'Sheila', 'Renz', 'Joanna', 'Paolo', 'Nica',
            'Harvey', 'Rhea', 'Gino', 'Lea', 'Aldrin', 'Tricia', 'Bryan', 'Camille',
            'Neil', 'Dianne', 'Arvin', 'Hannah', 'Ronald', 'Stephanie', 'Alvin', 'Iris',
            'Kevin', 'Riza', 'Adrian', 'Monica',
        ];

        $lastNames = [
            'Santos', 'Reyes', 'Cruz', 'Bautista', 'Ocampo', 'Garcia', 'Mendoza',
            'Torres', 'Flores', 'Ramos', 'Aquino', 'Villanueva', 'Castillo', 'De Leon',
            'Dela Cruz', 'Gonzales', 'Hernandez', 'Lopez', 'Perez', 'Ramirez',
            'Rivera', 'Rodriguez', 'Sanchez', 'Fernandez', 'Diaz', 'Morales',
            'Pascual', 'Soriano', 'Tolentino', 'Velasco', 'Manalo', 'Macaraeg',
            'Cunanan', 'Buenaventura', 'Cabrera', 'Mercado', 'Navarro', 'Padilla',
            'Quizon', 'Salvador', 'Tomas', 'Uy', 'Valencia', 'Yap', 'Zamora',
        ];

        $middleNames = [
            'B.', 'C.', 'D.', 'E.', 'F.', 'G.', 'H.', 'L.', 'M.', 'N.',
            'P.', 'R.', 'S.', 'T.', 'V.', null, null, null,
        ];

        $usedNumbers = DB::table('students')
            ->where('student_number', 'like', '2026%')
            ->pluck('student_number')
            ->flip()
            ->toArray();

        $students = [];
        $count    = 0;

        while ($count < 50) {
            $suffix = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
            $number = '2026' . $suffix;

            if (isset($usedNumbers[$number])) {
                continue;
            }

            $usedNumbers[$number] = true;

            $students[] = [
                'student_number' => $number,
                'first_name'     => $firstNames[array_rand($firstNames)],
                'last_name'      => $lastNames[array_rand($lastNames)],
                'middle_name'    => $middleNames[array_rand($middleNames)],
                'created_source' => 'MANUAL',
                'created_at'     => now(),
                'updated_at'     => now(),
            ];

            $count++;
        }

        // Insert students and collect their IDs
        $insertedIds = [];
        foreach ($students as $student) {
            $insertedIds[] = DB::table('students')->insertGetId($student);
        }

        // Enroll every student in the active academic year
        $enrollments = [];
        foreach ($insertedIds as $studentId) {
            $enrollments[] = [
                'student_id'       => $studentId,
                'academic_year_id' => $academicYearId,
                'program_id'       => $programId,
                'year_level'       => random_int(1, 4),
                'student_type'     => ['REGULAR', 'IRREGULAR', 'EXTENDEE'][random_int(0, 2)],
                'created_at'       => now(),
                'updated_at'       => now(),
            ];
        }

        DB::table('student_enrollments')->insert($enrollments);

        $this->command->info('Seeded 50 dummy students with student numbers 2026XXXXXX.');
    }
}
