<?php

namespace App\Services\PriceLists;

use InvalidArgumentException;
use RuntimeException;

/** Price formula utilities. @agent-service: Price formula @agent-pattern: Calculation helper @agent-reusable: HIGH */
class PriceFormulaService
{
    /** Parse formula into tokens (numbers/operators/base). */
    public function parseFormula(string $formula): array
    {
        $clean = trim($formula);
        if ($clean === '') { return []; }
        // Split by space while keeping operators
        $pattern = '/(\+|\-|\*|\/)/';
        $parts = preg_split($pattern, $clean, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
        return array_map([$this, 'castToken'], array_map('trim', $parts));
    }

    /** Validate formula syntax and safety. */
    public function validateFormula(string $formula): bool
    {
        $f = trim($formula);
        if ($f === '') { return false; }
        // Allow digits, dot, operators, space, and the word base
        $stripped = str_ireplace('base', '', $f);
        if (trim($stripped) === '') { return true; }
        if (! preg_match('/^[0-9+\-*\s\/\.]+$/', $stripped)) {
            throw new InvalidArgumentException('Công thức chứa ký tự không hợp lệ');
        }
        // Disallow double operators like ++ or ** not between numbers/base
        if (preg_match('/(\+|\-|\*|\/)\s*(\+|\*|\/)/', $f)) {
            throw new InvalidArgumentException('Công thức không hợp lệ (operator lặp)');
        }
        // Division by zero detection (rough)
        if (preg_match('/\/\s*0(\.0+)?(\D|$)/', $f)) {
            throw new InvalidArgumentException('Không được chia cho 0');
        }
        return true;
    }

    /** Calculate price from formula and base price. */
    public function calculateFromFormula(string $formula, float $basePrice): float
    {
        $this->validateFormula($formula);
        $expr = str_ireplace('base', (string) $basePrice, $formula);
        // Evaluate in isolated scope
        set_error_handler(static function () { throw new RuntimeException('Lỗi tính công thức'); });
        try {
            $result = eval('return ' . $expr . ';');
        } catch (\Throwable $e) {
            restore_error_handler();
            throw $e;
        }
        restore_error_handler();
        $result = (float) $result;
        return $result < 0 ? 0.0 : $result;
    }

    /** Apply rounding rules. */
    public function applyRounding(float $price, string $rule): float
    {
        switch ($rule) {
            case 'thousand':
                return round($price / 1000) * 1000;
            case 'ten_thousand':
                return round($price / 10000) * 10000;
            case 'hundred':
                return round($price / 100) * 100;
            case 'none':
            default:
                return $price;
        }
    }

    /** @internal Cast token to float or keep operator/base */
    private function castToken(string $token)
    {
        if (in_array($token, ['+', '-', '*', '/'], true)) { return $token; }
        if (strcasecmp($token, 'base') === 0) { return 'base'; }
        if (is_numeric($token)) { return (float) $token; }
        return $token;
    }
}
