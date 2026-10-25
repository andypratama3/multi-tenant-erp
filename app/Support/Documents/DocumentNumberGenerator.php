<?php

declare(strict_types=1);

namespace App\Support\Documents;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class DocumentNumberGenerator
{
    /**
     * Generate a document number in the format PREFIX-YYYYMMDD-0001.
     *
     * A dedicated sequence row is locked for the whole transaction so concurrent
     * requests can never receive the same number, even for the first document
     * of a period.
     */
    public function next(string $prefix, ?CarbonInterface $date = null): string
    {
        $prefix = strtoupper($prefix);
        $period = ($date ?? now())->format('Ymd');

        $number = DB::transaction(function () use ($prefix, $period): int {
            DB::table('document_sequences')->insertOrIgnore([
                'prefix' => $prefix,
                'period' => $period,
                'last_number' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            /** @var object{last_number: int|string} $sequence */
            $sequence = DB::table('document_sequences')
                ->where('prefix', $prefix)
                ->where('period', $period)
                ->lockForUpdate()
                ->first();

            $next = (int) $sequence->last_number + 1;

            DB::table('document_sequences')
                ->where('prefix', $prefix)
                ->where('period', $period)
                ->update([
                    'last_number' => $next,
                    'updated_at' => now(),
                ]);

            return $next;
        });

        return sprintf('%s-%s-%04d', $prefix, $period, $number);
    }
}
