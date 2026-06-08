<?php

namespace Database\Seeders;

use App\Models\Question;
use App\Models\QuestionPackage;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            AdminSeeder::class,
            QuestionPackageSeeder::class,
            UserTestingSeeder::class,
        ]);

        $packages = QuestionPackage::whereIn('name', [
            QuestionPackageSeeder::BASIC,
            QuestionPackageSeeder::POWER_TRAIN,
            QuestionPackageSeeder::HYDRAULIC_ELECTRICAL,
        ])->get()->keyBy('name');

        $questions = $this->questionsFromDocument();

        if ($questions === []) {
            $questions = $this->fallbackQuestions();
        }

        foreach ($questions as $question) {
            $package = $packages[$this->packageNameForNumber($question['number'])] ?? null;

            Question::updateOrCreate(
                ['text' => $question['text']],
                [
                    'question_package_id' => $package?->id,
                    'category' => $this->categoryForNumber($question['number']),
                    'difficulty' => $this->difficultyForNumber($question['number']),
                    'option_a' => $question['options']['a'],
                    'option_b' => $question['options']['b'],
                    'option_c' => $question['options']['c'],
                    'option_d' => $question['options']['d'],
                    'correct_option' => $this->answerKey()[$question['number']] ?? 'a',
                    'is_active' => true,
                ]
            );
        }
    }

    /**
     * The source Word file does not include an answer key, so these values are
     * best-effort starter keys and remain editable in the admin CMS.
     *
     * @return array<int, string>
     */
    private function answerKey(): array
    {
        return [
            1 => 'a',
            2 => 'd',
            3 => 'd',
            4 => 'd',
            5 => 'c',
            6 => 'c',
            7 => 'd',
            8 => 'a',
            9 => 'd',
            10 => 'd',
            11 => 'c',
            12 => 'a',
            13 => 'b',
            14 => 'a',
            15 => 'd',
            16 => 'c',
            17 => 'd',
            18 => 'd',
            19 => 'c',
            20 => 'd',
            21 => 'b',
            22 => 'c',
            23 => 'b',
            24 => 'c',
            25 => 'a',
            26 => 'a',
            27 => 'a',
            28 => 'b',
            29 => 'a',
            30 => 'd',
            31 => 'c',
            32 => 'c',
            33 => 'd',
            34 => 'a',
            35 => 'a',
            36 => 'd',
            37 => 'a',
            38 => 'c',
            39 => 'b',
            40 => 'c',
            41 => 'd',
            42 => 'c',
            43 => 'c',
            44 => 'c',
            45 => 'b',
            46 => 'c',
            47 => 'c',
            48 => 'b',
            49 => 'c',
            50 => 'c',
        ];
    }

    /**
     * @return array<int, array{number:int,text:string,options:array{a:string,b:string,c:string,d:string}}>
     */
    private function questionsFromDocument(): array
    {
        $path = storage_path('app/screening-mechanic.txt');

        if (! is_file($path)) {
            return [];
        }

        $text = file_get_contents($path) ?: '';
        $text = str_replace(["\r\n", "\r"], "\n", $text);

        preg_match_all('/(?:^|\n)\s*(\d+)\.\s*(.*?)(?=(?:\n\s*\d+\.\s)|\z)/s', $text, $blocks, PREG_SET_ORDER);

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

                    $options[$label] = trim(substr($body, $start, $end - $start));
                }

                if ($questionText === '' || count(array_intersect_key($options, array_flip(['a', 'b', 'c', 'd']))) < 4) {
                    return null;
                }

                return [
                    'number' => $number,
                    'text' => $questionText,
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

    private function packageNameForNumber(int $number): string
    {
        return match (true) {
            $number <= 20 => QuestionPackageSeeder::BASIC,
            $number <= 40 => QuestionPackageSeeder::POWER_TRAIN,
            default => QuestionPackageSeeder::HYDRAULIC_ELECTRICAL,
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

    /**
     * @return array<int, array{number:int,text:string,options:array{a:string,b:string,c:string,d:string}}>
     */
    private function fallbackQuestions(): array
    {
        return [
            [
                'number' => 1,
                'text' => 'Suatu usaha untuk dapat melaksanakan pekerjaan tanpa terjadi adanya suatu kecelakaan merupakan definisi dari:',
                'options' => [
                    'a' => 'Keselamatan kerja',
                    'b' => 'Kedisiplinan kerja',
                    'c' => 'Kesehatan kerja',
                    'd' => 'Ketelitian kerja',
                ],
            ],
        ];
    }
}
