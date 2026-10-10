<?php

namespace PontoConnect\Api\Resources;

use Generator;
use PontoConnect\Api\Requests\FinancialInstitution\GetFinancialInstitutionRequest;
use PontoConnect\Api\Requests\FinancialInstitution\GetFinancialInstitutionsRequest;
use PontoConnect\Data\FinancialInstitution;
use PontoConnect\Data\Page;

/**
 * API reference, "Financial Institution".
 *
 * Without an access token every financial institution is returned; with a
 * customer access token only those of the organization's accounts.
 */
class FinancialInstitutionResource extends Resource
{
    /**
     * @param array<string, string|bool|array<string, string|bool|list<string>>> $filters e.g. ['country' => 'BE', 'name' => ['like' => 'kbc']]
     *
     * @return Page<FinancialInstitution>
     */
    public function list(?int $limit = null, ?string $after = null, ?string $before = null, array $filters = []): Page
    {
        return Page::fromResponse(
            $this->sendAndDecode(new GetFinancialInstitutionsRequest($limit, $after, $before, $filters)),
            fn (array $resource) => FinancialInstitution::fromArray($resource),
        );
    }

    /**
     * @param array<string, string|bool|array<string, string|bool|list<string>>> $filters
     *
     * @return Generator<int, FinancialInstitution>
     */
    public function all(array $filters = [], int $pageSize = self::MAXIMUM_PAGE_SIZE): Generator
    {
        return $this->iteratePages(fn (?string $after) => $this->list($pageSize, $after, null, $filters));
    }

    public function get(string $financialInstitutionId): FinancialInstitution
    {
        return FinancialInstitution::fromArray(
            $this->sendAndDecodeData(new GetFinancialInstitutionRequest($financialInstitutionId)),
        );
    }
}
