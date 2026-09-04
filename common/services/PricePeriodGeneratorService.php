<?php

namespace common\services;

use common\models\GslCompany;
use common\models\GslPriceDiscount;
use common\models\GslPricePeriod;
use DateTimeImmutable;
use Yii;
use yii\db\Exception as DbException;

class PricePeriodGeneratorService
{
    public static function minGeneratableMonth(): string
    {
        return date('Y-m', strtotime('first day of next month'));
    }

    public static function isGeneratableMonth(string $month): bool
    {
        return preg_match('/^\d{4}-\d{2}$/', $month) === 1
            && $month >= self::minGeneratableMonth();
    }

    /**
     * @return array<int, array{0:string,1:string}>
     */
    public function buildRanges(string $month): array
    {
        if (!preg_match('/^(\d{4})-(\d{2})$/', $month, $matches)) {
            throw new \InvalidArgumentException(Yii::t('app', '月份格式无效。'));
        }

        $monthStart = sprintf('%04d-%02d-01', (int) $matches[1], (int) $matches[2]);
        $monthEnd = date('Y-m-t', strtotime($monthStart));

        $ranges = [];
        $from = $monthStart;

        while ($from <= $monthEnd) {
            $fromDate = new DateTimeImmutable($from);
            $weekday = (int) $fromDate->format('N');

            if ($weekday < 4) {
                $firstThursday = $fromDate->modify('+' . (4 - $weekday) . ' days');
            } elseif ($weekday === 4) {
                $firstThursday = $fromDate;
            } else {
                $firstThursday = $fromDate->modify('+' . (7 - $weekday + 4) . ' days');
            }

            $firstThursdayStr = $firstThursday->format('Y-m-d');

            if ($firstThursdayStr > $monthEnd) {
                $ranges[] = [$from, $monthEnd];
                break;
            }

            if ($from < $firstThursdayStr) {
                $ranges[] = [$from, $firstThursday->modify('-1 day')->format('Y-m-d')];
                $from = $firstThursdayStr;
                continue;
            }

            $weekEnd = $firstThursday->modify('+6 days');
            $weekEndStr = $weekEnd->format('Y-m-d');

            if ($weekEndStr > $monthEnd) {
                $ranges[] = [$from, $monthEnd];
                break;
            }

            $ranges[] = [$from, $weekEndStr];
            $from = $weekEnd->modify('+1 day')->format('Y-m-d');
        }

        return $ranges;
    }

    public function monthHasPeriods(string $month): bool
    {
        $monthStart = $month . '-01';
        $monthEnd = date('Y-m-t', strtotime($monthStart));

        return GslPricePeriod::find()
            ->where(['<=', 'valid_from', $monthEnd])
            ->andWhere(['>=', 'valid_to', $monthStart])
            ->exists();
    }

    /**
     * @throws \DomainException
     * @throws DbException
     */
    public function generate(string $month): int
    {
        if (!self::isGeneratableMonth($month)) {
            throw new \DomainException(Yii::t('app', '只能生成下月及以后的价格时段。'));
        }

        if ($this->monthHasPeriods($month)) {
            throw new \DomainException(Yii::t('app', '该月已有价格记录，请先清空后再生成。'));
        }

        $companies = GslCompany::find()
            ->where(['is_active' => 1])
            ->orderBy(['sort_order' => SORT_ASC, 'id' => SORT_ASC])
            ->all();

        if (empty($companies)) {
            throw new \DomainException(Yii::t('app', '没有可用的公司，无法生成价格时段。'));
        }

        $ranges = $this->buildRanges($month);
        if (empty($ranges)) {
            throw new \DomainException(Yii::t('app', '未能生成价格时段。'));
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            foreach ($ranges as [$validFrom, $validTo]) {
                $period = new GslPricePeriod([
                    'valid_from' => $validFrom,
                    'valid_to' => $validTo,
                    'list_price' => 0,
                ]);
                if (!$period->save(false)) {
                    throw new DbException('Failed to save price period.');
                }

                foreach ($companies as $company) {
                    $discount = new GslPriceDiscount([
                        'price_period_id' => $period->id,
                        'company_id' => $company->id,
                        'discount_price' => 0,
                    ]);
                    if (!$discount->save(false)) {
                        throw new DbException('Failed to save price discount.');
                    }
                }
            }

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        return count($ranges);
    }

    /**
     * @throws \DomainException
     */
    public function clearMonth(string $month): int
    {
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            throw new \InvalidArgumentException(Yii::t('app', '月份格式无效。'));
        }

        if (!self::isGeneratableMonth($month)) {
            throw new \DomainException(Yii::t('app', '只能清空下月及以后的价格时段。'));
        }

        $monthStart = $month . '-01';
        $monthEnd = date('Y-m-t', strtotime($monthStart));

        $periods = GslPricePeriod::find()
            ->where(['<=', 'valid_from', $monthEnd])
            ->andWhere(['>=', 'valid_to', $monthStart])
            ->all();

        if (empty($periods)) {
            throw new \DomainException(Yii::t('app', '该月没有价格记录。'));
        }

        $count = count($periods);
        foreach ($periods as $period) {
            $period->delete();
        }

        return $count;
    }
}
