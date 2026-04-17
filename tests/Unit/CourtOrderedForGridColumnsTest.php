<?php

namespace Tests\Unit;

use App\Models\Court;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CourtOrderedForGridColumnsTest extends TestCase
{
    #[Test]
    public function it_orders_outdoor_ascending_then_indoor_descending_by_name_number(): void
    {
        $courts = collect([
            new Court(['name' => 'Indoor 1', 'environment' => Court::ENV_INDOOR, 'sort_order' => 7]),
            new Court(['name' => 'Outdoor 3', 'environment' => Court::ENV_OUTDOOR, 'sort_order' => 2]),
            new Court(['name' => 'Indoor 5', 'environment' => Court::ENV_INDOOR, 'sort_order' => 3]),
            new Court(['name' => 'Outdoor 1', 'environment' => Court::ENV_OUTDOOR, 'sort_order' => 0]),
            new Court(['name' => 'Indoor 3', 'environment' => Court::ENV_INDOOR, 'sort_order' => 5]),
            new Court(['name' => 'Outdoor 2', 'environment' => Court::ENV_OUTDOOR, 'sort_order' => 1]),
            new Court(['name' => 'Indoor 4', 'environment' => Court::ENV_INDOOR, 'sort_order' => 4]),
            new Court(['name' => 'Indoor 2', 'environment' => Court::ENV_INDOOR, 'sort_order' => 6]),
        ]);

        $ordered = Court::orderedForGridColumns($courts)->pluck('name')->all();

        $this->assertSame([
            'Outdoor 1',
            'Outdoor 2',
            'Outdoor 3',
            'Indoor 5',
            'Indoor 4',
            'Indoor 3',
            'Indoor 2',
            'Indoor 1',
        ], $ordered);
    }
}
