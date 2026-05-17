<?php

namespace Database\Factories;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

/**
 * @extends Factory<AuditLog>
 *
 * [Lab 7] AuditLogFactory — honours AuditLog schema constraints:
 *   - CREATED_AT = 'timestamp'  (column name differs from default)
 *   - UPDATED_AT = null         (immutable — no updated_at column)
 *   - details cast as 'array'   (seedInChunks must json_encode before raw insert)
 */
class AuditLogFactory extends Factory // [Lab 7]
{
    protected $model = AuditLog::class;

    // ── [Lab 7] Static ID cache — loaded once per process ────────────────────

    /** @var int[] */
    protected static array $userIds = [];

    private static function userIds(): array
    {
        if (empty(static::$userIds)) {
            static::$userIds = DB::table('users')->pluck('id')->all();
        }
        return static::$userIds;
    }

    // ── [Lab 7] Action / entity-type pools (mirrors AuditLogSeeder) ──────────

    private const ACTIONS = [
        'TRANSACTION_CREATED', 'TRANSACTION_VOIDED',
        'VOID_REQUESTED',      'VOID_APPROVED',    'VOID_REJECTED',
        'FEE_PROFILE_CREATED', 'FEE_PROFILE_UPDATED',
        'REMITTANCE_CREATED',  'REMITTANCE_VERIFIED', 'REMITTANCE_ACCEPTED',
        'STUDENT_IMPORT_COMPLETED', 'STUDENT_ENROLLED_MANUAL',
        'BACKUP_COMPLETED',    'EXPORT_GENERATED',
        'FINE_WINDOW_OPENED',  'FINE_WINDOW_CLOSED',
    ];

    private const ENTITY_TYPES = [
        'TRANSACTION', 'FEE_PROFILE', 'REMITTANCE',
        'VOID_REQUEST', 'IMPORT_LOG', 'EXPORT_LOG',
    ];

    // ── [Lab 7] Definition ────────────────────────────────────────────────────

    public function definition(): array
    {
        $userIds = static::userIds();

        return [
            'user_id'     => ! empty($userIds) ? $this->faker->randomElement($userIds) : null,
            'action'      => $this->faker->randomElement(self::ACTIONS),
            'entity_type' => $this->faker->randomElement(self::ENTITY_TYPES),
            'entity_id'   => $this->faker->numberBetween(1, 10_000),

            // PHP array — seedInChunks will json_encode before DB::table()->insert()
            'details'     => [
                'note'   => $this->faker->sentence(6),
                'source' => 'mass-seeder',
            ],

            'ip_address'  => $this->faker->ipv4(),

            // 'timestamp' is CREATED_AT for this model
            'timestamp'   => $this->faker->dateTimeBetween('-2 years', 'now'),
        ];
    }
}
