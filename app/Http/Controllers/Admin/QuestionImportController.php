<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Question;
use App\Models\QuestionPackage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use OpenSpout\Reader\XLSX\Reader;

class QuestionImportController extends Controller
{
    public function create(): View
    {
        $packages = QuestionPackage::orderBy('name')->get();

        return view('admin.questions.import', compact('packages'));
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
                    if (in_array('text', $headerLower) || in_array('soal', $headerLower)) {
                        continue;
                    }
                }

                $text = $cellValues[0] ?? '';
                if (empty($text)) {
                    continue;
                }

                $optionA = $cellValues[1] ?? '';
                $optionB = $cellValues[2] ?? '';
                $optionC = $cellValues[3] ?? '';
                $optionD = $cellValues[4] ?? '';
                $correct = isset($cellValues[5]) ? strtolower(trim($cellValues[5])) : '';

                if (! in_array($correct, ['a', 'b', 'c', 'd'])) {
                    $errors[] = "Baris ke-".($imported + 2).": correct_option harus a/b/c/d, got '{$correct}'";
                    continue;
                }

                try {
                    Question::create([
                        'question_package_id' => $packageId,
                        'category' => $cellValues[6] ?? $defaultCategory,
                        'difficulty' => $cellValues[7] ?? $defaultDifficulty,
                        'text' => $text,
                        'option_a' => $optionA,
                        'option_b' => $optionB,
                        'option_c' => $optionC,
                        'option_d' => $optionD,
                        'correct_option' => $correct,
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
