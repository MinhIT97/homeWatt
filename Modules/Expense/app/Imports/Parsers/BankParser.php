<?php

namespace Modules\Expense\Imports\Parsers;

interface BankParser
{
    /**
     * Detect whether this parser can handle the given CSV headers.
     */
    public function detect(array $headers): bool;

    /**
     * Parse rows into normalized transaction data.
     *
     * @param  array<int, array<string, string|null>>  $rows
     * @return array<int, array{date: string, amount: float, type: string, description: string, reference: string|null}>
     */
    public function parse(array $rows): array;
}
