<?php

namespace PontoConnect\Tests\Unit\Data;

use PHPUnit\Framework\TestCase;
use PontoConnect\Data\Page;

class PageTest extends TestCase
{
    public function test_reads_cursors_from_the_pagination_links(): void
    {
        $page = Page::fromResponse([
            'meta' => [
                'paging' => [
                    'limit' => 2,
                    'before' => '9089414a-77f8-46b2-b979-edcfad5c2218',
                    'after' => 'd500ddac-27e8-447b-b0a1-dbca8c181b99',
                ],
            ],
            'links' => [
                'first' => 'http://api.ibanity.com/ponto-connect/accounts?page[limit]=2',
                'prev' => 'http://api.ibanity.com/ponto-connect/accounts?page[limit]=2&page[before]=9089414a-77f8-46b2-b979-edcfad5c2218',
                'next' => 'http://api.ibanity.com/ponto-connect/accounts?page[limit]=2&page[after]=d500ddac-27e8-447b-b0a1-dbca8c181b99',
            ],
            'data' => [
                ['type' => 'account', 'id' => '9089414a-77f8-46b2-b979-edcfad5c2218'],
                ['type' => 'account', 'id' => 'd500ddac-27e8-447b-b0a1-dbca8c181b99'],
            ],
        ], fn (array $resource) => $resource['id']);

        $this->assertSame(['9089414a-77f8-46b2-b979-edcfad5c2218', 'd500ddac-27e8-447b-b0a1-dbca8c181b99'], $page->items);
        $this->assertSame('d500ddac-27e8-447b-b0a1-dbca8c181b99', $page->nextCursor);
        $this->assertSame('9089414a-77f8-46b2-b979-edcfad5c2218', $page->previousCursor);
        $this->assertSame(2, $page->limit);
        $this->assertTrue($page->hasNextPage());
        $this->assertTrue($page->hasPreviousPage());
        $this->assertCount(2, $page);
    }

    public function test_reads_url_encoded_cursors(): void
    {
        $page = Page::fromResponse([
            'links' => [
                'next' => 'https://api.ibanity.com/ponto-connect/accounts?page%5Bafter%5D=5a8820b5-87c5-4d8d-b0f1-32e710b0bd9b&page%5Blimit%5D=1',
            ],
            'data' => [],
        ], fn (array $resource) => $resource);

        $this->assertSame('5a8820b5-87c5-4d8d-b0f1-32e710b0bd9b', $page->nextCursor);
    }

    public function test_last_page_has_no_next_cursor(): void
    {
        $page = Page::fromResponse([
            'links' => ['first' => 'https://api.ibanity.com/ponto-connect/accounts?page[limit]=10'],
            'meta' => ['paging' => ['limit' => 10]],
            'data' => [],
        ], fn (array $resource) => $resource);

        $this->assertNull($page->nextCursor);
        $this->assertNull($page->previousCursor);
        $this->assertFalse($page->hasNextPage());
        $this->assertTrue($page->isEmpty());
    }
}
