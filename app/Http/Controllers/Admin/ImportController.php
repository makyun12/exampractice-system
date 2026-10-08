<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PracticePackage;
use App\Services\QuestionImport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImportController extends Controller
{
    public function index(): View
    {
        return view('admin.import', ['packages' => PracticePackage::with('module.program')->get()]);
    }

    public function preview(Request $request, QuestionImport $import): View
    {
        $request->validate(['package_id' => 'required|exists:practice_packages,id', 'file' => 'required|file|max:2048|extensions:csv,xlsx']);
        $package = PracticePackage::findOrFail($request->package_id);
        $preview = $import->parse($request->file('file'), $package);
        $token = Str::random(40);
        if (empty($preview['errors'])) {
            Cache::put('import:'.$request->user()->id.':'.$token, ['package_id' => $package->id, 'rows' => $preview['rows']], now()->addMinutes(20));
        }

        return view('admin.import-preview', compact('preview', 'package', 'token'));
    }

    public function confirm(Request $request): RedirectResponse
    {
        $request->validate(['token' => 'required|alpha_num|size:40']);
        $key = 'import:'.$request->user()->id.':'.$request->token;
        $data = Cache::get($key);
        if (! $data) {
            return redirect()->route('admin.import')->withErrors(['file' => __('ui.import_expired')]);
        }
        DB::transaction(function () use ($data) {
            $package = PracticePackage::whereKey($data['package_id'])->lockForUpdate()->firstOrFail();
            $existing = $package->questions()->pluck('question')->all();
            foreach ($data['rows'] as $row) {
                if (! in_array($row['question'], $existing, true)) {
                    $package->questions()->create($row);
                }
            }
        });
        Cache::forget($key);

        return redirect()->route('admin.content.index', ['kind' => 'questions', 'parent' => $data['package_id']])->with('success', __('ui.import_success'));
    }

    public function template(string $format): StreamedResponse
    {
        abort_unless(in_array($format, ['csv', 'xlsx']), 404);
        $rows = [QuestionImport::HEADERS, ['What does こんにちは mean?', 'Hello', 'Goodbye', 'Thank you', 'Good night', 'A', 'こんにちは is a daytime greeting.', 'draft']];

        return response()->streamDownload(function () use ($format, $rows) {
            if ($format === 'csv') {
                $out = fopen('php://output', 'w');
                fwrite($out, "\xEF\xBB\xBF");
                foreach ($rows as $row) {
                    fputcsv($out, $row, ',', '"', '');
                } fclose($out);
            } else {
                $book = new Spreadsheet;
                $sheet = $book->getActiveSheet();
                $sheet->fromArray($rows);
                foreach (range('A', 'H') as $column) {
                    $sheet->getColumnDimension($column)->setWidth(25);
                }
                $sheet->getStyle('A1:H1')->getFont()->setBold(true);
                (new Xlsx($book))->save('php://output');
                $book->disconnectWorksheets();
            }
        }, 'exampractice-question-template.'.$format, ['Content-Type' => $format === 'csv' ? 'text/csv; charset=UTF-8' : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }
}
