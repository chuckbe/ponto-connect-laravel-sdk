<?php

namespace PontoConnect\Data;

use DateTimeImmutable;
use PontoConnect\Support\DateParser;
use PontoConnect\Support\Decimal;
use PontoConnect\Support\JsonApiResource;

/**
 * A booked account transaction.
 *
 * `amount` and `fee` are decimal strings, see {@see Decimal}.
 * A positive amount is money coming in, a negative amount money going out.
 */
class Transaction
{
    final public function __construct(
        public readonly string $id,
        public readonly ?string $accountId = null,
        public readonly ?string $amount = null,
        public readonly ?string $currency = null,
        public readonly ?DateTimeImmutable $valueDate = null,
        public readonly ?DateTimeImmutable $executionDate = null,
        public readonly ?string $counterpartName = null,
        public readonly ?string $counterpartReference = null,
        public readonly ?string $remittanceInformation = null,
        public readonly ?string $remittanceInformationType = null,
        public readonly ?string $description = null,
        public readonly ?string $additionalInformation = null,
        public readonly ?string $endToEndId = null,
        public readonly ?string $mandateId = null,
        public readonly ?string $creditorId = null,
        public readonly ?string $purposeCode = null,
        public readonly ?string $bankTransactionCode = null,
        public readonly ?string $proprietaryBankTransactionCode = null,
        public readonly ?string $cardReference = null,
        public readonly ?string $cardReferenceType = null,
        public readonly ?string $internalReference = null,
        public readonly ?string $digest = null,
        public readonly ?string $fee = null,
        public readonly ?DateTimeImmutable $createdAt = null,
        public readonly ?DateTimeImmutable $updatedAt = null,
    ) {
    }

    /**
     * @param array<string, mixed> $resource JSON:API resource object
     */
    public static function fromArray(array $resource): static
    {
        $transaction = JsonApiResource::fromArray($resource);

        return new static(
            id: (string) $transaction->id,
            accountId: $transaction->relationshipId('account'),
            amount: Decimal::fromNumber($transaction->attribute('amount')),
            currency: $transaction->stringAttribute('currency'),
            valueDate: DateParser::parse($transaction->attribute('valueDate')),
            executionDate: DateParser::parse($transaction->attribute('executionDate')),
            counterpartName: $transaction->stringAttribute('counterpartName'),
            counterpartReference: $transaction->stringAttribute('counterpartReference'),
            remittanceInformation: $transaction->stringAttribute('remittanceInformation'),
            remittanceInformationType: $transaction->stringAttribute('remittanceInformationType'),
            description: $transaction->stringAttribute('description'),
            additionalInformation: $transaction->stringAttribute('additionalInformation'),
            endToEndId: $transaction->stringAttribute('endToEndId'),
            mandateId: $transaction->stringAttribute('mandateId'),
            creditorId: $transaction->stringAttribute('creditorId'),
            purposeCode: $transaction->stringAttribute('purposeCode'),
            bankTransactionCode: $transaction->stringAttribute('bankTransactionCode'),
            proprietaryBankTransactionCode: $transaction->stringAttribute('proprietaryBankTransactionCode'),
            cardReference: $transaction->stringAttribute('cardReference'),
            cardReferenceType: $transaction->stringAttribute('cardReferenceType'),
            internalReference: $transaction->stringAttribute('internalReference'),
            digest: $transaction->stringAttribute('digest'),
            fee: Decimal::fromNumber($transaction->attribute('fee')),
            createdAt: DateParser::parse($transaction->attribute('createdAt')),
            updatedAt: DateParser::parse($transaction->attribute('updatedAt')),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'accountId' => $this->accountId,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'valueDate' => DateParser::format($this->valueDate),
            'executionDate' => DateParser::format($this->executionDate),
            'counterpartName' => $this->counterpartName,
            'counterpartReference' => $this->counterpartReference,
            'remittanceInformation' => $this->remittanceInformation,
            'remittanceInformationType' => $this->remittanceInformationType,
            'description' => $this->description,
            'additionalInformation' => $this->additionalInformation,
            'endToEndId' => $this->endToEndId,
            'mandateId' => $this->mandateId,
            'creditorId' => $this->creditorId,
            'purposeCode' => $this->purposeCode,
            'bankTransactionCode' => $this->bankTransactionCode,
            'proprietaryBankTransactionCode' => $this->proprietaryBankTransactionCode,
            'cardReference' => $this->cardReference,
            'cardReferenceType' => $this->cardReferenceType,
            'internalReference' => $this->internalReference,
            'digest' => $this->digest,
            'fee' => $this->fee,
            'createdAt' => DateParser::format($this->createdAt),
            'updatedAt' => DateParser::format($this->updatedAt),
        ];
    }
}
