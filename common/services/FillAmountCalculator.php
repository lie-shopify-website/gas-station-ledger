<?php

namespace common\services;

/**
 * §10.4 amount precision helpers.
 */
class FillAmountCalculator
{
    public static function truncLiters($value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        // Form / DECIMAL 常以十进制字符串进来，直接截断可避开二进制误差。
        if (is_string($value)) {
            $value = trim($value);
            if ($value === '' || !is_numeric($value)) {
                return 0.0;
            }
            if (stripos($value, 'e') === false) {
                return self::truncDecimalLiteral($value);
            }
        }

        // float 路径：先把 129.004*1000=129003.999... 纠回，再向下截断（非四舍五入）。
        $scaled = round((float) $value * 1000, 8);

        return ($scaled < 0.0 ? ceil($scaled) : floor($scaled)) / 1000.0;
    }

    private static function truncDecimalLiteral(string $value): float
    {
        $negative = isset($value[0]) && $value[0] === '-';
        $abs = ltrim($value, '+-');
        $parts = explode('.', $abs, 2);
        $int = $parts[0] === '' ? '0' : $parts[0];
        $frac = str_pad(substr($parts[1] ?? '', 0, 3), 3, '0');

        return (float) (($negative ? '-' : '') . $int . '.' . $frac);
    }

    public static function roundPrice($value): float
    {
        return round((float) $value, 2);
    }

    public static function calcListAmount(float $liters, float $listPrice): float
    {
        return self::roundPrice(self::truncLiters($liters) * self::roundPrice($listPrice));
    }

    public static function calcAmountDue(float $liters, float $discountPrice): float
    {
        return self::roundPrice(self::truncLiters($liters) * self::roundPrice($discountPrice));
    }

    public static function calcCost(float $liters, float $costPerLiter): float
    {
        return -self::roundPrice(self::truncLiters($liters) * self::roundPrice($costPerLiter));
    }

    /**
     * @return array{list_price:float,list_amount:float,discount_price:float,amount_due:float,cost:float}
     */
    public static function snapshot(float $liters, float $listPrice, float $discountPrice, float $costPerLiter): array
    {
        $liters = self::truncLiters($liters);
        $listPrice = self::roundPrice($listPrice);
        $discountPrice = self::roundPrice($discountPrice);

        return [
            'list_price' => $listPrice,
            'list_amount' => self::calcListAmount($liters, $listPrice),
            'discount_price' => $discountPrice,
            'amount_due' => self::calcAmountDue($liters, $discountPrice),
            'cost' => self::calcCost($liters, $costPerLiter),
        ];
    }
}
