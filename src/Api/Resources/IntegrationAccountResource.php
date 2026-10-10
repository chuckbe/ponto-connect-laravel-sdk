<?php

namespace PontoConnect\Api\Resources;

use Generator;
use PontoConnect\Api\Requests\IntegrationAccount\GetIntegrationAccountsRequest;
use PontoConnect\Data\IntegrationAccount;
use PontoConnect\Data\Page;

/**
 * API reference, "Integration Account". Requires a client access token.
 */
class IntegrationAccountResource extends Resource
{
    /**
     * @return Page<IntegrationAccount>
     */
    public function list(?int $limit = null, ?string $after = null, ?string $before = null): Page
    {
        return Page::fromResponse(
            $this->sendAndDecode(new GetIntegrationAccountsRequest($limit, $after, $before)),
            fn (array $resource) => IntegrationAccount::fromArray($resource),
        );
    }

    /**
     * @return Generator<int, IntegrationAccount>
     */
    public function all(int $pageSize = self::MAXIMUM_PAGE_SIZE): Generator
    {
        return $this->iteratePages(fn (?string $after) => $this->list($pageSize, $after));
    }
}
