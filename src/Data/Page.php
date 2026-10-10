<?php

namespace PontoConnect\Data;

use ArrayIterator;
use Closure;
use Countable;
use DateTimeImmutable;
use IteratorAggregate;
use PontoConnect\Support\DateParser;
use Traversable;

/**
 * One page of a cursor-paginated list.
 *
 * Pass `nextCursor` as `$after` to fetch the next page and
 * `previousCursor` as `$before` to fetch the previous one.
 *
 * @template TItem
 *
 * @implements IteratorAggregate<int, TItem>
 */
final class Page implements Countable, IteratorAggregate
{
    /**
     * @param list<TItem> $items
     */
    public function __construct(
        public readonly array $items,
        public readonly ?string $nextCursor = null,
        public readonly ?string $previousCursor = null,
        public readonly ?int $limit = null,
        public readonly ?DateTimeImmutable $synchronizedAt = null,
        public readonly ?Synchronization $latestSynchronization = null,
    ) {
    }

    /**
     * @template TMapped
     *
     * @param array<string, mixed> $body
     * @param Closure(array<string, mixed>): TMapped $mapItem
     *
     * @return self<TMapped>
     */
    public static function fromResponse(array $body, Closure $mapItem): self
    {
        $items = [];
        foreach ($body['data'] ?? [] as $resource) {
            $items[] = $mapItem($resource);
        }

        $paging = $body['meta']['paging'] ?? [];

        $limit = null;
        if (isset($paging['limit'])) {
            $limit = (int) $paging['limit'];
        }

        $latestSynchronization = null;
        if (isset($body['meta']['latestSynchronization']) && is_array($body['meta']['latestSynchronization'])) {
            $latestSynchronization = Synchronization::fromArray($body['meta']['latestSynchronization']);
        }

        return new self(
            items: $items,
            nextCursor: self::cursorFromLink($body['links']['next'] ?? null, 'after'),
            previousCursor: self::cursorFromLink($body['links']['prev'] ?? null, 'before'),
            limit: $limit,
            synchronizedAt: DateParser::parse($body['meta']['synchronizedAt'] ?? null),
            latestSynchronization: $latestSynchronization,
        );
    }

    public function hasNextPage(): bool
    {
        return $this->nextCursor !== null;
    }

    public function hasPreviousPage(): bool
    {
        return $this->previousCursor !== null;
    }

    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    public function count(): int
    {
        return count($this->items);
    }

    /**
     * @return Traversable<int, TItem>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    /**
     * Read `page[after]` or `page[before]` from a pagination link.
     */
    private static function cursorFromLink(mixed $link, string $cursorName): ?string
    {
        if (! is_string($link) || $link === '') {
            return null;
        }

        $query = parse_url($link, PHP_URL_QUERY);
        if (! is_string($query)) {
            return null;
        }

        parse_str($query, $parameters);

        $cursor = $parameters['page'][$cursorName] ?? null;
        if (! is_string($cursor) || $cursor === '') {
            return null;
        }

        return $cursor;
    }
}
