<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Question;
use App\Models\QuestionPackage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\StreamedResponse;
use Illuminate\View\View;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Reader\XLSX\Reader;
use OpenSpout\Writer\XLSX\Options as XLSXOptions;

class QuestionImportController extends Controller
{
    public function create(): View
    {
        $packages = QuestionPackage::orderBy('name')->get();

        return view('admin.questions.import', compact('packages'));
    }

    public function template(): StreamedResponse
    {
        $filename = 'template-soal.xlsx';
        $tempPath = storage_path('app/temp_'.$filename);

        $options = new XLSXOptions();
        $options->SHOULD_USE_INLINE_STRINGS = true;

        $writer = new \OpenSpout\Writer\XLSX\Writer($options);
        $writer->openToFile($tempPath);

        $writer->addRow(Row::fromValues([
            'type', 'text', 'option_a', 'option_b', 'option_c', 'option_d',
            'correct_option', 'category', 'difficulty',
        ]));

        $examples = [
            Row::fromValues([
                'multiple_choice',
                'Apa fungsi oli mesin?',
                'Melumasi komponen',
                'Mendinginkan mesin',
                'Membersihkan sirkuit oli',
                'Semua benar',
                'd', 'Engine', 'basic',
            ]),
            Row::fromValues([
                'essay',
                'Jelaskan proses perawatan harian pada heavy equipment!',
                '', '', '', '',
                '', 'Maintenance', 'intermediate',
            ]),
            Row::fromValues([
                'upload',
                'Upload foto hasil inspeksi undercarriage unit!',
                '', '', '', '',
                '', 'Inspection', 'advanced',
            ]),
            Row::fromValues([
                'multiple_choice',
                'Komponen yang berfungsi menghasilkan tenaga pada engine adalah?',
                'Piston',
                'Crankshaft',
                'Camshaft',
                'Flywheel',
                'a', 'Engine', 'basic',
            ]),
        ];

        foreach ($examples as $row) {
            $writer->addRow($row);
        }

        $writer->close();

        $headers = [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ];

        $callback = function () use ($tempPath): void {
            readfile($tempPath);
            @unlink($tempPath);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120'],
            'question_package_id' => ['nullable', 'integer', 'exists:question_packages,id'],
            'category' => ['nullable', 'string', 'max:100'],
            'difficulty' => ['nullable', 'string', 'max:50'],
        ]);

        $packageId = $data['question_package_id'] ?? null;
        $defaultCategory = $data['category'] ?? 'Mechanic';
        $defaultDifficulty = $data['difficulty'] ?? 'basic';

        $reader = new Reader();
        $reader->open($request->file('file')->getRealPath());

        $imported = 0;
        $errors = [];
        $isFirstRow = true;

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $cells = $row->getCells();
                $cellValues = [];
                foreach ($cells as $cell) {
                    $cellValues[] = trim((string) $cell->getValue());
                }

                if ($isFirstRow) {
                    $isFirstRow = false;
                    $headerLower = array_map('strtolower', $cellValues);
                    if (in_array('text', $headerLower) || in_array('soal', $headerLower) || in_array('type', $headerLower)) {
                        continue;
                    }
                }

                $type = strtolower(trim($cellValues[0] ?? 'multiple_choice'));
                if (! in_array($type, ['multiple_choice', 'essay', 'upload'])) {
                    $type = 'multiple_choice';
                }

                $text = $cellValues[1] ?? '';
                if (empty($text)) {
                    continue;
                }

                $optionA = $cellValues[2] ?? '';
                $optionB = $cellValues[3] ?? '';
                $optionC = $cellValues[4] ?? '';
                $optionD = $cellValues[5] ?? '';
                $correct = isset($cellValues[6]) ? strtolower(trim($cellValues[6])) : '';

                if ($type === 'multiple_choice' && ! in_array($correct, ['a', 'b', 'c', 'd'])) {
                    $errors[] = "Baris ke-".($imported + 2).": correct_option harus a/b/c/d, got '{$correct}'";
                    continue;
                }

                if ($type === 'multiple_choice') {
                    if (empty($optionA) || empty($optionB) || empty($optionC) || empty($optionD)) {
                        $errors[] = "Baris ke-".($imported + 2).": MC wajib punya 4 pilihan (A/B/C/D)";
                        continue;
                    }
                }

                try {
                    Question::create([
                        'question_package_id' => $packageId,
                        'type' => $type,
                        'category' => $cellValues[7] ?? $defaultCategory,
                        'difficulty' => $cellValues[8] ?? $defaultDifficulty,
                        'text' => $text,
                        'option_a' => $type === 'multiple_choice' ? $optionA : null,
                        'option_b' => $type === 'multiple_choice' ? $optionB : null,
                        'option_c' => $type === 'multiple_choice' ? $optionC : null,
                        'option_d' => $type === 'multiple_choice' ? $optionD : null,
                        'correct_option' => $type === 'multiple_choice' ? $correct : null,
                        'is_active' => true,
                    ]);
                    $imported++;
                } catch (\Throwable $e) {
                    $errors[] = "Baris ke-".($imported + 2).": ".$e->getMessage();
                }
            }
        }

        $reader->close();

        ActivityLog::log('questions_import', "Import {$imported} soal dari Excel", Question::class);

        $message = "Berhasil mengimpor {$imported} soal.";
        if ($errors) {
            $message .= ' '.implode('; ', array_slice($errors, 0, 5));
        }

        return redirect()->route('admin.questions.index')->with('status', $message);
    }
}
