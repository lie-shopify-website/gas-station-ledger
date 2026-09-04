<?php

namespace common\models;

use common\services\FillAmountCalculator;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

class GslSwipe extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%gsl_swipe}}';
    }

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'updatedAtAttribute' => false,
            ],
        ];
    }

    public function rules()
    {
        return [
            [['work_date', 'company_id', 'card_id'], 'required'],
            [['work_date'], 'date', 'format' => 'php:Y-m-d'],
            [['company_id', 'card_id'], 'integer'],
            [['liters'], 'number'],
            [['swipe_receipt'], 'string', 'max' => 64],
            [['note'], 'string', 'max' => 255],
        ];
    }

    public function getCompany()
    {
        return $this->hasOne(GslCompany::class, ['id' => 'company_id']);
    }

    public function getCard()
    {
        return $this->hasOne(GslCard::class, ['id' => 'card_id']);
    }

    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }
        $this->liters = FillAmountCalculator::truncLiters($this->liters);
        return true;
    }
}
