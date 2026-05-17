<?php

namespace Database\Factories;

use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

/**
 * @extends Factory<Transaction>
 *
 * [Lab 7] TransactionFactory — honours all DB constraints on Transaction:
 *   - reference_number is non-null only when payment_method = 'GCASH'
 *   - fee_profile_id is non-null only when transaction_type = 'FEE'
 *   - amount_paid is always formatted as DECIMAL(10,2) string via number_format()
 *   - or_number is a sequential placeholder; FcatsMassSeeder overwrites with real
 *     sequential values per org and then UPDATEs or_sequences (never INSERTs)
 */
class TransactionFactory extends Factory // [Lab 7]
{
    protected $model = Transaction::class;

    // ── [Lab 7] Static ID caches — loaded once per process, not per record ────

    /** @var int[] */
    protected static array $studentIds      = [];

    /** @var int[] */
    protected static array $orgIds          = [];

    /** @var int[] */
    protected static array $academicYearIds = [];

    /** @var int[] */
    protected static array $userIds         = [];

    /** @var int[] */
    protected static array $feeProfileIds   = [];

    /** @var int[] */
    protected static array $remittanceIds   = [];

    /** Sequential counter for placeholder or_numbers — reset on process start */
    protected static int $orCounter = 0;

    // ── [Lab 7] Lazy cache loaders ────────────────────────────────────────────

    private static function studentIds(): array
    {
        if (empty(static::$studentIds)) {
            static::$studentIds = DB::table('students')->pluck('id')->all();
        }
        return static::$studentIds;
    }

    private static function orgIds(): array
    {
        if (empty(static::$orgIds)) {
            static::$orgIds = DB::table('organizations')->where('is_active', true)->pluck('id')->all();
        }
        return static::$orgIds;
    }

    private static function academicYearIds(): array
    {
        if (empty(static::$academicYearIds)) {
            static::$academicYearIds = DB::table('academic_years')->pluck('id')->all();
        }
        return static::$academicYearIds;
    }

    private static function userIds(): array
    {
        if (empty(static::$userIds)) {
            static::$userIds = DB::table('users')->pluck('id')->all();
        }
        return static::$userIds;
    }

    private static function feeProfileIds(): array
    {
        if (empty(static::$feeProfileIds)) {
            static::$feeProfileIds = DB::table('fee_profiles')->where('is_active', true)->pluck('id')->all();
        }
        return static::$feeProfileIds;
    }

    private static function remittanceIds(): array
    {
        if (empty(static::$remittanceIds)) {
            static::$remittanceIds = DB::table('remittances')->pluck('id')->all();
        }
        return static::$remittanceIds;
    }

    // ── [Lab 7] Definition ────────────────────────────────────────────────────

    public function definition(): array
    {
        // [Lab 7] Placeholder or_number — seeder overwrites with real sequential values
        $orNumber = 'SEED-' . str_pad(++static::$orCounter, 8, '0', STR_PAD_LEFT);

        return [
            // [Lab 7] Placeholder — FcatsMassSeeder will overwrite with real OR numbers
            // and UPDATE or_sequences (rows already exist — never INSERT)
            'or_number'          => $orNumber,

            'organization_id'    => $this->faker->randomElement(static::orgIds()),
            'academic_year_id'   => $this->faker->randomElement(static::academicYearIds()),
            'student_id'         => $this->faker->randomElement(static::studentIds()),
            'processed_by_user_id' => $this->faker->randomElement(static::userIds()),

            // [Lab 7] 80% FEE / 20% FINE
            'transaction_type'   => $this->faker->randomElement(
                array_merge(array_fill(0, 8, 'FEE'), array_fill(0, 2, 'FINE'))
            ),

            // [Lab 7] 70% CASH / 30% GCASH
            'payment_method'     => $this->faker->randomElement(
                array_merge(array_fill(0, 7, 'CASH'), array_fill(0, 3, 'GCASH'))
            ),

            // [Lab 7] Closures receive the already-resolved attribute bag so dependent
            // fields always reflect the final transaction_type / payment_method value,
            // even when those fields are overridden by a state or make([...]).

            // reference_number: 13-digit numeric string for GCASH, null for CASH
            'reference_number'   => fn(array $attrs) =>
                $attrs['payment_method'] === 'GCASH'
                    ? $this->faker->numerify('#############')
                    : null,

            // fee_profile_id: set for FEE, null for FINE
            'fee_profile_id'     => fn(array $attrs) => (function () use ($attrs) {
                if ($attrs['transaction_type'] !== 'FEE') {
                    return null;
                }
                $ids = static::feeProfileIds();
                return ! empty($ids) ? $this->faker->randomElement($ids) : null;
            })(),

            // amount_paid: always DECIMAL(10,2) string — never raw float
            'amount_paid'        => fn(array $attrs) => (function () use ($attrs) {
                if ($attrs['transaction_type'] === 'FEE') {
                    $raw = $this->faker->randomElement([100.00, 150.00, 200.00, 250.00]);
                } else {
                    $raw = $this->faker->numberBetween(1, 8) * 10.00; // 1–8 slots × 10
                }
                return number_format($raw, 2, '.', '');
            })(),

            'student_fine_id'    => null, // linked by FineService when applicable
            'remittance_id'      => null, // set via remitted() state or seeder batch

            // [Lab 7] 2% chance of being voided
            'is_void'            => $this->faker->boolean(2),

            // [Lab 7] random created_at within the last 2 years
            'created_at'         => $this->faker->dateTimeBetween('-2 years', 'now'),
            'updated_at'         => now(),
        ];
    }

    // ── [Lab 7] States ────────────────────────────────────────────────────────

    /**
     * Attach a valid existing remittance_id from the database.
     * Requires at least one Remittance row to exist.
     */
    public function remitted(): static
    {
        return $this->state(function () {
            $ids = static::remittanceIds();
            return [
                'remittance_id' => ! empty($ids)
                    ? $this->faker->randomElement($ids)
                    : null,
            ];
        });
    }

    /**
     * Mark the transaction as voided.
     */
    public function voided(): static
    {
        return $this->state([
            'is_void' => true,
        ]);
    }
}
