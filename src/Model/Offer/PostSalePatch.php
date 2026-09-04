<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Model\Offer;

use DevLancer\VonHalsky\Exception\InvalidRequestException;
use DevLancer\VonHalsky\Model\OptionalValue;
use DevLancer\VonHalsky\Model\RequestDtoInterface;

/** Partial post-sale policies for an offer merge patch. */
final class PostSalePatch implements RequestDtoInterface
{
    /** @var OptionalValue<PostSalePolicyPatch|null> */
    public readonly OptionalValue $returnPolicy;
    /** @var OptionalValue<PostSalePolicyPatch|null> */
    public readonly OptionalValue $complaintPolicy;

    /**
     * @param OptionalValue<PostSalePolicyPatch|null>|null $returnPolicy
     * @param OptionalValue<PostSalePolicyPatch|null>|null $complaintPolicy
     */
    public function __construct(?OptionalValue $returnPolicy = null, ?OptionalValue $complaintPolicy = null)
    {
        $this->returnPolicy = $returnPolicy ?? OptionalValue::undefined();
        $this->complaintPolicy = $complaintPolicy ?? OptionalValue::undefined();
        $this->validatePolicy($this->returnPolicy, 'returnPolicy');
        $this->validatePolicy($this->complaintPolicy, 'complaintPolicy');
    }

    public function jsonSerialize(): array
    {
        return [
            'returnPolicy' => $this->returnPolicy,
            'complaintPolicy' => $this->complaintPolicy,
        ];
    }

    /** @param OptionalValue<PostSalePolicyPatch|null> $value */
    private function validatePolicy(OptionalValue $value, string $field): void
    {
        if ($value->isDefined() && !$value->isNull() && !($value->value() instanceof PostSalePolicyPatch)) {
            throw new InvalidRequestException('Offer.postSale.' . $field, 'must be a PostSalePolicyPatch');
        }
    }
}
