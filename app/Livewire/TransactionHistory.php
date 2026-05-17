<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;

class TransactionHistory extends Component
{
    use WithPagination;

    public $date = '';
    public $type = '';
    public $status = '';

    public function updating($name, $value)
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = Transaction::with(['student', 'feeProfile', 'studentFine'])
            ->where('organization_id', Auth::user()->organization_id)
            ->latest();

        if ($this->date) {
            $query->whereDate('created_at', $this->date);
        }

        if ($this->type) {
            $query->where('transaction_type', $this->type);
        }

        if ($this->status === 'VOID') {
            $query->where('is_void', true);
        } elseif ($this->status) {
            $query->where('is_void', false);
        }

        // Using simple collection filter for status because amounts are in relations.
        $transactions = $query->get()->filter(function ($tx) {
            if ($this->status === 'VOID' || !$this->status) {
                return true;
            }

            $totalDue = $tx->isFine() 
                ? ($tx->studentFine ? $tx->studentFine->fine_amount : $tx->amount_paid)
                : ($tx->feeProfile ? $tx->feeProfile->amount : $tx->amount_paid);

            $balance = $totalDue - $tx->amount_paid;
            
            if ($this->status === 'FULLY_PAID') {
                return $balance <= 0;
            } elseif ($this->status === 'PARTIAL') {
                return $balance > 0;
            }

            return true;
        });

        return view('livewire.transaction-history', [
            'transactions' => $transactions
        ])->extends('layouts.app')->section('content');
    }
}
