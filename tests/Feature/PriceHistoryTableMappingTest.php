<?php

namespace Tests\Feature;

use App\Models\PriceHistory;
use Tests\TestCase;

/**
 * Regression: the property detail page eager-loads price history on every view.
 * The model must resolve to the `price_history` table created by migration
 * 2026_08_30_090029, otherwise Eloquent pluralizes to `price_histories` and the
 * page 500s with SQLSTATE[42S02].
 */
class PriceHistoryTableMappingTest extends TestCase
{
    public function test_model_maps_to_the_price_history_table(): void
    {
        $this->assertSame('price_history', (new PriceHistory)->getTable());
    }

    public function test_relation_builds_a_price_history_query(): void
    {
        $relation = (new PriceHistory)->newQuery()->getQuery();

        $this->assertSame('price_history', $relation->from);
    }
}