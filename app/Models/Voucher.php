<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    protected $fillable = [
        'voucher_no',
        'voucher_type',
        'voucher_status',
        'voucher_date',
        'reference_id',
        'journal_entry_id',
        'narration',
        'created_by',
        'amount'
    ];

    protected function casts(): array
    {
        return [
            'voucher_date' => 'date',

        ];
    }

    public function entries()
    {
        return $this->hasMany(VoucherEntry::class);
    }

    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('voucher_date', [$startDate, $endDate]);
    }
}
