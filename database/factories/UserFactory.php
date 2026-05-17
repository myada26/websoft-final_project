<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;   // [Lab 7]
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    // ── [Lab 7] Static ID caches — loaded once per process ───────────────────

    /** @var int[] */
    protected static array $fcatsOrgIds     = []; // [Lab 7]

    /** @var int[] */
    protected static array $fcatsStudentIds = []; // [Lab 7]

    private static function fcatsOrgIds(): array // [Lab 7]
    {
        if (empty(static::$fcatsOrgIds)) { // [Lab 7]
            static::$fcatsOrgIds = DB::table('organizations') // [Lab 7]
                ->where('is_active', true) // [Lab 7]
                ->pluck('id') // [Lab 7]
                ->all(); // [Lab 7]
        } // [Lab 7]
        return static::$fcatsOrgIds; // [Lab 7]
    } // [Lab 7]

    private static function fcatsStudentIds(): array // [Lab 7]
    {
        if (empty(static::$fcatsStudentIds)) { // [Lab 7]
            static::$fcatsStudentIds = DB::table('students') // [Lab 7]
                ->pluck('id') // [Lab 7]
                ->all(); // [Lab 7]
        } // [Lab 7]
        return static::$fcatsStudentIds; // [Lab 7]
    } // [Lab 7]

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    // ── [Lab 7] State ─────────────────────────────────────────────────────────

    /**
     * [Lab 7] Generate a FCATS officer account linked to a random org and student.
     *
     * Usage: User::factory()->fcatsOfficer()->count(10)->make()
     *
     * username format: "{student_number}-{org_code}" where org_code is derived from
     * the org ID (organizations has no code column — proxy: ORG001, ORG002, …).
     */
    public function fcatsOfficer(): static // [Lab 7]
    {
        return $this->state(function () { // [Lab 7]
            $orgIds     = static::fcatsOrgIds(); // [Lab 7]
            $studentIds = static::fcatsStudentIds(); // [Lab 7]

            $orgId     = ! empty($orgIds)     ? $this->faker->randomElement($orgIds)     : null; // [Lab 7]
            $studentId = ! empty($studentIds) ? $this->faker->randomElement($studentIds) : null; // [Lab 7]

            // Derive a stable org code from the org ID (no code column on organizations)
            $orgCode = $orgId !== null // [Lab 7]
                ? 'ORG' . str_pad($orgId, 3, '0', STR_PAD_LEFT) // [Lab 7]
                : 'ORG000'; // [Lab 7]

            // Load the student_number for the username; fall back to a random string
            $studentNumber = $studentId !== null // [Lab 7]
                ? (DB::table('students')->where('id', $studentId)->value('student_number') ?? Str::random(8)) // [Lab 7]
                : Str::random(8); // [Lab 7]

            return [ // [Lab 7]
                'username'               => "{$studentNumber}-{$orgCode}", // [Lab 7]
                'password_hash'          => Hash::make('password'), // [Lab 7]
                'role'                   => $this->faker->randomElement(['Treasurer', 'Auditor', 'Chairperson']), // [Lab 7]
                'organization_id'        => $orgId, // [Lab 7]
                'student_id'             => $studentId, // [Lab 7]
                'is_active'              => true, // [Lab 7]
                'failed_login_attempts'  => 0, // [Lab 7]
                'locked_until'           => null, // [Lab 7]
                'last_login'             => $this->faker->boolean(50) // [Lab 7]
                    ? $this->faker->dateTimeBetween('-6 months', 'now') // [Lab 7]
                    : null, // [Lab 7]
            ]; // [Lab 7]
        }); // [Lab 7]
    } // [Lab 7]
}
