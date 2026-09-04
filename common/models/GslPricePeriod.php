<?php

namespace common\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

class GslPricePeriod extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%gsl_price_period}}';
    }

    public function behaviors()
    {
        return [TimestampBehavior::class];
    }

    public function rules()
    {
        return [
            [['valid_from', 'valid_to', 'list_price'], 'required'],
            [['valid_from', 'valid_to'], 'date', 'format' => 'php:Y-m-d'],
            [['list_price'], 'number'],
        ];
    }

    public function getDiscounts()
    {
        return $this->hasMany(GslPriceDiscount::class, ['price_period_id' => 'id']);
    }

    public static function findForDate(string $workDate): ?self
    {
        return static::find()
            ->where(['<=', 'valid_from', $workDate])
            ->andWhere(['>=', 'valid_to', $workDate])
            ->orderBy(['valid_from' => SORT_DESC])
            ->one();
    }
}
