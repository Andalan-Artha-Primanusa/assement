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
            QuestionPackageSeeder::MECHANIC_M1,
            QuestionPackageSeeder::MECHANIC_M2,
            QuestionPackageSeeder::MECHANIC_M3,
            QuestionPackageSeeder::OPERATOR,
        ])->get()->keyBy('name');

        $mechanicQuestions = $this->getQuestions();

        $half = (int) ceil(count($mechanicQuestions) / 3);

        $this->seedQuestions(
            $packages[QuestionPackageSeeder::MECHANIC_M1] ?? null,
            array_slice($mechanicQuestions, 0, $half),
            'mechanic'
        );

        $this->seedQuestions(
            $packages[QuestionPackageSeeder::MECHANIC_M2] ?? null,
            array_slice($mechanicQuestions, $half, $half),
            'mechanic'
        );

        $this->seedQuestions(
            $packages[QuestionPackageSeeder::MECHANIC_M3] ?? null,
            array_slice($mechanicQuestions, $half * 2),
            'mechanic'
        );

        $this->seedQuestions(
            $packages[QuestionPackageSeeder::OPERATOR] ?? null,
            $this->parseQuestions($this->autoElectricianQuestions()),
            'operator'
        );
    }

    /**
     * @param  array<int, array{number:int,text:string,options:array{a:string,b:string,c:string,d:string}}>  $questions
     */
    private function seedQuestions(?QuestionPackage $package, array $questions, string $type): void
    {
        foreach ($questions as $q) {
            $category = $this->categoryFor($type, $q['number']);
            $difficulty = $this->difficultyFor($type, $q['number']);

            Question::updateOrCreate(
                [
                    'question_package_id' => $package?->id,
                    'text' => $q['text'],
                ],
                [
                    'question_package_id' => $package?->id,
                    'category' => $category,
                    'difficulty' => $difficulty,
                    'option_a' => $q['options']['a'],
                    'option_b' => $q['options']['b'],
                    'option_c' => $q['options']['c'],
                    'option_d' => $q['options']['d'],
                    'correct_option' => $this->answerKeyFor($type, $q['number']),
                    'is_active' => true,
                ]
            );
        }
    }

    private function answerKeyFor(string $type, int $number): string
    {
        if ($type === 'mechanic') {
            return $this->mechanicAnswerKey()[$number] ?? 'a';
        }

        return 'a';
    }

    private function mechanicAnswerKey(): array
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

    private function categoryFor(string $type, int $number): string
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

    private function difficultyFor(string $type, int $number): string
    {
        return match (true) {
            $number <= 15 => 'basic',
            $number <= 35 => 'intermediate',
            default => 'advanced',
        };
    }

    /**
     * @return array<int, array{number:int,text:string,options:array{a:string,b:string,c:string,d:string}}>
     */
    private function parseQuestions(string $source): array
    {
        preg_match_all('/(?:^|\n)\s*(\d+)\.\s*(.*?)(?=(?:\n\s*\d+\.\s)|\z)/s', $source, $blocks, PREG_SET_ORDER);

        return collect($blocks)
            ->map(function (array $block): ?array {
                $number = (int) $block[1];
                $body = trim(preg_replace('/[ \t]+/', ' ', $block[2]));
                $body = trim(preg_replace('/\s+/', ' ', $body));

                preg_match_all('/(^|\s)([abcd])\.\s+/i', $body, $markers, PREG_OFFSET_CAPTURE);

                if (count($markers[0]) < 4) {
                    return null;
                }

                $firstOptionOffset = $markers[0][0][1];
                $questionText = trim(substr($body, 0, $firstOptionOffset));
                $options = [];

                foreach ($markers[0] as $index => $marker) {
                    $label = strtolower($markers[2][$index][0]);
                    $start = $marker[1] + strlen($marker[0]);
                    $end = isset($markers[0][$index + 1])
                        ? $markers[0][$index + 1][1]
                        : strlen($body);

                    $options[$label] = trim(substr($body, $start, $end - $start), " \t\n\r\0\x0B.");
                }

                if ($questionText === '' || count(array_intersect_key($options, array_flip(['a', 'b', 'c', 'd']))) < 4) {
                    return null;
                }

                return [
                    'number' => $number,
                    'text' => trim($questionText, " \t\n\r\0\x0B."),
                    'options' => [
                        'a' => $options['a'],
                        'b' => $options['b'],
                        'c' => $options['c'],
                        'd' => $options['d'],
                    ],
                ];
            })
            ->filter()
            ->values()
            ->all();
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

    private function autoElectricianQuestions(): string
    {
        return <<<'QUESTIONS'
1. Bagian terkecil dari suatu atom adalah
a. Elektron
b. Proton
c. Neutron
d. Mikron

2. Elektron yang terdapat pada kulit paling luar disebut
a. Variabel
b. Outer
c. Neutron
d. Valensi

3. Bahan semikonduktor adalah bahan yang atomnya memiliki
a. Elektron lebih dari 4
b. Elektron kurang dari 4
c. Semua salah
d. Elektron terluar sama dengan 4

4. Yang mempunyai muatan negatif adalah
a. Proton
b. Neutron
c. Elektron
d. Semua salah

5. Jumlah muatan listrik yang mengalir melalui suatu titik tertentu selama satu detik adalah pengertian dari
a. Tegangan
b. Daya
c. Arus
d. Coulomb

6. Satuan dari Arus listrik adalah
a. Coulomb
b. Watt
c. Ampere
d. Jawaban a dan b, benar

7. Rumus untuk menghitung tegangan adalah
a. V = I / R
b. V = I2 x R
c. V = R / I
d. V = I x R

8. Arus yang mengalir dalam polaritas yang tetap adalah
a. 5,2 mm
b. 5,25 mm
c. 12,2 mm
d. 12,25 mm

9. Alat yang digunakan untuk memegang atau membatasi gerakan dari pin atau shaft adalah
a. Alternating Current
b. Arus Diam
c. State Current
d. Direct Current

10. Satuan tenaga listrik dinyatakan dengan
a. Ampere
b. Power
c. Joule
d. Watt

11. Satu Horsepower sama dengan
a. 746 Watt
b. 735 Watt
c. 746 Kilowatt
d. 735 Kilowatt

12. Makin besar hambatan listrik pada penghantar, maka
a. Semakin kecil arus yang mengalir
b. Semakin besar arus yang mengalir
c. Tidak ada perubahan arus
d. Semua jawaban salah

13. Sumber energi listrik utama pada unit adalah
a. Kondensator
b. Accumulator
c. Alternator
d. Engine

14. Battery dapat dibedakan berdasarkan kontruksinya yaitu
a. Baterai Compound dan Baterai Solid
b. Baterai Basah dan Baterai Kering
c. Baterai Keras dan Baterai Lunak
d. Baterai Besar dan Baterai Kecil

15. Battery dapat dibedakan berdasarkan tipenya yaitu
a. Baterai Compound dan Baterai Solid
b. Baterai Keras dan Baterai Lunak
c. Baterai Besar dan Baterai Kecil
d. Baterai Basah dan Baterai Kering

16. Mencegah masuknya debu dan kotoran ke dalam sel baterai adalah fungsi dari
a. Blind plug
b. Vent plug
c. Bracket
d. Housing

17. Standard berat jenis (specific gravity) elektrolit batterai pada temperature standard (200 Celsius) adalah
a. 1,42
b. 1,35
c. 1,20
d. 1,28

18. Bahan utama plat positif dan plat negatif pada baterai adalah
a. Aluminium
b. Besi
c. Tembaga
d. Timbal

19. Perubahan berat jenis elektrolit dipengaruhi oleh
a. Discharge rate
b. Charge rate
c. Semua jawaban benar
d. Temperature

20. Larutan elektrolit merupakan
a. Asam sulfat
b. Air
c. Semua jawaban benar
d. Hasil campuran asam sulfat dan air

21. Terminal Voltage adalah
a. Batas tegangan baterai yang diizinkan saat discharging
b. Batas tegangan baterai yang diizinkan saat discharging dan recharging
c. Semua jawaban salah
d. Batas tegangan baterai yang diizinkan saat recharging

22. Self discharge bisa terjadi karena
a. Penguapan
b. Kebocoran udara
c. Reaksi kimia
d. Baterai bocor

23. Hal-hal yang mempengaruhi efektifitas charging battery di unit, kecuali
a. Temperatur baterai
b. Kapasitas baterai
c. Kondisi plat pada baterai
d. Kebersihan elektrolit

24. Jumlah listrik yang dapat dihasilkan dengan melepaskan arus tetap sampai dicapai voltage akhir merupakan penjelasan dari
a. Arus baterai
b. Tegangan baterai
c. Kapasitas baterai
d. Kehandalan baterai

25. Penyebab semakin cepatnya kerusakan baterai
a. Semua jawaban benar
b. Level elektrolit rendah
c. Overcharging
d. Korosi pada terminal baterai

26. Mencegah kerusakan komponen karena short circuit dan dapat digunakan berulang kali adalah fungsi dari
a. Circuit Breaker
b. Temperatur baterai
c. Switch
d. Contactor

27. Mencegah kerusakan komponen karena short circuit dan hanya dapat digunakan satu kali adalah fungsi dari
a. Fuse
b. Circuit Breaker
c. Switch
d. Contactor

28. Fungsi dari switch adalah untuk
a. Memutuskan
b. Memutus dan menghubungkan
c. Menghubungkan
d. Mengisolasi

29. Jenis-jenis resistor, kecuali
a. Resistor keramik
b. Resistor tetap
c. Resistor variabel
d. Resistor non linier

30. Resistor 4 gelang dengan nilai 390 Ohm dan toleransi 5% memiliki warna
a. Jingga, cokelat, putih, emas
b. Jingga, putih, hitam, emas
c. Putih, jingga, hitam, emas
d. Jingga, putih, cokelat, emas

31. Jenis rangkaian dasar listrik, kecuali
a. Rangkaian bertingkat
b. Rangkaian seri
c. Rangkaian seri paralel
d. Rangkaian paralel

32. Besi yang dibuat menjadi magnet dengan cara tertentu disebut
a. Permanen magnet
b. Magnet alami
c. Magnet buatan
d. Remanen magnet

33. Medan magnet yang ditimbulkan akibat adanya arus pada sebuah konduktor disebut
a. Radiasi magnet
b. Remanen magnet
c. Arus magnet
d. Elektromagnet

34. Fungsi rotor coil pada alternator adalah
a. Pembangkit medan magnet
b. Menghasilkan arus listrik
c. Mengubah garis gaya magnet
d. Menjaga arus listrik tetap mengalir

35. Alat yang bekerja dengan cara merubah garis - garis gaya magnet yang memotong lilitan menjadi tenaga listrik disebut
a. Generator
b. Transformator
c. Alternator
d. Motor listrik

36. Multimeter dapat digunakan sebagai
a. Volt meter
b. Ampere meter
c. Ohm meter
d. Semua jawaban benar

37. Untuk mengukur spesifik grafity dari baterai, menggunakan
a. Hydrometer
b. Flowmeter
c. AVO meter
d. Ohm meter

38. Tool yang berfungsi untuk melekatkan kabel pada konektor, disebut
a. Clamp
b. Driver
c. Crimping
d. Socket

39. Komponen - komponen utama yang termasuk dalam starting system
a. Baterai, starting switch, baterai relay, starting motor, safety relay, alternator
b. Baterai, starting switch, baterai relay, starting motor, safety relay
c. Baterai, starting switch, baterai telay, fuse, staring motor
d. Baterai, starting swich, starting motor

40. Memutuskan ataupun menghubungkan komponen-komponen dalam starting sistem adalah fungsi
a. Connector
b. Starting motor
c. Starting switch
d. Baterai relay

41. Jenis baterai relay
a. Positif baterai relay
b. Negatif baterai relay
c. Semua jawaban salah
d. Jawaban a dan b, benar

42. Berikut adalah gambar dari
a. Baterai relay
b. Safety relay
c. Starting motor
d. Alternator

43. Sistem yang berfungsi mengisi battery agar full charge adalah
a. Refil sistem
b. Alternating sistem
c. Charging sistem
d. Reuse sistem

44. Sistem untuk memanaskan udara yang akan masuk ke ruang bakar dengan tujuan mempermudah menghidupkan engine pada waktu udara sekeliling engine masih dingin disebut
a. Warming
b. Starting
c. Preheating
d. Cooling

45. Komponen yang termasuk dalam preheating, kecuali
a. Glow plug
b. Brush
c. APS
d. Thermostat

46. Kode warna LgY menunjukan bahwa kabel tersebut berwarna
a. Kuning
b. Hijau muda
c. Hijau muda dan kuning
d. Hijau dan kuning

47. Jenis resistor yang tahanannya dapat diubah-ubah yang diakibatkan oleh pengaruh suhu (temperatur) adalah
a. LDR
b. Resistor variabel
c. Thermistor
d. Potentiometer

48. Komponen elektronika yang mempunyai sifat dapat menyimpan muatan listrik adalah
a. Transistor
b. Kapasitor
c. Resistor
d. Dioda

49. Light Dependent Resistor merupakan jenis resistor yang perubahan resistansinya ditentukan oleh
a. Suhu
b. Tekanan
c. Cahaya
d. Tegangan

50. Komponen elektronika yang merupakan pertemuan junction antara material P dan N adalah
a. Resistor
b. Kapasitor
c. Transistor
d. Trimpot
QUESTIONS;
    }

    private function tyremanQuestions(): string
    {
        return <<<'QUESTIONS'
1. Berikut ini yang bukan merupakan 4 fungsi ban adalah
a. Mengendalikan beban Unit
b. Menyangga beban
c. Menyerap Guncangan
d. Meneruskan pengereman & traksi kepermukaan jalan

2. Jika di area sidewall tyre terdapat tulisan 27.00R49 merupakan gambaran dimensi fisik ban meliputi keterangan berikut ini, kecuali
a. Section width
b. Tyre construction
c. Rim diameter
d. Section height

3. Menurut type konstruksinya tyre dibagi menjadi 2 yaitu
a. Tube type dan tubeless
b. Pneumatic dan Solid
c. Tubeless dan radial
d. Radial dan Bias

4. Berdasarkan type penyimpanan anginya ban dibagi menjadi 2 yaitu
a. Radial dan Bias
b. Pneumatic dan Solid
c. Tube type dan tubeless
d. Tubeless dan radial

5. Manakah pernyataan berikut merupakan fungsi dari casing ply
a. Menahan ban duduk di Rim
b. Pelindung wire terhadap gesekan dengan rim
c. Menahan beban dan tekanan udara
d. Penerus torsi, daya pengereman danmencengram jalan

6. Serial number tyre B8S000987, arti dari huruf S dari serial number tersebut adalah
a. Tahun Pembuatan
b. Tanggal Pembuatan
c. Bulan Pembuatan
d. Kota pembuatan

7. Dibawah ini adalah bagian structural tyre,kecuali :
a. Casing
b. Belt 4
c. Bead bundle/wire
d. Bead chaffer

8. Apakah yang di maksud dengan bagian struktural tyre:
a. Bagian pendukung pada tyre
b. Bagian telapak pada tyre
c. Bagian inner linner
d. Bagian yang memiliki fungsi utama pada tyre

9. Manakah di bawah ini yang termasuk bagian-bagian bead
a. Bead flange
b. Bead toe
c. Bead heel
d. Semua benar

10. Aktifitas pemeriksaaan kondisi tyre secara rutin disebut
a. Disposisi Tyre
b. Washing Tyre
c. Receiving Tyre
d. Inspeksi tyre

11. Berapa standar minimal jarak radius yang diizinkan ketika melakukan pengisian tyre
a. 1 meter
b. 2 meter
c. 3 meter
d. 4 meter

12. Alat yang di gunakan sebagai tempat untuk menempatkan tyre yang dapat berputar ,naik dan turundisebut ?
a. Tyre stand
b. Tyre Changer
c. Tyre lever
d. Tyre cage

13. Alat yang di gunakan sebagai tempat untuk menempatkan tyre yang sedang dilakukan pengisian angin disebut ?
a. Tyre Changer
b. Tyre cage
c. Tyre stand
d. Tyre lever

14. Berikut ini merupakan fungsi regulator pada air line system adalah
a. Mengatur tekanan udara
b. Menyaring air
c. Memisahkan air
d. Menyaring partikel kotoran

15. Berikut ini merupakan tindakan yang tepat saat anda bekerja kemudian alat yang anda gunakanmengalami kerusakan
a. Tetap memaksa bekerja agar produksitercapai
b. Meminjam peralatan rekan kerja yg lain
c. Stop pekerjaan,lapor GL,Perbaiki sendiri
d. Stop Pekerjaan,Pasang TAG peralatan rusak,& Lapor atasan

16. Berikut ini merupakan ukuran ban yang sesuai dengan unitnya adalah
a. 27.00 R49 (HD 465)
b. 23.5 R25 (GR825)
c. 24.00 R49 (HD 785)
d. 29.5 R29 (HM 400)

17. Peralatan yang digunakan untuk menahan dan mengangkat posisi tyre adalah
a. Tyre Inflator
b. Curing Tools
c. Suporting Tools
d. Tyre Handler

18. Peralatan yang digunakan untuk mengukur kedalaman thread tyre disebut
a. Tyre Lever
b. Core Remover
c. Pressure gauge
d. Thread depth gauge

19. Peralatan yang digunakan untuk mengetahui atau mengecek tekanan angin dalam tyre disebut
a. Thread Depth Gauge
b. Dial Gauge
c. Pressure Gauge
d. Vernier Caliper

20. Keausan tyre sampai pada batas limit TUR (Keausan Normal)
a. Cut/Separation
b. O-Ring Problem
c. Impact
d. Smooth

21. Fungsi dari rock ejector adalah
a. Pemisah antara ban satu dengan yang lain
b. Pembersih kotoran atau batu yang terjepit
c. Penyeimbang Unit
d. Semua jawaban salah

22. Terpisahnya lapisan belt pada tread
a. Tread Lifting
b. Seized Brake
c. Tread Separation
d. Foreign Object

23. Coel kecil dalam jumlah yang banyak di daerah tread
a. Stone Drilling
b. Tread Chiping
c. Soulder Wear
d. Center Wear

24. Keretakan di area sidewall akibat dari under pressure
a. Tread Chunking
b. Radial Crack
c. Radial Crack
d. Pre Worn

25. Terpisahnya lapisan tyre karena panas berlebih bukan karena terpotong
a. Heat Separation
b. Worn To Ply
c. Run Flat
d. Bead Faique
QUESTIONS;
    }
}
