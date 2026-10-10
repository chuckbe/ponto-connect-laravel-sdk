<?php

namespace PontoConnect\Data;

use DateTimeImmutable;
use PontoConnect\Support\DateParser;
use PontoConnect\Support\Decimal;
use PontoConnect\Support\JsonApiResource;

/**
 * A fake transaction on a sandbox account. It shows up as a regular
 * {@see Transaction} once the related account has been synchronized.
 */
class FinancialInstitutionTransaction
{
    public function __construct(
        public readonly string $id,
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
        public readonly ?string $fee = null,
        public readonly ?DateTimeImmutable $createdAt = null,
        public readonly ?DateTimeImmutable $updatedAt = null,
    ) {
    }

    /**
     * @param array<string, mixed> $resource JSON:API resource object
     */
    public static function fromArray(array $resource): self
    {
        $transaction = JsonApiResource::fromArray($resource);

        return new self(
            id: (string) $transaction->id,
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
            'fee' => $this->fee,
            'createdAt' => DateParser::format($this->createdAt),
            'updatedAt' => DateParser::format($this->updatedAt),
        ];
    }
}
