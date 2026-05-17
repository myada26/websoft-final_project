<?php

namespace Database\Factories;

use App\Models\Student;
use App\Models\StudentEnrollment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Student>
 */
class StudentFactory extends Factory // [Lab 7]
{
    protected $model = Student::class;

    public function definition(): array
    {
        $year = $this->faker->numberBetween(2019, 2024);

        return [
            // [Lab 7] 4-digit year + 6 random digits = 10-char student number (test data only)
            // Real data uses dash format "2023-001"; both coexist intentionally.
            'student_number' => $this->faker->unique()->numerify($year . '######'),

            'first_name'     => $this->faker->firstName(),
            'last_name'      => $this->faker->lastName(),

            // [Lab 7] null 95% of the time; one of Jr./Sr./III the other 5%
            'name_extension' => $this->faker->optional(0.05)->randomElement(['Jr.', 'Sr.', 'III']),

            // [Lab 7] last name used as middle name proxy; null 20% of the time
            'middle_name'    => $this->faker->optional(0.80)->lastName(),

            'email'          => $this->faker->unique()->safeEmail(),

            // [Lab 7] 90% SSC_BULK (imported), 10% MANUAL
            'created_source' => $this->faker->randomElement(
                array_merge(array_fill(0, 9, 'SSC_BULK'), ['MANUAL'])
            ),

            'created_at'     => now(),
            'updated_at'     => now(),
        ];
    }

    /**
     * [Lab 7] State: also create a StudentEnrollment after the student is persisted.
     *
     * Used by FcatsMassSeeder when individual create() calls are needed.
     * For bulk seeding via make()->toArray() + DB::table()->insert(), the seeder
     * builds enrollment rows separately to avoid N+1 inserts.
     *
     * Year level is randomised 1–5; student_type defaults to REGULAR.
     */
    public function withEnrollment(int $academicYearId, int $programId): static
    {
        return $this->afterCreating(
            function (Student $student) use ($academicYearId, $programId): void {
                StudentEnrollment::firstOrCreate(
                    [
                        'student_id'       => $student->id,
                        'academic_year_id' => $academicYearId,
                    ],
                    [
                        'program_id'   => $programId,
                        'year_level'   => $this->faker->numberBetween(1, 5),
                        'student_type' => 'REGULAR',
                        'created_at'   => now(),
                        'updated_at'   => now(),
                    ]
                );
            }
        );
    }
}
