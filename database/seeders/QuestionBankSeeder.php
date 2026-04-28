<?php

namespace Database\Seeders;

use App\Models\QuestionBankItem;
use Illuminate\Database\Seeder;

class QuestionBankSeeder extends Seeder
{
    public function run(): void
    {
        $distribution = [
            'Elementary' => ['Math' => 50, 'English' => 50, 'Science' => 50],
            'High School' => ['Math' => 34, 'English' => 33, 'Science' => 33],
            'Senior High School' => ['Math' => 20, 'English' => 20, 'Science' => 20],
        ];

        foreach ($distribution as $level => $subjects) {
            foreach ($subjects as $subject => $count) {
                for ($i = 1; $i <= $count; $i++) {
                    QuestionBankItem::create($this->buildItem($level, $subject, $i));
                }
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildItem(string $level, string $subject, int $index): array
    {
        $question = $this->buildQuestion($level, $subject, $index);

        return array_merge([
            'user_id' => null,
            'level' => $level,
            'subject' => $subject,
            'status' => 'Active',
        ], $question);
    }

    /**
     * @return array<string, string>
     */
    protected function buildQuestion(string $level, string $subject, int $index): array
    {
        return match ("{$level}|{$subject}") {
            'Elementary|Math' => $this->elementaryMath($index),
            'Elementary|English' => $this->elementaryEnglish($index),
            'Elementary|Science' => $this->elementaryScience($index),
            'High School|Math' => $this->highSchoolMath($index),
            'High School|English' => $this->highSchoolEnglish($index),
            'High School|Science' => $this->highSchoolScience($index),
            'Senior High School|Math' => $this->seniorHighMath($index),
            'Senior High School|English' => $this->seniorHighEnglish($index),
            'Senior High School|Science' => $this->seniorHighScience($index),
            default => $this->fallbackQuestion($level, $subject, $index),
        };
    }

    /**
     * @return array<string, string>
     */
    protected function elementaryMath(int $index): array
    {
        $a = 2 + ($index % 8);
        $b = 3 + (($index + 2) % 7);
        $answer = $a + $b;

        return $this->mcq(
            "What is {$a} + {$b}?",
            [$answer - 2, $answer, $answer + 1, $answer + 3],
            1,
            'DepEd K-6 Math: basic addition and number sense.'
        );
    }

    protected function elementaryEnglish(int $index): array
    {
        $words = [
            ['run', 'verb'], ['happy', 'adjective'], ['teacher', 'noun'], ['quickly', 'adverb'],
        ];
        [$word, $type] = $words[$index % count($words)];

        return $this->mcq(
            "What part of speech is the word \"{$word}\"?",
            ['noun', 'verb', 'adjective', 'adverb'],
            match ($type) {
                'noun' => 0, 'verb' => 1, 'adjective' => 2, default => 3
            },
            'DepEd K-6 English: identify common parts of speech.'
        );
    }

    protected function elementaryScience(int $index): array
    {
        $items = [
            ['Which sense organ is used for seeing?', ['Eyes', 'Ears', 'Nose', 'Tongue'], 0],
            ['Which part of a plant absorbs water from soil?', ['Leaf', 'Stem', 'Root', 'Flower'], 2],
            ['What do plants need to make food?', ['Sunlight', 'Plastic', 'Stone', 'Sand'], 0],
            ['Which state of matter has a fixed shape?', ['Liquid', 'Gas', 'Solid', 'Cloud'], 2],
        ];
        [$question, $options, $correct] = $items[$index % count($items)];

        return $this->mcq(
            $question,
            $options,
            $correct,
            'DepEd K-6 Science: body parts, plants, and states of matter.'
        );
    }

    protected function highSchoolMath(int $index): array
    {
        $x = 2 + ($index % 6);
        $b = 5 + (($index + 1) % 5);
        $answer = $x + $b;

        return $this->mcq(
            "Solve for x: x + {$b} = ".($x + $b),
            [$x - 1, $x, $x + 1, $x + 2],
            1,
            'DepEd JHS Math: linear equations in one variable.'
        );
    }

    protected function highSchoolEnglish(int $index): array
    {
        $items = [
            ['The students ___ preparing for the exam.', ['is', 'are', 'was', 'be'], 1],
            ['Choose the correct pronoun: Maria and ___ went to the library.', ['me', 'I', 'her', 'us'], 1],
            ['Identify the correct sentence.', ['He go to school.', 'She walks to school.', 'They walks to school.', 'I is happy.'], 1],
        ];
        [$question, $options, $correct] = $items[$index % count($items)];

        return $this->mcq(
            $question,
            $options,
            $correct,
            'DepEd JHS English: grammar and sentence correctness.'
        );
    }

    protected function highSchoolScience(int $index): array
    {
        $items = [
            ['What is the basic unit of life?', ['Atom', 'Cell', 'Tissue', 'Organ'], 1],
            ['Which process do plants use to make food?', ['Respiration', 'Photosynthesis', 'Digestion', 'Evaporation'], 1],
            ['What force pulls objects toward Earth?', ['Friction', 'Magnetism', 'Gravity', 'Tension'], 2],
        ];
        [$question, $options, $correct] = $items[$index % count($items)];

        return $this->mcq(
            $question,
            $options,
            $correct,
            'DepEd JHS Science: cells, energy processes, and forces.'
        );
    }

    protected function seniorHighMath(int $index): array
    {
        $a = 1 + ($index % 4);
        $b = 2 + (($index + 2) % 5);
        $result = $a * $b;

        return $this->mcq(
            "What is the product of {$a} and {$b}?",
            [$result - 2, $result, $result + 2, $result + 4],
            1,
            'DepEd SHS Math: algebraic reasoning and operations.'
        );
    }

    protected function seniorHighEnglish(int $index): array
    {
        $items = [
            ['Which sentence is in active voice?', ['The song was sung by Ana.', 'Ana sang the song.', 'The song is being sung.', 'The song had been sung.'], 1],
            ['Choose the best transition for contrast.', ['Similarly', 'However', 'Therefore', 'For example'], 1],
            ['Which statement is a claim?', ['Many people drink water daily.', 'Water is wet.', 'Schools should start later to improve student focus.', 'The room is quiet.'], 2],
        ];
        [$question, $options, $correct] = $items[$index % count($items)];

        return $this->mcq(
            $question,
            $options,
            $correct,
            'DepEd SHS English: writing conventions and argument structure.'
        );
    }

    protected function seniorHighScience(int $index): array
    {
        $items = [
            ['Which organelle is known as the powerhouse of the cell?', ['Nucleus', 'Mitochondrion', 'Ribosome', 'Golgi apparatus'], 1],
            ['In the water cycle, what is the process of water vapor turning into liquid?', ['Evaporation', 'Condensation', 'Sublimation', 'Precipitation'], 1],
            ['Which law explains that for every action there is an equal and opposite reaction?', ['Newton\'s First Law', 'Newton\'s Second Law', 'Newton\'s Third Law', 'Law of Inertia'], 2],
        ];
        [$question, $options, $correct] = $items[$index % count($items)];

        return $this->mcq(
            $question,
            $options,
            $correct,
            'DepEd SHS Science: biology, Earth science, and physics fundamentals.'
        );
    }

    protected function fallbackQuestion(string $level, string $subject, int $index): array
    {
        return $this->mcq(
            "{$subject} practice question {$index} for {$level}: choose the best answer.",
            ['Option A', 'Option B', 'Option C', 'Option D'],
            0,
            'General competency-aligned question.'
        );
    }

    /**
     * @param  array<int, int|string>  $options
     * @return array<string, string>
     */
    protected function mcq(string $questionText, array $options, int $correctIndex, string $source): array
    {
        $normalized = array_map(static fn ($value): string => (string) $value, $options);
        $letters = ['A', 'B', 'C', 'D'];

        return [
            'question_text' => $questionText,
            'option_a' => $normalized[0] ?? 'Option A',
            'option_b' => $normalized[1] ?? 'Option B',
            'option_c' => $normalized[2] ?? 'Option C',
            'option_d' => $normalized[3] ?? 'Option D',
            'correct_option' => $letters[$correctIndex] ?? 'A',
            'explanation' => 'Correct answer is based on the target competency for this item.',
            'source_competency' => $source,
        ];
    }
}
