<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Question;
use App\Models\QuestionPackage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Reader\XLSX\Reader;
use OpenSpout\Writer\XLSX\Options as XLSXOptions;

class QuestionImportController extends Controller
{
    public function create(Request $request): View
    {
        $packages = QuestionPackage::whereIn('type', $request->user()->visiblePackageTypes())
            ->with('operatorAssessmentCategory')
            ->orderBy('name')
            ->get();
        $selectedPackageId = $request->integer('question_package_id');

        return view('admin.questions.import', compact('packages', 'selectedPackageId'));
    }

    public function template(Request $request)
    {
        $usesPointTemplate = QuestionPackage::usesQuestionPoints($request->string('type')->toString());
        $filename = $usesPointTemplate ? 'template-soal-point.xlsx' : 'template-soal.xlsx';
        $tempPath = storage_path('app/temp_'.$filename);

        $options = new XLSXOptions();
        $options->SHOULD_USE_INLINE_STRINGS = true;

        $writer = new \OpenSpout\Writer\XLSX\Writer($options);
        $writer->openToFile($tempPath);

        $writer->addRow(Row::fromValues([
            'type', 'text', 'option_a', 'option_b', 'option_c', 'option_d',
            'correct_option', 'category', 'difficulty', 'points',
        ]));

        $examples = $usesPointTemplate ? [
            Row::fromValues([
                'multiple_choice',
                'Dokumen apa yang digunakan untuk mencatat pengajuan cuti karyawan?',
                'Form cuti',
                'Surat jalan',
                'Invoice vendor',
                'Berita acara unit',
                'a', 'Administrasi HR', 'basic', '2',
            ]),
            Row::fromValues([
                'multiple_choice',
                'Data personal karyawan wajib dijaga karena termasuk?',
                'Informasi publik',
                'Data rahasia perusahaan',
                'Materi promosi',
                'Dokumen operasional umum',
                'b', 'Kerahasiaan Data', 'intermediate', '5',
            ]),
            Row::fromValues([
                'multiple_choice',
                'Langkah pertama saat menerima keluhan karyawan adalah?',
                'Mengabaikan sampai ada bukti tertulis',
                'Mencatat dan mengklarifikasi informasi awal',
                'Langsung memberi sanksi',
                'Menyebarkan informasi ke grup kerja',
                'b', 'Employee Relation', 'advanced', '8',
            ]),
            Row::fromValues([
                'true_false',
                'Data karyawan boleh dibagikan ke pihak luar tanpa persetujuan.',
                '', '', '', '',
                'b', 'Kerahasiaan Data', 'basic', '3',
            ]),
        ] : [
            Row::fromValues([
                'multiple_choice',
                'Apa fungsi oli mesin?',
                'Melumasi komponen',
                'Mendinginkan mesin',
                'Membersihkan sirkuit oli',
                'Semua benar',
                'd', 'Engine', 'basic', '1',
            ]),
            Row::fromValues([
                'essay',
                'Jelaskan proses perawatan harian pada heavy equipment!',
                '', '', '', '',
                '', 'Maintenance', 'intermediate', '1',
            ]),
            Row::fromValues([
                'upload',
                'Upload foto hasil inspeksi undercarriage unit!',
                '', '', '', '',
                '', 'Inspection', 'advanced', '1',
            ]),
            Row::fromValues([
                'multiple_choice',
                'Komponen yang berfungsi menghasilkan tenaga pada engine adalah?',
                'Piston',
                'Crankshaft',
                'Camshaft',
                'Flywheel',
                'a', 'Engine', 'basic', '1',
            ]),
            Row::fromValues([
                'true_false',
                'Filter udara yang tersumbat dapat menurunkan performa engine.',
                '', '', '', '',
                'a', 'Engine', 'basic', '1',
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
            'question_package_id' => [
                'nullable',
                'integer',
                Rule::exists('question_packages', 'id')->where(function ($query) use ($request): void {
                    $query->whereIn('type', $request->user()->visiblePackageTypes());
                }),
            ],
            'category' => ['nullable', 'string', 'max:100'],
            'difficulty' => ['nullable', 'string', 'max:50'],
        ]);

        $packageId = $data['question_package_id'] ?? null;
        $package = $packageId ? QuestionPackage::find($packageId) : null;
        $defaultCategory = $data['category'] ?? QuestionPackage::typeLabel($request->user()->visiblePackageTypes()[0] ?? QuestionPackage::TYPE_MEKANIK);
        $defaultDifficulty = $data['difficulty'] ?? 'basic';

        $reader = new Reader();
        $reader->open($request->file('file')->getRealPath());

        $imported = 0;
        $errors = [];
        $isFirstRow = true;
        $rowNumber = 0;

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $rowNumber++;
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
                if (! in_array($type, ['multiple_choice', 'true_false', 'essay', 'upload'])) {
                    $type = 'multiple_choice';
                }

                if (! in_array($type, [Question::TYPE_MULTIPLE_CHOICE, Question::TYPE_TRUE_FALSE], true) && $package?->type !== QuestionPackage::TYPE_SHE) {
                    $errors[] = "Baris ke-{$rowNumber}: Essay/Upload khusus untuk paket SHE. Paket lain wajib menggunakan multiple_choice atau true_false.";
                    continue;
                }

                if ($type === Question::TYPE_TRUE_FALSE && $package?->type === QuestionPackage::TYPE_SHE) {
                    $errors[] = "Baris ke-{$rowNumber}: true_false dipakai untuk Mekanik, Operator, atau HR. Paket SHE tetap memakai multiple_choice, essay, dan upload.";
                    continue;
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
                $points = $this->parsePoints($cellValues[9] ?? null);

                if ($type === 'multiple_choice' && ! in_array($correct, ['a', 'b', 'c', 'd'])) {
                    $errors[] = "Baris ke-{$rowNumber}: correct_option harus a/b/c/d, got '{$correct}'";
                    continue;
                }

                if ($type === 'true_false' && ! in_array($correct, ['a', 'b'])) {
                    $errors[] = "Baris ke-{$rowNumber}: correct_option Benar/Salah harus a atau b, got '{$correct}'";
                    continue;
                }

                if ($type === 'multiple_choice') {
                    if (empty($optionA) || empty($optionB) || empty($optionC) || empty($optionD)) {
                        $errors[] = "Baris ke-{$rowNumber}: MC wajib punya 4 pilihan (A/B/C/D)";
                        continue;
                    }
                }

                if ($type === 'true_false') {
                    $optionA = 'Benar';
                    $optionB = 'Salah';
                    $optionC = null;
                    $optionD = null;
                }

                if (QuestionPackage::usesQuestionPoints($package?->type) && $points === null) {
                    $errors[] = "Baris ke-{$rowNumber}: points wajib angka lebih dari 0 untuk paket Operator/HR.";
                    continue;
                }

                try {
                    Question::create([
                        'question_package_id' => $packageId,
                        'type' => $type,
                        'category' => $cellValues[7] ?? $defaultCategory,
                        'difficulty' => $cellValues[8] ?? $defaultDifficulty,
                        'text' => $text,
                        'option_a' => in_array($type, ['multiple_choice', 'true_false'], true) ? $optionA : null,
                        'option_b' => in_array($type, ['multiple_choice', 'true_false'], true) ? $optionB : null,
                        'option_c' => $type === 'multiple_choice' ? $optionC : null,
                        'option_d' => $type === 'multiple_choice' ? $optionD : null,
                        'correct_option' => in_array($type, ['multiple_choice', 'true_false'], true) ? $correct : null,
                        'points' => QuestionPackage::usesQuestionPoints($package?->type) ? $points : 1,
                        'is_active' => true,
                    ]);
                    $imported++;
                } catch (\Throwable $e) {
                    $errors[] = "Baris ke-{$rowNumber}: ".$e->getMessage();
                }
            }
        }

        $reader->close();

        ActivityLog::log('questions_import', "Import {$imported} soal dari Excel", Question::class);

        $message = "Berhasil mengimpor {$imported} soal.";

        $redirect = $packageId
            ? redirect()->route('admin.packages.questions', $packageId)
            : redirect()->route('admin.questions.index');

        if ($errors) {
            $allErrors = implode('<br>', array_slice($errors, 0, 20));
            $extraCount = count($errors) > 20 ? '<br>... dan '. (count($errors) - 20) .' baris lainnya.' : '';
            return $redirect->withErrors(['import' => $allErrors . $extraCount])->with('status', $message);
        }

        return $redirect->with('status', $message);
    }

    private function parsePoints(?string $value): ?float
    {
        if ($value === null || trim($value) === '') {
            return 1;
        }

        $normalized = str_replace(',', '.', trim($value));

        if (! is_numeric($normalized)) {
            return null;
        }

        $points = (float) $normalized;

        return $points > 0 && $points <= 1000 ? $points : null;
    }
}
