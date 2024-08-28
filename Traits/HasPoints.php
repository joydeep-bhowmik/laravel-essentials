<?php

namespace App\Traits;

use App\Models\Point;
use Illuminate\Database\Eloquent\Builder;

trait HasPoints
{


    /**
     * Get the current points tally for the user.
     *
     * @return int
     */
    public function getCurrentPoints(): int
    {
        $creditSum = $this->points()->where('transaction_type', 'CREDIT')->sum('amount');
        $debitSum = $this->points()->where('transaction_type', 'DEBIT')->sum('amount');

        return $creditSum - $debitSum;
    }

    /**
     * Debit a specified amount of points from the user's tally.
     *
     * @param int $amount
     * @return void
     * @throws \Exception
     */
    public function debitPoints(int $amount, string $note = null): void
    {
        $this->makeTransaction($amount, 'DEBIT', $note);
    }

    /**
     * Credit a specified amount of points to the user's tally.
     *
     * @param int $amount
     * @return void
     */
    public function creditPoints(int $amount, string $note = null): void
    {
        $this->makeTransaction($amount, 'CREDIT', $note);
    }

    /**
     * Define a relationship to the points table.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function points()
    {
        return Point::where('user_id', $this->id)->get();
    }


    public function makeTransaction(int $amount, string $type, string $note = null): void
    {
        if (!in_array($type, ['CREDIT', 'DEBIT'])) {
            throw new \Exception("Invalid transaction type. It must be either 'CREDIT' or 'DEBIT'.");
        }

        if ($amount <= 0) {
            throw new \Exception("The amount must be positive.");
        }

        if ($type === 'DEBIT' && $amount > $this->getCurrentBalance()) {
            throw new \Exception("Insufficient balance to debit the specified amount.");
        }

        $this->points()->create([
            'amount' => $amount,
            'transaction_type' => $type,
            'note' => $note
        ]);
    }
}
