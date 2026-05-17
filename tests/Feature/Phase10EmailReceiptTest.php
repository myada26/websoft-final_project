<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Student;
use App\Models\Transaction;
use App\Models\FeeProfile;
use App\Models\AcademicYear;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class Phase10EmailReceiptTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedDatabase();
    }

    protected function seedDatabase(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
    }

    // ==================== FR-0031: Email Receipt Delivery ====================

    public function test_transaction_sends_email_receipt_to_student()
    {
        Http::fake([
            '*' => Http::response(['success' => true, 'message_ids' => ['123']], 200),
        ]);

        $treasurer = $this->getUserByRole('TREASURER');
        $feeProfile = FeeProfile::where('category', 'REGULAR')->first();
        $student = $this->getStudentWithEmail();

        $this->actingAs($treasurer)->post('/org/transactions', [
            'student_id' => $student->id,
            'fee_profile_ids' => [$feeProfile->id],
            'payment_method' => 'CASH',
        ]);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://send.api.mailtrap.io/api/send';
        });
    }

    public function test_email_not_sent_when_student_has_no_email()
    {
        Http::fake([
            'https://send.api.mailtrap.io/api/send' => Http::response(['success' => true], 200),
        ]);

        $treasurer = $this->getUserByRole('TREASURER');
        $feeProfile = FeeProfile::where('category', 'REGULAR')->first();

        $student = Student::create([
            'student_number' => 'TEST-999',
            'first_name' => 'Test',
            'last_name' => 'Student',
            'email' => null,
            'created_source' => 'MANUAL',
        ]);

        $this->actingAs($treasurer)->post('/org/transactions', [
            'student_id' => $student->id,
            'fee_profile_ids' => [$feeProfile->id],
            'payment_method' => 'CASH',
        ]);

        Http::assertNotSent(function ($request) {
            return $request->url() === 'https://send.api.mailtrap.io/api/send';
        });
    }

    public function test_email_sent_for_gcash_transaction()
    {
        Http::fake([
            'https://send.api.mailtrap.io/api/send' => Http::response(['success' => true], 200),
        ]);

        $treasurer = $this->getUserByRole('TREASURER');
        $feeProfile = FeeProfile::where('category', 'REGULAR')->first();
        $student = $this->getStudentWithEmail();

        $this->actingAs($treasurer)->post('/org/transactions', [
            'student_id' => $student->id,
            'fee_profile_ids' => [$feeProfile->id],
            'payment_method' => 'GCASH',
            'gcash_reference' => '1234567890',
        ]);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://send.api.mailtrap.io/api/send';
        });
    }

    public function test_email_sent_for_fine_transaction()
    {
        Http::fake([
            'https://send.api.mailtrap.io/api/send' => Http::response(['success' => true], 200),
        ]);

        $treasurer = $this->getUserByRole('TREASURER');
        $student = $this->getStudentWithEmail();
        $semester = AcademicYear::where('is_active', true)->first();
        $event = \App\Models\Event::where('organization_id', $treasurer->organization_id)->first();

        $fine = \App\Models\StudentFine::create([
            'student_id' => $student->id,
            'organization_id' => $treasurer->organization_id,
            'event_id' => $event->id,
            'academic_year_id' => $semester->id,
            'slots_missed' => 2,
            'fine_amount' => 20.00,
            'status' => 'UNPAID',
        ]);

        app(\App\Services\FineCollectionWindowService::class)->openWindow($treasurer->organization, $treasurer);

        $this->actingAs($treasurer)->post('/org/transactions/fine', [
            'student_id' => $student->id,
            'payment_method' => 'CASH',
            'amount_paid' => $fine->fine_amount,
            'student_fine_id' => $fine->id,
        ]);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://send.api.mailtrap.io/api/send';
        });
    }

    public function test_receipt_email_contains_required_fields()
    {
        Http::fake([
            'https://send.api.mailtrap.io/api/send' => Http::response(['success' => true], 200),
        ]);

        $treasurer = $this->getUserByRole('TREASURER');
        $feeProfile = FeeProfile::where('category', 'REGULAR')->first();
        $student = $this->getStudentWithEmail();

        $this->actingAs($treasurer)->post('/org/transactions', [
            'student_id' => $student->id,
            'fee_profile_ids' => [$feeProfile->id],
            'payment_method' => 'CASH',
        ]);

        Http::assertSent(function ($request) {
            $data = $request->data();
            return isset($data['subject']) 
                && isset($data['html'])
                && isset($data['attachments']);
        });
    }

    public function test_email_has_pdf_attachment()
    {
        Http::fake([
            'https://send.api.mailtrap.io/api/send' => Http::response(['success' => true], 200),
        ]);

        $treasurer = $this->getUserByRole('TREASURER');
        $feeProfile = FeeProfile::where('category', 'REGULAR')->first();
        $student = $this->getStudentWithEmail();

        $this->actingAs($treasurer)->post('/org/transactions', [
            'student_id' => $student->id,
            'fee_profile_ids' => [$feeProfile->id],
            'payment_method' => 'CASH',
        ]);

        Http::assertSent(function ($request) {
            $data = $request->data();
            return isset($data['attachments'][0]['filename']) 
                && $data['attachments'][0]['filename'] === 'receipt.pdf';
        });
    }

    public function test_email_subject_contains_or_number_and_org()
    {
        Http::fake([
            'https://send.api.mailtrap.io/api/send' => Http::response(['success' => true], 200),
        ]);

        $treasurer = $this->getUserByRole('TREASURER');
        $feeProfile = FeeProfile::where('category', 'REGULAR')->first();
        $student = $this->getStudentWithEmail();

        $this->actingAs($treasurer)->post('/org/transactions', [
            'student_id' => $student->id,
            'fee_profile_ids' => [$feeProfile->id],
            'payment_method' => 'CASH',
        ]);

        $tx = Transaction::where('organization_id', $treasurer->organization_id)->first();
        $org = $tx->organization;

        Http::assertSent(function ($request) use ($tx, $org) {
            $data = $request->data();
            return str_contains($data['subject'], $tx->or_number) 
                && str_contains($data['subject'], $org->name);
        });
    }

    // ==================== Helper Methods ====================

    private function getUserByRole(string $role): User
    {
        return User::where('role', $role)->firstOrFail();
    }

    private function getStudentWithEmail(): Student
    {
        $student = Student::whereNotNull('email')->first();
        
        if (!$student) {
            $student = Student::first();
            $student->update(['email' => 'student@test.com']);
        }

        return $student;
    }
}
