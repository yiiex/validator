<?php

namespace Yii1x\Validator\Contracts;

interface ValidatorInterface
{
    public function validate(string $scenario): bool;

    public function addError(string $attribute, string $message, array $params = []): static;

    public function hasErrors(null|string|array $attribute = null): bool;

    public function getErrors(null|string|array $attribute = null): array;

    public function clearErrors(null|string|array $attribute = null): static;

    public function getSafeAttributes(?string $scenario = null): array;

    public function getRequiredAttributes(?string $scenario = null): array;

    public function setRules(array $rules): static;
}