<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

trait ReordersRecords
{
    /**
     * Persist a drag-and-drop ordering: sort_order is set to each id's position
     * in the submitted list (offset by the paginator's starting position, so a
     * reordered page 2 continues after page 1). Wrapped in a transaction so a
     * partial save can't scramble the order.
     *
     * @param  class-string<Model>  $modelClass
     * @param  array<int, int|string>  $ids
     */
    protected function persistOrder(string $modelClass, array $ids, int $offset = 0): void
    {
        DB::transaction(function () use ($modelClass, $ids, $offset): void {
            foreach ($ids as $position => $id) {
                $modelClass::whereKey($id)->update(['sort_order' => $offset + $position]);
            }
        });
    }

    /**
     * The sort_order that places a new record at the end of the list.
     *
     * @param  class-string<Model>  $modelClass
     */
    protected function nextSortOrder(string $modelClass): int
    {
        return ($modelClass::max('sort_order') ?? -1) + 1;
    }
}
