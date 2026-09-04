<?php

namespace common\services;

/**
 * §10.4 amount precision helpers.
 */
class FillAmountCalculator
{
    public static function truncLiters($value): float
    {
        return floor((float) $value * 1000) / 1000;
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
