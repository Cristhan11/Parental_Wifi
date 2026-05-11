<?php

namespace Tests\Unit;

use App\Models\DictionaryWord;
use App\Services\VideoWordService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VideoWordServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_validate_words_passes_when_order_and_length_match(): void
    {
        $service = new VideoWordService;
        $result = $service->validateWords(
            ['Apple', 'Banana'],
            ['apple', ' banana '],
        );

        $this->assertTrue($result['passed_validation']);
        $this->assertSame(2, $result['words_correct']);
        $this->assertSame(2, $result['words_shown_count']);
        $this->assertSame(2, $result['words_entered_count']);
    }

    public function test_validate_words_fails_when_wrong_order(): void
    {
        $service = new VideoWordService;
        $result = $service->validateWords(
            ['Apple', 'Banana'],
            ['Banana', 'Apple'],
        );

        $this->assertFalse($result['passed_validation']);
        $this->assertSame(0, $result['words_correct']);
    }

    public function test_validate_words_fails_when_wrong_length(): void
    {
        $service = new VideoWordService;
        $result = $service->validateWords(
            ['Apple', 'Banana'],
            ['Apple', 'Banana', 'Cherry'],
        );

        $this->assertFalse($result['passed_validation']);
    }

    public function test_validate_words_fails_when_extra_words_would_have_passed_under_set_logic(): void
    {
        $service = new VideoWordService;
        $result = $service->validateWords(
            ['Apple', 'Banana'],
            ['Apple', 'Banana', 'Cherry', 'Date'],
        );

        $this->assertFalse($result['passed_validation']);
    }

    public function test_validate_words_fails_when_empty_shown(): void
    {
        $service = new VideoWordService;
        $result = $service->validateWords([], ['Apple']);

        $this->assertFalse($result['passed_validation']);
        $this->assertSame(0, $result['words_shown_count']);
    }

    public function test_select_distractor_words_excludes_ids_and_respects_limit(): void
    {
        $w1 = DictionaryWord::create(['word' => 'alpha', 'definition' => 'a', 'is_built_in' => false, 'user_id' => null]);
        $w2 = DictionaryWord::create(['word' => 'beta', 'definition' => 'b', 'is_built_in' => false, 'user_id' => null]);
        $w3 = DictionaryWord::create(['word' => 'gamma', 'definition' => 'c', 'is_built_in' => false, 'user_id' => null]);
        $w4 = DictionaryWord::create(['word' => 'delta', 'definition' => 'd', 'is_built_in' => false, 'user_id' => null]);
        $w5 = DictionaryWord::create(['word' => 'epsilon', 'definition' => 'e', 'is_built_in' => false, 'user_id' => null]);
        $w6 = DictionaryWord::create(['word' => 'zeta', 'definition' => 'z', 'is_built_in' => false, 'user_id' => null]);

        $service = new VideoWordService;
        $distractors = $service->selectDistractorWords(5, [$w1->id, $w2->id]);

        $this->assertLessThanOrEqual(5, $distractors->count());
        $ids = $distractors->pluck('id')->all();
        $this->assertNotContains($w1->id, $ids);
        $this->assertNotContains($w2->id, $ids);
        foreach ($distractors as $row) {
            $this->assertNotSame($w1->id, $row->id);
            $this->assertNotSame($w2->id, $row->id);
        }
    }
}
