<?php

namespace PontoConnect\Api\Resources;

use Generator;
use PontoConnect\Api\Requests\Account\GetAccountRequest;
use PontoConnect\Api\Requests\Account\GetAccountsRequest;
use PontoConnect\Api\Requests\Account\RevokeAccountRequest;
use PontoConnect\Data\Account;
use PontoConnect\Data\Page;

/**
 * API reference, "Account". Requires a customer access token.
 */
class AccountResource extends Resource
{
    /**
     * @return Page<Account>
     */
    public function list(?int $limit = null, ?string $after = null, ?string $before = null): Page
    {
        return Page::fromResponse(
            $this->sendAndDecode(new GetAccountsRequest($limit, $after, $before)),
            fn (array $resource) => Account::fromArray($resource),
        );
    }

    /**
     * @return Generator<int, Account>
     */
    public function all(int $pageSize = self::MAXIMUM_PAGE_SIZE): Generator
    {
        return $this->iteratePages(fn (?string $after) => $this->list($pageSize, $after));
    }

    public function get(string $accountId): Account
    {
        return Account::fromArray($this->sendAndDecodeData(new GetAccountRequest($accountId)));
    }

    /**
     * Remove the account from the integration. The customer has to add it
     * again in Ponto (or in a new authorization flow) to restore access.
     */
    public function revoke(string $accountId): void
    {
        $this->connector->send(new RevokeAccountRequest($accountId));
    }
}
