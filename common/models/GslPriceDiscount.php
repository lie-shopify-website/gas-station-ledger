<?php

namespace common\models;

use yii\db\ActiveRecord;

class GslPriceDiscount extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%gsl_price_discount}}';
    }

    public function rules()
    {
        return [
            [['price_period_id', 'company_id', 'discount_price'], 'required'],
            [['price_period_id', 'company_id'], 'integer'],
            [['discount_price'], 'number'],
        ];
    }

    public function getCompany()
    {
        return $this->hasOne(GslCompany::class, ['id' => 'company_id']);
    }
}
