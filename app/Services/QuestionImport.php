<?php

namespace App\Services;

use App\Models\PracticePackage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

class QuestionImport
{
    public const HEADERS = ['question', 'option_a', 'option_b', 'option_c', 'option_d', 'correct_answer', 'explanation', 'status'];

    public function parse(UploadedFile $file, PracticePackage $package): array
    {
        $extension = strtolower($file->getClientOriginalExtension());
        if ($extension === 'xlsx') {
            $zip = new \ZipArchive;
            if ($zip->open($file->getRealPath()) !== true) {
                throw ValidationException::withMessages(['file' => __('ui.invalid_file')]);
            }
            $total = 0;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $total += $zip->statIndex($i)['size'];
            }
            $zip->close();
            if ($total > 20 * 1024 * 1024) {
                throw ValidationException::withMessages(['file' => __('ui.file_too_large')]);
            }
        }
        $reader = $extension === 'xlsx' ? new Xlsx : new Csv;
        $reader->setReadDataOnly(true);
        if ($reader instanceof Csv) {
            $reader->setInputEncoding('UTF-8');
            $reader->setDelimiter(',');
        }
        try {
            $book = $reader->load($file->getRealPath());
            $sheet = $book->getSheet(0);
            if ($sheet->getHighestDataRow() > 501 || Coordinate::columnIndexFromString($sheet->getHighestDataColumn()) > 8) {
                throw ValidationException::withMessages(['file' => __('ui.import_limit')]);
            }
            $rows = $sheet->toArray(null, false, false, false);
            $book->disconnectWorksheets();
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw ValidationException::withMessages(['file' => __('ui.invalid_file')]);
        }
        $headers = array_map(fn ($v) => trim((string) $v, "\xEF\xBB\xBF \t\n\r\0\x0B"), array_shift($rows) ?? []);
        if ($headers !== self::HEADERS) {
            throw ValidationException::withMessages(['file' => __('ui.invalid_headers')]);
        }
        $valid = [];
        $errors = [];
        $seen = $package->questions()->pluck('question')->all();
        foreach ($rows as $index => $row) {
            if (! array_filter($row, fn ($v) => $v !== null && $v !== '')) {
                continue;
            }
            $data = array_combine(self::HEADERS, array_map(fn ($v) => trim((string) $v), array_pad($row, 8, '')));
            $validator = Validator::make($data, [
                'question' => 'required|string|max:10000', 'option_a' => 'required|string|max:2000', 'option_b' => 'required|string|max:2000',
                'option_c' => 'required|string|max:2000', 'option_d' => 'required|string|max:2000', 'correct_answer' => 'required|in:A,B,C,D',
                'explanation' => 'nullable|string|max:10000', 'status' => 'required|in:active,draft',
            ]);
            if ($validator->fails()) {
                $errors[] = ['row' => $index + 2, 'message' => implode(' ', $validator->errors()->all())];
            } elseif (in_array($data['question'], $seen, true)) {
                $errors[] = ['row' => $index + 2, 'message' => __('ui.duplicate_question')];
            } else {
                $valid[] = $data;
                $seen[] = $data['question'];
            }
        }
        if (count($valid) + count($errors) === 0) {
            throw ValidationException::withMessages(['file' => __('ui.empty_file')]);
        }

        return ['rows' => $valid, 'errors' => $errors];
    }
}
