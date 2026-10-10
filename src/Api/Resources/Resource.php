<?php

namespace PontoConnect\Api\Resources;

use Closure;
use Generator;
use PontoConnect\Api\PontoConnectApiClient;
use PontoConnect\Data\Page;
use Saloon\Http\Request;

abstract class Resource
{
    public const MAXIMUM_PAGE_SIZE = 100;

    public function __construct(
        protected PontoConnectApiClient $connector,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    protected function sendAndDecode(Request $request): array
    {
        return $this->connector->send($request)->json();
    }

    /**
     * @return array<string, mixed>
     */
    protected function sendAndDecodeData(Request $request): array
    {
        $data = $this->sendAndDecode($request)['data'] ?? [];
        if (! is_array($data)) {
            return [];
        }

        return $data;
    }

    /**
     * Lazily walk every page, fetching the next one only when needed.
     *
     * @template TItem
     *
     * @param Closure(string|null): Page<TItem> $fetchPageAfter
     *
     * @return Generator<int, TItem>
     */
    protected function iteratePages(Closure $fetchPageAfter): Generator
    {
        $cursor = null;

        do {
            $page = $fetchPageAfter($cursor);

            foreach ($page->items as $item) {
                yield $item;
            }

            $cursor = $page->nextCursor;
        } while ($cursor !== null && ! $page->isEmpty());
    }
}
