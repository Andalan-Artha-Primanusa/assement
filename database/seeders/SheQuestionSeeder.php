<?php

namespace Database\Seeders;

use App\Models\Question;
use App\Models\QuestionPackage;
use Illuminate\Database\Seeder;

class SheQuestionSeeder extends Seeder
{
    public function run(): void
    {
        $packages = QuestionPackage::whereIn('name', [
            ShePackageSeeder::SHE_DEPARTEMENT_HEAD,
            ShePackageSeeder::SHE_SECTION_HEAD,
            ShePackageSeeder::SHE_LEAD_OF,
        ])->get();

        foreach ($packages as $package) {
            $this->seedMultipleChoice($package);
            $this->seedEssay($package);
            $this->seedPortfolio($package);
        }
    }

    private function seedMultipleChoice(QuestionPackage $package): void
    {
        $questions = [
            [
                'text' => 'Apa tujuan utama Job Safety Analysis (JSA) sebelum pekerjaan dimulai?',
                'options' => [
                    'a' => 'Mengidentifikasi bahaya dan menetapkan pengendalian risiko',
                    'b' => 'Menggantikan toolbox meeting harian',
                    'c' => 'Menentukan target produksi harian',
                    'd' => 'Mencatat absensi pekerja',
                ],
                'correct' => 'a',
            ],
            [
                'text' => 'Urutan pengendalian risiko yang paling tepat adalah?',
                'options' => [
                    'a' => 'APD, administrasi, engineering, eliminasi',
                    'b' => 'Eliminasi, substitusi, engineering, administrasi, APD',
                    'c' => 'Administrasi, APD, eliminasi, substitusi',
                    'd' => 'Substitusi, APD, administrasi, eliminasi',
                ],
                'correct' => 'b',
            ],
            [
                'text' => 'Dokumen yang umum digunakan untuk izin pekerjaan berisiko tinggi adalah?',
                'options' => [
                    'a' => 'Permit to Work',
                    'b' => 'Purchase Request',
                    'c' => 'Daily Timesheet',
                    'd' => 'Delivery Order',
                ],
                'correct' => 'a',
            ],
            [
                'text' => 'Tindakan pertama saat menemukan kondisi tidak aman di area kerja adalah?',
                'options' => [
                    'a' => 'Melanjutkan pekerjaan agar target tercapai',
                    'b' => 'Mengabaikan jika belum terjadi insiden',
                    'c' => 'Menghentikan pekerjaan jika berbahaya dan melaporkan kepada atasan',
                    'd' => 'Menunggu audit berikutnya',
                ],
                'correct' => 'c',
            ],
            [
                'text' => 'Indikator leading SHE yang paling tepat adalah?',
                'options' => [
                    'a' => 'Jumlah fatality',
                    'b' => 'Jumlah inspeksi dan temuan yang ditutup tepat waktu',
                    'c' => 'Jumlah lost time injury',
                    'd' => 'Total kerusakan alat',
                ],
                'correct' => 'b',
            ],
        ];

        foreach ($questions as $question) {
            Question::updateOrCreate(
                [
                    'question_package_id' => $package->id,
                    'text' => $question['text'],
                ],
                [
                    'type' => Question::TYPE_MULTIPLE_CHOICE,
                    'category' => 'SHE Basic',
                    'difficulty' => 'basic',
                    'option_a' => $question['options']['a'],
                    'option_b' => $question['options']['b'],
                    'option_c' => $question['options']['c'],
                    'option_d' => $question['options']['d'],
                    'correct_option' => $question['correct'],
                    'is_active' => true,
                ]
            );
        }
    }

    private function seedEssay(QuestionPackage $package): void
    {
        $questions = [
            'Jelaskan langkah Anda saat harus mengendalikan pekerjaan berisiko tinggi yang melibatkan beberapa kontraktor.',
            'Ceritakan cara Anda melakukan investigasi insiden mulai dari pengamanan lokasi sampai tindakan perbaikan.',
            'Bagaimana strategi Anda meningkatkan budaya SHE di area kerja yang tingkat kepatuhannya masih rendah?',
        ];

        foreach ($questions as $question) {
            Question::updateOrCreate(
                [
                    'question_package_id' => $package->id,
                    'text' => $question,
                ],
                [
                    'type' => Question::TYPE_ESSAY,
                    'category' => 'SHE Leadership',
                    'difficulty' => 'intermediate',
                    'is_active' => true,
                ]
            );
        }
    }

    private function seedPortfolio(QuestionPackage $package): void
    {
        Question::updateOrCreate(
            [
                'question_package_id' => $package->id,
                'text' => 'Upload portfolio atau bukti hasil kerja SHE, misalnya laporan inspeksi, investigasi insiden, program improvement, atau dokumen kampanye keselamatan.',
            ],
            [
                'type' => Question::TYPE_UPLOAD,
                'category' => 'SHE Portfolio',
                'difficulty' => 'advanced',
                'is_active' => true,
            ]
        );
    }
}
