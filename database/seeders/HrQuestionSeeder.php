<?php

namespace Database\Seeders;

use App\Models\Question;
use App\Models\QuestionPackage;
use Illuminate\Database\Seeder;

class HrQuestionSeeder extends Seeder
{
    public function run(): void
    {
        $packages = QuestionPackage::where('type', QuestionPackage::TYPE_HR)->get();

        foreach ($packages as $package) {
            $this->seedQuestions($package);
        }
    }

    private function seedQuestions(QuestionPackage $package): void
    {
        $questions = [
            [
                'category' => 'Administrasi',
                'difficulty' => 'basic',
                'text' => 'Apa langkah terbaik saat menerima dokumen penting tetapi data pendukungnya belum lengkap?',
                'options' => [
                    'a' => 'Memproses dokumen agar pekerjaan cepat selesai',
                    'b' => 'Menolak dokumen tanpa memberi informasi lanjutan',
                    'c' => 'Meminta kelengkapan data, mencatat status, dan memberi batas waktu tindak lanjut',
                    'd' => 'Menyimpan dokumen sampai pemohon menanyakan kembali',
                ],
                'correct' => 'c',
            ],
            [
                'category' => 'Kerahasiaan Data',
                'difficulty' => 'basic',
                'text' => 'Informasi gaji, data pribadi, atau dokumen kontrak sebaiknya dibagikan kepada siapa?',
                'options' => [
                    'a' => 'Semua rekan kerja yang meminta',
                    'b' => 'Pihak yang punya kebutuhan kerja dan kewenangan resmi',
                    'c' => 'Grup chat tim agar proses lebih transparan',
                    'd' => 'Vendor eksternal tanpa persetujuan internal',
                ],
                'correct' => 'b',
            ],
            [
                'category' => 'Akurasi Data',
                'difficulty' => 'intermediate',
                'text' => 'Cara paling aman sebelum mengirim rekap data operasional ke atasan adalah?',
                'options' => [
                    'a' => 'Mengirim versi pertama agar cepat',
                    'b' => 'Meminta orang lain bertanggung jawab penuh',
                    'c' => 'Melakukan pengecekan ulang sumber data, rumus, dan periode laporan',
                    'd' => 'Menghapus data yang tampak berbeda dari mayoritas',
                ],
                'correct' => 'c',
            ],
            [
                'category' => 'Prioritas Kerja',
                'difficulty' => 'intermediate',
                'text' => 'Jika dua permintaan datang bersamaan, satu berdampak pada operasional hari ini dan satu bersifat arsip bulanan, mana yang didahulukan?',
                'options' => [
                    'a' => 'Arsip bulanan karena lebih mudah diselesaikan',
                    'b' => 'Permintaan operasional hari ini sambil memberi estimasi waktu untuk arsip bulanan',
                    'c' => 'Menunda keduanya sampai ada instruksi tertulis',
                    'd' => 'Mengerjakan yang diminta oleh teman paling dekat',
                ],
                'correct' => 'b',
            ],
            [
                'category' => 'Komunikasi',
                'difficulty' => 'basic',
                'text' => 'Respon profesional saat menemukan kesalahan data dari departemen lain adalah?',
                'options' => [
                    'a' => 'Langsung menyalahkan pengirim data',
                    'b' => 'Mengubah data tanpa konfirmasi',
                    'c' => 'Mengonfirmasi temuan dengan bukti dan meminta koreksi secara jelas',
                    'd' => 'Membiarkan karena bukan tanggung jawab admin',
                ],
                'correct' => 'c',
            ],
            [
                'category' => 'Kontrol Dokumen',
                'difficulty' => 'intermediate',
                'text' => 'Apa yang perlu dipastikan saat menyimpan dokumen resmi agar mudah diaudit?',
                'options' => [
                    'a' => 'Nama file, versi, tanggal, pemilik dokumen, dan lokasi penyimpanan konsisten',
                    'b' => 'File disimpan di desktop pribadi saja',
                    'c' => 'Dokumen dikirim hanya melalui chat pribadi',
                    'd' => 'Versi lama dihapus tanpa catatan perubahan',
                ],
                'correct' => 'a',
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
                    'category' => $question['category'],
                    'difficulty' => $question['difficulty'],
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
}
