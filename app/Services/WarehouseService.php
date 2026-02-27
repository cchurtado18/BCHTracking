<?php

namespace App\Services;

use App\Models\WarehouseSequence;
use Illuminate\Support\Facades\DB;

class WarehouseService
{
    /**
     * Generate a unique 6-digit warehouse code
     * 
     * @return string
     */
    public function generateWarehouseCode(): string
    {
        return DB::transaction(function () {
            // Lock the row for update
            $sequence = WarehouseSequence::lockForUpdate()->find(1);
            
            if (!$sequence) {
                // If doesn't exist, create it
                $sequence = WarehouseSequence::create([
                    'id' => 1,
                    'next_number' => 1,
                ]);
            }
            
            // Get next number and increment
            $nextNumber = $sequence->next_number;
            $sequence->increment('next_number');
            
            // Format as 6 digits with leading zeros
            return str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
        });
    }
}

