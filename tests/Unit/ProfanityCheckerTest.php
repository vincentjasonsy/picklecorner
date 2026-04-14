<?php

namespace Tests\Unit;

use App\Services\ProfanityChecker;
use Tests\TestCase;

class ProfanityCheckerTest extends TestCase
{
    public function test_empty_text_is_clean(): void
    {
        $this->assertFalse(ProfanityChecker::containsProfanity(null));
        $this->assertFalse(ProfanityChecker::containsProfanity(''));
        $this->assertFalse(ProfanityChecker::containsProfanity('   '));
    }

    public function test_detects_configured_word_with_boundary(): void
    {
        $this->assertTrue(ProfanityChecker::containsProfanity('What the shit was that'));
        $this->assertFalse(ProfanityChecker::containsProfanity('Shitake mushrooms'));
    }
}
