<?php

namespace Tests\Unit;

use App\Services\VenueBookingSpecsBuilder;
use PHPUnit\Framework\TestCase;

class VenueBookingSpecsBuilderTest extends TestCase
{
    public function test_each_court_must_be_contiguous(): void
    {
        $courtA = 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa';
        $this->assertTrue(VenueBookingSpecsBuilder::eachCourtHasOnlyContiguousHours([
            $courtA.'-9',
            $courtA.'-10',
            $courtA.'-11',
        ]));
        $this->assertFalse(VenueBookingSpecsBuilder::eachCourtHasOnlyContiguousHours([
            $courtA.'-9',
            $courtA.'-11',
        ]));
    }

    public function test_two_courts_can_each_have_their_own_block(): void
    {
        $courtA = 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa';
        $courtB = 'bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb';
        $this->assertTrue(VenueBookingSpecsBuilder::eachCourtHasOnlyContiguousHours([
            $courtA.'-9',
            $courtA.'-10',
            $courtB.'-14',
            $courtB.'-15',
        ]));
    }
}
