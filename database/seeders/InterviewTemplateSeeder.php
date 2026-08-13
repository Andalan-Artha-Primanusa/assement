<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\InterviewTemplate;
use App\Models\InterviewCategory;
use App\Models\InterviewAspect;

class InterviewTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $template = InterviewTemplate::create([
            'name' => 'Form Penilaian Interview Umum',
            'type' => 'hr',
            'min_recommended_percentage' => 70,
            'min_considered_percentage' => 50,
        ]);

        $data = [
            'A. KUALIFIKASI & PENGALAMAN' => [
                'Pendidikan & kualifikasi sesuai jabatan',
                'Pengalaman kerja relevan',
                'Pemahaman tugas & tanggung jawab jabatan'
            ],
            'B. KOMPETENSI TEKNIS / FUNGSIONAL' => [
                'Penguasaan kompetensi teknis sesuai jabatan',
                'Kemampuan menggunakan tools / equipment / system kerja',
                'Kemampuan troubleshooting & problem solving teknis',
                'Kualitas, ketelitian & akurasi hasil kerja',
                'Produktivitas & pencapaian target kerja'
            ],
            'C. SOFTSKILL & SIKAP KERJA' => [
                'Appearance / Penampilan',
                'General Attitude / Sikap (Perilaku, Sopan Santun)',
                'Communication Skill / Komunikasi & Koordinasi',
                'Integritas, etika & kepatuhan',
                'Teamwork / Kerjasama',
                'Inisiatif dan Problem Solving',
                'Motivasi & komitmen terhadap jabatan',
                'Adaptasi & ketahanan terhadap kondisi site serta Tekanan Kerja',
                'Disiplin, Integritas & Tanggung Jawab'
            ],
            'D. KOMPETENSI KESELAMATAN' => [
                'Pemahaman & kepatuhan K3 pertambangan',
                'Kesadaran lingkungan & pengelolaan dampak kerja',
                'Kepatuhan terhadap SOP, peraturan & standar perusahaan',
                'Kesadaran terhadap risiko & tindakan pencegahan'
            ],
            'E. KEPEMIMPINAN (JIKA DIPERSYARATKAN)' => [
                'Leadership / Kepemimpinan (*)',
                'Developing Others / Mengembangkan Tim (**)',
                'Pengambilan keputusan & accountability (*)'
            ]
        ];

        $catOrder = 1;
        foreach ($data as $catName => $aspects) {
            $category = InterviewCategory::create([
                'interview_template_id' => $template->id,
                'name' => $catName,
                'order' => $catOrder++
            ]);

            $aspOrder = 1;
            foreach ($aspects as $aspName) {
                InterviewAspect::create([
                    'interview_category_id' => $category->id,
                    'name' => $aspName,
                    'order' => $aspOrder++
                ]);
            }
        }
        
        // Also create one for mekanik and operator
        $mekanik = $template->replicate();
        $mekanik->name = 'Form Penilaian Interview Mekanik';
        $mekanik->type = 'mekanik';
        $mekanik->save();
        
        foreach($template->categories as $c) {
            $newC = $c->replicate();
            $newC->interview_template_id = $mekanik->id;
            $newC->save();
            foreach($c->aspects as $a) {
                $newA = $a->replicate();
                $newA->interview_category_id = $newC->id;
                $newA->save();
            }
        }
        
        $operator = $template->replicate();
        $operator->name = 'Form Penilaian Interview Operator';
        $operator->type = 'operator';
        $operator->save();
        
        foreach($template->categories as $c) {
            $newC = $c->replicate();
            $newC->interview_template_id = $operator->id;
            $newC->save();
            foreach($c->aspects as $a) {
                $newA = $a->replicate();
                $newA->interview_category_id = $newC->id;
                $newA->save();
            }
        }
    }
}
