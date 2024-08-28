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
        $this->makePointsTransaction($amount, 'DEBIT', $note);
    }

    /**
     * Credit a specified amount of points to the user's tally.
     *
     * @param int $amount
     * @return void
     */
    public function creditPoints(int $amount, string $note = null): void
    {
        $this->makePointsTransaction($amount, 'CREDIT', $note);
    }

    /**
     * Define a relationship to the points table.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function points()
    {
        return $this->morphMany(Point::class, 'pointable');
    }


    public function makePointsTransaction(int $amount, string $type, string $note = null): void
    {
        $type = strtoupper($type);
        if (!in_array($type, ['CREDIT', 'DEBIT'])) {
            throw new \Exception("Invalid transaction type. It must be either 'CREDIT' or 'DEBIT'.");
        }

        if ($amount <= 0) {
            throw new \Exception("The amount must be positive.");
        }

        if ($type === 'DEBIT' && $amount > $this->getCurrentPoints()) {
            throw new \Exception("Insufficient balance to debit the specified amount.");
        }

        $this->points()->create([
            'amount' => $amount,
            'transaction_type' => $type,
            'note' => $note
        ]);
    }

    protected static function bootHasPoints()
    {
        static::deleting(function ($model) {
            // Automatically delete related points on model deletion
            $model->points()->delete();
        });
    }
}
