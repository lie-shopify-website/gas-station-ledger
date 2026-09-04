<?php

namespace common\models;

use common\services\FillAmountCalculator;
use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

class GslFill extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%gsl_fill}}';
    }

    public function behaviors()
    {
        return [TimestampBehavior::class];
    }

    public function rules()
    {
        return [
            [['work_date', 'company_id'], 'required'],
            [['work_date'], 'date', 'format' => 'php:Y-m-d'],
            [['company_id', 'plate_id', 'counter_id'], 'integer'],
            [['liters', 'list_price', 'list_amount', 'discount_price', 'amount_due', 'cost'], 'number'],
            [['ticket_no'], 'string', 'max' => 32],
            [['note'], 'string', 'max' => 255],
            ['plate_id', 'validatePlateCompany'],
        ];
    }

    public function validatePlateCompany(string $attribute): void
    {
        if (!$this->plate_id || !$this->company_id) {
            return;
        }

        $plate = GslPlate::findOne($this->plate_id);
        if ($plate === null || (int) $plate->company_id !== (int) $this->company_id) {
            $this->addError($attribute, Yii::t('app', '车牌与所选公司不匹配。'));
        }
    }

    public function getCompany()
    {
        return $this->hasOne(GslCompany::class, ['id' => 'company_id']);
    }

    public function getPlate()
    {
        return $this->hasOne(GslPlate::class, ['id' => 'plate_id']);
    }

    public function getCounter()
    {
        return $this->hasOne(GslCounter::class, ['id' => 'counter_id']);
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
