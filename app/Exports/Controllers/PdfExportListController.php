<?php

namespace BookStack\Exports\Controllers;

use BookStack\Exports\Models\PdfExport;
use BookStack\Http\Controller;

class PdfExportListController extends Controller
{
    public function index()
    {
        try {
            $user = user();

            $exports = PdfExport::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->limit(50)
                ->get();

            return view('exports.pdf-exports', [
                'exports' => $exports,
            ]);
        } catch (\Throwable $th) {}
    }
}
