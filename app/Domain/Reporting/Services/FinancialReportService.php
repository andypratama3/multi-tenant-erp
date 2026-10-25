<?php

namespace App\Domain\Reporting\Services;

use App\Domain\Reporting\Data\AccountBalanceRow;
use App\Domain\Reporting\Data\TrialBalanceRow;
use App\Support\Money;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FinancialReportService
{
    /**
     * @return Collection<int, TrialBalanceRow>
     */
    public function trialBalance(string $from, string $to): Collection
    {
        $rows = DB::table('journal_lines as jl')
            ->join('journals as j', 'j.id', '=', 'jl.journal_id')
            ->join('accounts as a', 'a.id', '=', 'jl.account_id')
            ->select('a.code', 'a.name', 'a.type')
            ->selectRaw("COALESCE(SUM(CASE WHEN jl.type = 'debit' THEN jl.amount ELSE 0 END), 0) as debit")
            ->selectRaw("COALESCE(SUM(CASE WHEN jl.type = 'credit' THEN jl.amount ELSE 0 END), 0) as credit")
            ->whereNull('j.deleted_at')
            ->whereNull('a.deleted_at')
            ->where('j.status', 'posted')
            ->whereBetween('j.posted_at', [$from, $to])
            ->groupBy('a.id', 'a.code', 'a.name', 'a.type')
            ->orderBy('a.code')
            ->get();

        return $rows->map(function (\stdClass $row): TrialBalanceRow {
            $debit = Money::round((string) $row->debit);
            $credit = Money::round((string) $row->credit);

            return new TrialBalanceRow(
                code: (string) $row->code,
                name: (string) $row->name,
                type: (string) $row->type,
                debit: $debit,
                credit: $credit,
                balance: Money::sub($debit, $credit),
            );
        });
    }

    /**
     * @return array{rows: Collection<int, AccountBalanceRow>, revenue: string, expense: string, net_profit: string}
     */
    public function incomeStatement(string $from, string $to): array
    {
        $rows = $this->accountBalances($from, $to, ['revenue', 'expense']);
        $revenue = '0.00';
        $expense = '0.00';

        foreach ($rows as $row) {
            if ($row->type === 'revenue') {
                $revenue = Money::add($revenue, $row->amount);
            } else {
                $expense = Money::add($expense, $row->amount);
            }
        }

        return [
            'rows' => $rows,
            'revenue' => $revenue,
            'expense' => $expense,
            'net_profit' => Money::sub($revenue, $expense),
        ];
    }

    /**
     * @return array{assets: Collection<int, AccountBalanceRow>, liabilities: Collection<int, AccountBalanceRow>, equity: Collection<int, AccountBalanceRow>, total_assets: string, total_liabilities: string, total_equity: string, net_profit: string, total_liabilities_equity: string, balanced: bool}
     */
    public function balanceSheet(string $asOf): array
    {
        $rows = $this->accountBalances('1970-01-01', $asOf, ['asset', 'liability', 'equity', 'revenue', 'expense']);
        $assets = collect();
        $liabilities = collect();
        $equity = collect();
        $totalAssets = '0.00';
        $totalLiabilities = '0.00';
        $totalEquity = '0.00';
        $revenue = '0.00';
        $expense = '0.00';

        foreach ($rows as $row) {
            if ($row->type === 'asset') {
                $assets->push($row);
                $totalAssets = Money::add($totalAssets, $row->amount);
            } elseif ($row->type === 'liability') {
                $liabilities->push($row);
                $totalLiabilities = Money::add($totalLiabilities, $row->amount);
            } elseif ($row->type === 'equity') {
                $equity->push($row);
                $totalEquity = Money::add($totalEquity, $row->amount);
            } elseif ($row->type === 'revenue') {
                $revenue = Money::add($revenue, $row->amount);
            } elseif ($row->type === 'expense') {
                $expense = Money::add($expense, $row->amount);
            }
        }

        $netProfit = Money::sub($revenue, $expense);
        $totalLiabilitiesEquity = Money::add(Money::add($totalLiabilities, $totalEquity), $netProfit);

        return [
            'assets' => $assets,
            'liabilities' => $liabilities,
            'equity' => $equity,
            'total_assets' => $totalAssets,
            'total_liabilities' => $totalLiabilities,
            'total_equity' => $totalEquity,
            'net_profit' => $netProfit,
            'total_liabilities_equity' => $totalLiabilitiesEquity,
            'balanced' => Money::compare($totalAssets, $totalLiabilitiesEquity) === 0,
        ];
    }

    /**
     * @param  array<int, string>  $types
     * @return Collection<int, AccountBalanceRow>
     */
    private function accountBalances(string $from, string $to, array $types): Collection
    {
        $rows = DB::table('journal_lines as jl')
            ->join('journals as j', 'j.id', '=', 'jl.journal_id')
            ->join('accounts as a', 'a.id', '=', 'jl.account_id')
            ->select('a.code', 'a.name', 'a.type')
            ->selectRaw("COALESCE(SUM(CASE WHEN jl.type = 'debit' THEN jl.amount ELSE 0 END), 0) as debit")
            ->selectRaw("COALESCE(SUM(CASE WHEN jl.type = 'credit' THEN jl.amount ELSE 0 END), 0) as credit")
            ->whereNull('j.deleted_at')
            ->whereNull('a.deleted_at')
            ->where('j.status', 'posted')
            ->whereIn('a.type', $types)
            ->whereBetween('j.posted_at', [$from, $to])
            ->groupBy('a.id', 'a.code', 'a.name', 'a.type')
            ->orderBy('a.code')
            ->get();

        return $rows->map(function (\stdClass $row): AccountBalanceRow {
            $debit = Money::round((string) $row->debit);
            $credit = Money::round((string) $row->credit);
            $amount = in_array((string) $row->type, ['asset', 'expense'], true)
                ? Money::sub($debit, $credit)
                : Money::sub($credit, $debit);

            return new AccountBalanceRow(
                code: (string) $row->code,
                name: (string) $row->name,
                type: (string) $row->type,
                amount: $amount,
            );
        });
    }
}
