<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\StudentFine;
use App\Models\AcademicYear;
use App\Models\Transaction;
use Illuminate\Http\Request;

class PublicAccountabilityController extends Controller
{
    public function index(Request $request)
    {
        $studentNumber = trim($request->query('student_number', ''));
        $student       = null;
        $fines         = collect();
        $notFound      = false;
        $feeStatus     = null;
        $totalOutstanding = 0;

        if ($studentNumber !== '') {
            $student = Student::where('student_number', $studentNumber)
                ->with('latestEnrollment.academicYear', 'latestEnrollment.program')
                ->first();

            if ($student) {
                $fines = StudentFine::where('student_id', $student->id)
                    ->with([
                        'event:id,name,date',
                        'organization:id,name',
                        'transaction:id,or_number',
                    ])
                    ->orderByDesc('created_at')
                    ->get();

                $activeYear = AcademicYear::where('is_active', true)->first();
                if ($activeYear) {
                    $paidTx = Transaction::where('student_id', $student->id)
                        ->where('academic_year_id', $activeYear->id)
                        ->where('transaction_type', 'FEE')
                        ->where('is_void', false)
                        ->exists();
                    $feeStatus = $paidTx ? 'PAID' : 'UNPAID';
                }

                $totalOutstanding = $fines->where('status', 'UNPAID')->sum('fine_amount');
            } else {
                $notFound = true;
            }
        }

        return view('public.check-fees', compact('studentNumber', 'student', 'fines', 'notFound', 'feeStatus', 'totalOutstanding'));
    }
}
