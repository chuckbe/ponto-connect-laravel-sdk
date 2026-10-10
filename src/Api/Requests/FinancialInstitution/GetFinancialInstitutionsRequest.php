<?php

namespace PontoConnect\Api\Requests\FinancialInstitution;

use PontoConnect\Api\Requests\Concerns\HasCursorPagination;
use Saloon\Enums\Method;
use Saloon\Http\Request;

/**
 * List Financial Institutions: GET /ponto-connect/financial-institutions.
 *
 * Without a customer access token all financial institutions are listed,
 * with one only those of the organization's authorized accounts.
 */
class GetFinancialInstitutionsRequest extends Request
{
    use HasCursorPagination;

    protected Method $method = Method::GET;

    /**
     * Filters as `field => value` (operator "eq") or `field => [operator => value]`.
     * Filterable fields: country, name, paymentsEnabled, bulkPaymentsEnabled,
     * sharedBrandName and sharedBrandReference. Operators: eq, like, contains, in.
     *
     * @param array<string, string|bool|array<string, string|bool|list<string>>> $filters
     */
    public function __construct(
        protected ?int $limit = null,
        protected ?string $after = null,
        protected ?string $before = null,
        protected array $filters = [],
    ) {
    }

    public function resolveEndpoint(): string
    {
        return '/ponto-connect/financial-institutions';
    }

    /**
     * @return array<string, int|string>
     */
    protected function defaultQuery(): array
    {
        $query = $this->paginationQuery();

        foreach ($this->filters as $field => $filter) {
            if (! is_array($filter)) {
                $filter = ['eq' => $filter];
            }

            foreach ($filter as $operator => $value) {
                $query[sprintf('filter[%s][%s]', $field, $operator)] = $this->filterValue($value);
            }
        }

        return $query;
    }

    /**
     * @param string|bool|list<string> $value
     */
    private function filterValue(string|bool|array $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_array($value)) {
            return implode(',', $value);
        }

        return $value;
    }
}
