<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GeneralLedger;
use App\Models\ChartOfAccount;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GeneralLedgerController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:view-reports');
    }

    public function index(Request $request): View
    {
        $query = GeneralLedger::with(['account', 'reference', 'user'])
                    ->orderBy('entry_date', 'desc')
                    ->orderBy('journal_group_id', 'desc');

        if ($request->filled('start_date')) {
            $query->where('entry_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->where('entry_date', '<=', $request->end_date);
        }
        if ($request->filled('account_id')) {
            $query->where('chart_of_account_id', $request->account_id);
        }
        if ($request->filled('journal_group_id')) {
            $query->where('journal_group_id', 'like', '%' . $request->journal_group_id . '%');
        }

        $journalEntries = $query->paginate(50)->appends($request->query());
        $accounts = ChartOfAccount::where('is_active', true)->orderBy('account_number')->get();

        return view('admin.reports.general-ledger', compact(
            'journalEntries', 
            'accounts'
        ));
    }
}