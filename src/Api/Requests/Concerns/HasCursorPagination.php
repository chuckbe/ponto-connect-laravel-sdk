<?php

namespace PontoConnect\Api\Requests\Concerns;

use InvalidArgumentException;

/**
 * Adds the `page[limit]`, `page[after]` and `page[before]` query parameters.
 * The using request must declare `$limit`, `$after` and `$before` properties.
 */
trait HasCursorPagination
{
    /**
     * @return array<string, int|string>
     */
    protected function paginationQuery(): array
    {
        $query = [];

        if ($this->limit !== null) {
            if ($this->limit < 1 || $this->limit > 100) {
                throw new InvalidArgumentException('The page limit must be between 1 and 100.');
            }

            $query['page[limit]'] = $this->limit;
        }

        if ($this->after !== null) {
            $query['page[after]'] = $this->after;
        }

        if ($this->before !== null) {
            $query['page[before]'] = $this->before;
        }

        return $query;
    }

    /**
     * @return array<string, int|string>
     */
    protected function defaultQuery(): array
    {
        return $this->paginationQuery();
    }
}
