<?php

namespace Database\Seeders;

use App\Models\Question;
use App\Models\QuestionPackage;
use Illuminate\Database\Seeder;

class QuestionTestingSeeder extends Seeder
{
    public function run(): void
    {
        $packages = QuestionPackage::whereIn('name', [
            QuestionPackageSeeder::BASIC,
            QuestionPackageSeeder::POWER_TRAIN,
            QuestionPackageSeeder::HYDRAULIC_ELECTRICAL,
        ])->get()->keyBy('name');

        $questions = $this->getQuestions();

        foreach ($questions as $q) {
            $category = $this->categoryForNumber($q['number']);
            $difficulty = $this->difficultyForNumber($q['number']);
            $packageName = $this->packageNameForNumber($q['number']);
            $package = $packages[$packageName] ?? null;

            Question::updateOrCreate(
                ['text' => $q['text']],
                [
                    'question_package_id' => $package?->id,
                    'category' => $category,
                    'difficulty' => $difficulty,
                    'option_a' => $q['options']['a'],
                    'option_b' => $q['options']['b'],
                    'option_c' => $q['options']['c'],
                    'option_d' => $q['options']['d'],
                    'correct_option' => $this->answerKey()[$q['number']] ?? 'a',
                    'is_active' => true,
                ]
            );
        }
    }

    private function answerKey(): array
    {
        
        return [
            1 => 'a', 2 => 'd', 3 => 'd', 4 => 'c', 5 => 'c',
            6 => 'c', 7 => 'd', 8 => 'a', 9 => 'd', 10 => 'd',
            11 => 'c', 12 => 'a', 13 => 'b', 14 => 'a', 15 => 'd',
            16 => 'c', 17 => 'd', 18 => 'd', 19 => 'c', 20 => 'd',
            21 => 'b', 22 => 'c', 23 => 'b', 24 => 'c', 25 => 'a',
            26 => 'a', 27 => 'a', 28 => 'b', 29 => 'a', 30 => 'd',
            31 => 'c', 32 => 'c', 33 => 'd', 34 => 'a', 35 => 'a',
            36 => 'd', 37 => 'a', 38 => 'c', 39 => 'b', 40 => 'c',
            41 => 'd', 42 => 'c', 43 => 'c', 44 => 'c', 45 => 'b',
            46 => 'c', 47 => 'c', 48 => 'b', 49 => 'c', 50 => 'c',
        ];
    }

    private function categoryForNumber(int $number): string
    {
        return match (true) {
            $number <= 3 => 'Safety',
            $number <= 15 => 'Maintenance & Tools',
            $number <= 20 => 'Engine',
            $number <= 30 => 'Power Train',
            $number <= 40 => 'Steering & Undercarriage',
            $number <= 45 => 'Hydraulic',
            default => 'Electrical',
        };
    }

    private function difficultyForNumber(int $number): string
    {
        return match (true) {
            $number <= 15 => 'basic',
            $number <= 35 => 'intermediate',
            default => 'advanced',
        };
    }

    private function packageNameForNumber(int $number): string
    {
        return match (true) {
            $number <= 20 => QuestionPackageSeeder::BASIC,
            $number <= 40 => QuestionPackageSeeder::POWER_TRAIN,
            default => QuestionPackageSeeder::HYDRAULIC_ELECTRICAL,
        };
    }

    private function getQuestions(): array
    {
        return [
            [
                'number' => 1,
                'text' => 'Suatu usaha untuk dapat melaksanakan pekerjaan tanpa terjadi adanya suatu kecelakaan merupakan definisi dari:',
                'options' => ['a' => 'Keselamatan kerja', 'b' => 'Kedisiplinan kerja', 'c' => 'Kesehatan kerja', 'd' => 'Ketelitian kerja'],
            ],
            [
                'number' => 2,
                'text' => 'Suatu kejadian yang tidak direncanakan, tidak diduga, tidak diinginkan dan terjadi secara tiba-tiba serta bersifat merugikan disebut:',
                'options' => ['a' => 'Action', 'b' => 'Influent', 'c' => 'Incident', 'd' => 'Accident'],
            ],
            [
                'number' => 3,
                'text' => 'Sebuah tag yang berisikan keterangan bahwa alat dimana tag tersebut terpasang tidak dapat dioperasikan disebut:',
                'options' => ['a' => 'Personal danger tag', 'b' => 'Permit tag', 'c' => 'Information tag', 'd' => 'Out of service tag'],
            ],
            [
                'number' => 4,
                'text' => 'Tractors roda dengan perlengkapan blade/ripper yang pekerjaannya untuk meratakan tanah/finishing adalah:',
                'options' => ['a' => 'Dump truck', 'b' => 'Wheel loader', 'c' => 'Motor grader', 'd' => 'Wheel dozer'],
            ],
            [
                'number' => 5,
                'text' => 'Pada engine SA12V140-1, angka 140 menunjukkan:',
                'options' => ['a' => 'Isi cylinder', 'b' => 'Total piston displacement', 'c' => 'Diameter cylinder', 'd' => 'Size/ukuran engine'],
            ],
            [
                'number' => 6,
                'text' => 'Torque wrench berfungsi untuk:',
                'options' => ['a' => 'Melepas nut dengan torsi tertentu', 'b' => 'Melepas bolt dengan torsi tertentu', 'c' => 'Mengencangkan bolt atau nut dengan torsi tertentu', 'd' => 'Mengencangkan bolt atau nut'],
            ],
            [
                'number' => 7,
                'text' => 'Berikut ini adalah yang termasuk fungsi dari vernier caliper adalah:',
                'options' => ['a' => 'Mengukur diameter luar', 'b' => 'Mengukur diameter dalam', 'c' => 'Mengukur kedalaman', 'd' => 'Jawaban a, b dan c benar'],
            ],
            [
                'number' => 8,
                'text' => 'Berapa hasil dari pembacaan alat ukur berikut (5,2 mm):',
                'options' => ['a' => '5,2 mm', 'b' => '5,25 mm', 'c' => '12,2 mm', 'd' => '12,25 mm'],
            ],
            [
                'number' => 9,
                'text' => 'Alat yang digunakan untuk memegang atau membatasi gerakan dari pin atau shaft adalah:',
                'options' => ['a' => 'Cotter pin', 'b' => 'Tapper pin', 'c' => 'Clamp', 'd' => 'Snap Ring'],
            ],
            [
                'number' => 10,
                'text' => 'Suatu bahan yang digunakan untuk merapatkan atau menutup celah antara dua benda untuk mencegah terjadinya kebocoran fluida atau gas adalah:',
                'options' => ['a' => 'Adhesive', 'b' => 'Gasket Eliminator 518', 'c' => 'Lubricant', 'd' => 'Sealent'],
            ],
            [
                'number' => 11,
                'text' => 'Pekerjaan yang dilakukan karena mesin benar-benar mati karena rusak, tetapi kerusakan tersebut sudah diperkirakan sebelumnya adalah:',
                'options' => ['a' => 'Preventive maintenance', 'b' => 'Break down maintenance', 'c' => 'Corrective maintenance', 'd' => 'Emergency maintenance'],
            ],
            [
                'number' => 12,
                'text' => 'Pada saat hour meter unit menunjukan 4750 hm, maka perawatan periodik yang dilakukan adalah:',
                'options' => ['a' => '250 hm', 'b' => '2000 hm', 'c' => '750 hm', 'd' => '1000 hm'],
            ],
            [
                'number' => 13,
                'text' => 'Yang dimaksud SAE adalah:',
                'options' => ['a' => 'Service of Automatis Engineers', 'b' => 'Society of Automotive Engineers', 'c' => 'Society of automatis engineers', 'd' => 'Service of automotive engineers'],
            ],
            [
                'number' => 14,
                'text' => 'Jika menyetel brake melebihi ukuran standard, maka akan menyebabkan:',
                'options' => ['a' => 'Lining brake cepat aus', 'b' => 'Steering cepat aus', 'c' => 'Gaya pengereman bagus', 'd' => 'Semua jawaban salah'],
            ],
            [
                'number' => 15,
                'text' => 'Tujuan dilaksanakan perawatan adalah:',
                'options' => ['a' => 'Meminimumkan biaya perawatan', 'b' => 'Memaksimumkan waktu operasi', 'c' => 'Mencegah hal yang membahayakan', 'd' => 'Jawaban a, b dan c benar'],
            ],
            [
                'number' => 16,
                'text' => 'Udara yang diisap oleh piston ke dalam ruang bakar pada Diesel Engine, berfungsi untuk kecuali:',
                'options' => ['a' => 'Membilas sisa-sisa gas buang', 'b' => 'Meningkatkan compression pressure', 'c' => 'Mendinginkan ruang bakar', 'd' => 'Bercampur dengan bahan bakar supaya terjadi pembakaran'],
            ],
            [
                'number' => 17,
                'text' => 'Pada Diesel Engine 4 langkah untuk menghasilkan tenaga (power) maka:',
                'options' => ['a' => '2 kali putaran crankshaft menghasilkan 1 kali tenaga', 'b' => '2 kali putaran crankshaft dan 1 kali putaran camshaft', 'c' => '4 kali langkah piston menghasilkan 1 kali tenaga', 'd' => 'Jawaban a, b dan c benar'],
            ],
            [
                'number' => 18,
                'text' => 'Diesel Engine yang dilengkapi dengan Turbocharger dan After cooler, fungsi after cooler:',
                'options' => ['a' => 'Meningkatkan volume kompresi di dalam ruangan bakar', 'b' => 'Mendinginkan Turbocharger sehingga HP engine naik', 'c' => 'Meningkatkan volume udara di ruang bakar', 'd' => 'Meningkatkan kerapatan udara yang masuk ke ruang bakar'],
            ],
            [
                'number' => 19,
                'text' => 'Pada Diesel Engine 4 langkah, urutan langkah yang terjadi adalah:',
                'options' => ['a' => 'Kompresi, power, exhaust dan intake', 'b' => 'Power, exhaust, intake dan kompresi', 'c' => 'Intake, compression, power dan exhaust', 'd' => 'Jawaban a, b dan c benar'],
            ],
            [
                'number' => 20,
                'text' => 'Apabila penyetelan valve terlalu rapat, maka akan terjadi:',
                'options' => ['a' => 'Valve membuka lebih lambat, dan menutup lebih cepat', 'b' => 'Valve membuka lebih cepat, dan menutup lebih cepat', 'c' => 'Valve membuka lebih lambat, dan menutup lebih lambat', 'd' => 'Valve membuka lebih cepat dan menutup lebih lambat'],
            ],
            [
                'number' => 21,
                'text' => 'Komponen yang berfungsi untuk mengatur kecepatan pada unit dan untuk mendapatkan posisi maju atau mundur adalah:',
                'options' => ['a' => 'Inertia brake', 'b' => 'Transmisi', 'c' => 'Main Clutch', 'd' => 'Bevel gear'],
            ],
            [
                'number' => 22,
                'text' => 'Pada clutch diafragma type, yang terhubung dengan input transmisi adalah:',
                'options' => ['a' => 'Pressure plate', 'b' => 'Fly Wheel', 'c' => 'Disc Clutch', 'd' => 'Release bearing'],
            ],
            [
                'number' => 23,
                'text' => 'Shifter Fork berfungsi untuk:',
                'options' => ['a' => 'Menetralkan interlocking system', 'b' => 'Memindahkan roda gigi atau kopling', 'c' => 'Sebagai alat pengaman sewaktu unit sedang operasi', 'd' => 'Memindahkan kecepatan pada saat unit berjalan'],
            ],
            [
                'number' => 24,
                'text' => 'Komponen yang berfungsi sebagai penyambung dan pemutus tenaga engine dengan transmisi disebut?',
                'options' => ['a' => 'Damper', 'b' => 'Transmission', 'c' => 'Clutch', 'd' => 'Steering'],
            ],
            [
                'number' => 25,
                'text' => 'Pada transmisi jenis ini roda gigi tidak saling berhubungan pada kondisi netral:',
                'options' => ['a' => 'Sliding mesh', 'b' => 'Syncro mesh', 'c' => 'Constant mesh', 'd' => 'Semua salah'],
            ],
            [
                'number' => 26,
                'text' => 'Apa yang dimaksud dengan torqflow drive system?',
                'options' => ['a' => 'Sistem pemindah tenaga dari engine ke power train dengan perantara zat cair', 'b' => 'Sistem pemindah tenaga dari engine ke power train dengan perantara hasil pembakaran', 'c' => 'Sistem pemindah tenaga dari engine ke power train dengan perantara udara', 'd' => 'Sistem pemindah tenaga dari engine ke power train dengan perantara benda padat'],
            ],
            [
                'number' => 27,
                'text' => 'Fungsi turbine pada torque converter adalah:',
                'options' => ['a' => 'Merubah tenaga kinetis menjadi tenaga mekanis', 'b' => 'Merubah tenaga mekanis menjadi tenaga kinetis', 'c' => 'Mengarahkan aliran oli ke stator', 'd' => 'Merubah tenaga engine sesuai dengan bebannya'],
            ],
            [
                'number' => 28,
                'text' => 'Fungsi impeller pada torque converter adalah:',
                'options' => ['a' => 'Merubah tenaga kinetis menjadi tenaga mekanis', 'b' => 'Merubah tenaga mekanis menjadi tenaga kinetis', 'c' => 'Mengarahkan aliran oli ke stator', 'd' => 'Merubah tenaga engine sesuai dengan bebannya'],
            ],
            [
                'number' => 29,
                'text' => 'Berikut elemen dari planetary gear, kecuali:',
                'options' => ['a' => 'Helical Gear', 'b' => 'Planet Carrier', 'c' => 'Sun Gear', 'd' => 'Ring Gear'],
            ],
            [
                'number' => 30,
                'text' => 'Bekerjanya damper adalah saat:',
                'options' => ['a' => 'Dari putaran rendah ke putaran tinggi', 'b' => 'Dari putaran tinggi ke putaran rendah', 'c' => 'Saat torque converter stall', 'd' => 'Saat terjadi perubahan beban'],
            ],
            [
                'number' => 31,
                'text' => 'Steering system adalah suatu sistem pengendalian unit yang digunakan untuk:',
                'options' => ['a' => 'Merubah arah gerak/jalan unit menjadi ke kiri atau ke kanan', 'b' => 'Merubah kecepatan gerak/jalan unit menjadi lebih cepat atau lambat', 'c' => 'Merubah arah dan kecepatan gerak/jalan unit pada salah satu sisi kiri atau kanan', 'd' => 'Menghentikan arah dan kecepatan gerak/jalan unit pada salah satu sisi kiri atau kanan'],
            ],
            [
                'number' => 32,
                'text' => 'Steering system yang digunakan pada crawler machine prinsip kerjanya adalah:',
                'options' => ['a' => 'Menghentikan putaran pada kedua sisi crawler-nya', 'b' => 'Merubah sudut jalan pada salah satu sisi crawler-nya', 'c' => 'Menghentikan putaran pada salah satu sisi crawler-nya', 'd' => 'Merubah arah putaran pada salah satu sisi crawler-nya'],
            ],
            [
                'number' => 33,
                'text' => 'Sistem rem (brake system) adalah suatu sistem pengendalian unit yang digunakan untuk:',
                'options' => ['a' => 'Merubah arah dan kecepatan gerak unit pada salah satu sisi kiri atau kanan', 'b' => 'Merubah kecepatan gerak unit menjadi lebih cepat atau lambat', 'c' => 'Merubah arah gerak unit menjadi ke kiri atau ke kanan', 'd' => 'Memperlambat dan menghentikan gerak unit'],
            ],
            [
                'number' => 34,
                'text' => 'Pada alat berat bulldozer dan dozer shovel, tipe steering yang digunakan adalah system:',
                'options' => ['a' => 'Clutch', 'b' => 'Articulated', 'c' => 'Rod & linkage', 'd' => 'Follow up linkage'],
            ],
            [
                'number' => 35,
                'text' => 'Komponen yang berfungsi merubah air pressure menjadi gerakan mekanik untuk menekan oli yang ada di slack adjuster guna pengoperasian brake adalah:',
                'options' => ['a' => 'Brake Chamber', 'b' => 'Parking Brake', 'c' => 'Air Compressor', 'd' => 'Air Governor'],
            ],
            [
                'number' => 36,
                'text' => 'Fungsi utama floating seal pada final drive adalah:',
                'options' => ['a' => 'Mencegah tekanan udara yang berlebihan di dalam final drive case', 'b' => 'Mencegah kebocoran oli di dalam final drive', 'c' => 'Mencegah kotoran masuk ke dalam final drive', 'd' => 'Mencegah kebocoran oli dan kotoran masuk ke dalam final drive'],
            ],
            [
                'number' => 37,
                'text' => 'Komponen undercarriage yang berfungsi untuk menahan bagian atas dari gulungan track adalah:',
                'options' => ['a' => 'Carrier roller', 'b' => 'Track Roller', 'c' => 'Front Idler', 'd' => 'Sprocket'],
            ],
            [
                'number' => 38,
                'text' => 'Pengencang track dan penahan benturan adalah fungsi dari:',
                'options' => ['a' => 'Carrier roller', 'b' => 'Track frame', 'c' => 'Front idler', 'd' => 'Bogey'],
            ],
            [
                'number' => 39,
                'text' => 'Undercarriage yang dilengkapi dengan rubber bushing dan rubber pad termasuk ke dalam klasifikasi:',
                'options' => ['a' => 'Rigid type', 'b' => 'Semi Rigid Type', 'c' => 'Boogie type', 'd' => 'Semi Rigid type dan Boogie type'],
            ],
            [
                'number' => 40,
                'text' => 'Oli pelumas yang terdapat pada track roller berfungsi untuk:',
                'options' => ['a' => 'Mengurangi keausan antara link dan track roller', 'b' => 'Mengurangi keausan antara link dan bushing', 'c' => 'Mengurangi keausan antara bushing dan shaft', 'd' => 'Mengurangi keausan antara track roller dan bushing'],
            ],
            [
                'number' => 41,
                'text' => 'Sifat-sifat zat cair/fluida adalah:',
                'options' => ['a' => 'Tidak dapat dimampatkan (uncompressible)', 'b' => 'Bentuknya selalu berubah sesuai dengan tempatnya', 'c' => 'Mengalir dari tekanan yang lebih tinggi ke yang lebih rendah', 'd' => 'Semua jawaban diatas benar'],
            ],
            [
                'number' => 42,
                'text' => 'Yang berfungsi untuk mengatur jumlah aliran oli yang akan masuk ke actuator adalah:',
                'options' => ['a' => 'Pressure valve', 'b' => 'Directional valve', 'c' => 'Flow valve', 'd' => 'Shuttle valve'],
            ],
            [
                'number' => 43,
                'text' => 'Lubang kecil yang terdapat dalam pipa/saluran untuk mempersempit aliran zat cair/fluida sehingga tekanan setelah orifice akan turun disebut:',
                'options' => ['a' => 'Clamp', 'b' => 'O-Ring', 'c' => 'Orifice', 'd' => 'Hose'],
            ],
            [
                'number' => 44,
                'text' => 'Tempat penampungan/penyediaan oli dan pendinginan oli yang kembali dari sistem, hydraulic tank terbagi menjadi dua yaitu pressurized dan unpressurized disebut:',
                'options' => ['a' => 'Hydraulic Filter', 'b' => 'Hydraulic Line', 'c' => 'Hydraulic Tank', 'd' => 'Hydraulic Actuator'],
            ],
            [
                'number' => 45,
                'text' => 'Secara garis besar valve terbagi dalam:',
                'options' => ['a' => 'Pressure valve, safety valve dan orbitrol valve', 'b' => 'Pressure valve, flow valve dan directional valve', 'c' => 'Flow valve, directional valve dan demand valve', 'd' => 'Jawaban a, b, dan c benar'],
            ],
            [
                'number' => 46,
                'text' => 'Gaya yang menyebabkan terjadinya arus listrik atau beda potensial disebut:',
                'options' => ['a' => 'Hambatan', 'b' => 'Daya', 'c' => 'Tegangan', 'd' => 'Kuat Arus'],
            ],
            [
                'number' => 47,
                'text' => 'Sebuah reaksi kimia antara dua buah plat timbal yang berbeda sifat kimia dan terendam dalam larutan elektrolit disebut:',
                'options' => ['a' => 'Starting Motor', 'b' => 'Resistor', 'c' => 'Battery', 'd' => 'Alternator'],
            ],
            [
                'number' => 48,
                'text' => 'Komponen-komponen utama yang bukan termasuk dalam charging system adalah:',
                'options' => ['a' => 'Circuit Breaker', 'b' => 'Oil Cooler', 'c' => 'Battery', 'd' => 'Alternator'],
            ],
            [
                'number' => 49,
                'text' => 'Komponen-komponen utama yang bukan termasuk dalam starting system adalah:',
                'options' => ['a' => 'Battery', 'b' => 'Switch', 'c' => 'Radiator', 'd' => 'Relay'],
            ],
            [
                'number' => 50,
                'text' => 'Pada preheating system untuk membatasi over current (arus berlebihan) digunakan:',
                'options' => ['a' => 'Fuse', 'b' => 'Heater relay', 'c' => 'Circuit breaker', 'd' => 'Heater signal'],
            ],
        ];
    }
}
