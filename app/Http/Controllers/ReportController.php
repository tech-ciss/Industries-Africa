<?php

namespace App\Http\Controllers;

use App\Exports\TicketsExport;
use App\Http\Requests\ReportRequest;
use App\Models\Category;
use App\Models\User;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function index()
    {
        return view('reports.index', [
            'categories' => Category::orderBy('name')->get(),
            'technicians' => User::where('role', 'technician')->orderBy('name')->get(),
        ]);
    }

    /** Excel : feuille "Incidents" (filtrable) + feuille "Statistiques". */
    public function exportExcel(ReportRequest $request)
    {
        $filters = $request->safe()->only(['status', 'priority', 'category_id', 'technician_id', 'date_from', 'date_to']);

        return Excel::download(new TicketsExport($filters), 'tickets-'.now()->format('Y-m-d').'.xlsx');
    }

    /** PDF : rapport du mois demandé (mois courant par défaut). */
    public function exportPdf(ReportRequest $request, ReportService $reports)
    {
        $month = $request->filled('month')
            ? Carbon::createFromFormat('!Y-m', $request->input('month'))
            : now()->startOfMonth();

        return Pdf::loadView('reports.pdf', $reports->monthly($month))
            ->download('rapport-mensuel-'.$month->format('Y-m').'.pdf');
    }
}
