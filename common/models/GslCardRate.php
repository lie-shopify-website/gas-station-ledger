<?php

namespace common\models;

use DateTimeImmutable;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * 按卡的费率（每张卡按周一条，周区间为周四 → 周三，与价格时段口径一致）。
 */
class GslCardRate extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%gsl_card_rate}}';
    }

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'updatedAtAttribute' => 'updated_at',
                'createdAtAttribute' => 'created_at',
            ],
        ];
    }

    public function rules()
    {
        return [
            [['card_id', 'rate', 'effective_from', 'effective_to'], 'required'],
            [['card_id'], 'integer'],
            [['rate'], 'number'],
            [['effective_from', 'effective_to'], 'date', 'format' => 'php:Y-m-d'],
            [['note'], 'string', 'max' => 255],
        ];
    }

    public function attributeLabels()
    {
        return [
            'card_id' => \Yii::t('app', '油卡'),
            'rate' => \Yii::t('app', '费率'),
            'effective_from' => \Yii::t('app', '生效日期'),
            'effective_to' => \Yii::t('app', '结束日期'),
            'note' => \Yii::t('app', '备注'),
        ];
    }

    public function getCard()
    {
        return $this->hasOne(GslCard::class, ['id' => 'card_id']);
    }

    /**
     * 取某张卡在指定日期的当期费率；无覆盖则回退到最近一条已生效费率。
     */
    public static function resolveRate(int $cardId, string $date): ?float
    {
        if ($cardId <= 0 || $date === '') {
            return null;
        }

        $row = static::find()
            ->where(['card_id' => $cardId])
            ->andWhere(['<=', 'effective_from', $date])
            ->andWhere(['>=', 'effective_to', $date])
            ->orderBy(['effective_from' => SORT_DESC])
            ->one();

        if ($row === null) {
            $row = static::find()
                ->where(['card_id' => $cardId])
                ->andWhere(['<=', 'effective_from', $date])
                ->orderBy(['effective_from' => SORT_DESC])
                ->one();
        }

        return $row === null ? null : (float) $row->rate;
    }

    /**
     * 写入（或更新）某张卡在指定周的费率。
     */
    public static function setRate(int $cardId, string $effectiveFrom, string $effectiveTo, float $rate, ?string $note = null): void
    {
        $row = static::findOne(['card_id' => $cardId, 'effective_from' => $effectiveFrom]);
        if ($row === null) {
            $row = new static([
                'card_id' => $cardId,
                'effective_from' => $effectiveFrom,
            ]);
        }

        $row->rate = $rate;
        $row->effective_to = $effectiveTo;
        $row->note = $note;
        $row->save(false);
    }

    /**
     * @return array{0:string,1:string} 含指定日期的周区间（周四 ~ 周三）
     */
    public static function weekOf(string $date): array
    {
        $d = new DateTimeImmutable($date);
        $n = (int) $d->format('N');
        $delta = $n >= 4 ? $n - 4 : $n + 3;
        $thursday = $d->modify('-' . $delta . ' days');

        return [$thursday->format('Y-m-d'), $thursday->modify('+6 days')->format('Y-m-d')];
    }
}
