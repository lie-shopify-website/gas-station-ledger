<?php

namespace common\models;

use common\services\FillAmountCalculator;
use Yii;
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
            [['liters', 'discount_price', 'amount_due'], 'number'],
            [['swipe_receipt'], 'string', 'max' => 64],
            [['note'], 'string', 'max' => 255],
            ['liters', 'validatePositive'],
            ['liters', 'validateMaxPerTime'],
            ['liters', 'validateMaxPerCardDay'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'work_date' => Yii::t('app', '日期'),
            'company_id' => Yii::t('app', '公司'),
            'card_id' => Yii::t('app', '油卡'),
            'liters' => Yii::t('app', '升数'),
            'discount_price' => Yii::t('app', '优惠价'),
            'amount_due' => Yii::t('app', '应收金额'),
            'swipe_receipt' => Yii::t('app', '回单号'),
            'note' => Yii::t('app', '备注'),
        ];
    }

    public function validatePositive(string $attribute): void
    {
        if ($this->$attribute === null || $this->$attribute === '') {
            return;
        }
        if ((float) $this->$attribute <= 0) {
            $this->addError($attribute, Yii::t('app', '升数必须大于 0。'));
        }
    }

    public function validateMaxPerTime(string $attribute): void
    {
        $max = (float) GslSetting::getValue('swipe_max_per_time', 250);
        if ($max <= 0 || $this->$attribute === null || $this->$attribute === '') {
            return;
        }
        if ((float) $this->$attribute > $max) {
            $this->addError($attribute, Yii::t('app', '单次刷卡不得超过 {max} 升。', ['max' => $max]));
        }
    }

    public function validateMaxPerCardDay(string $attribute): void
    {
        if (!$this->card_id || !$this->work_date || $this->$attribute === null || $this->$attribute === '') {
            return;
        }

        $max = (float) GslSetting::getValue('swipe_max_per_card_day', 500);
        if ($max <= 0) {
            return;
        }

        $query = static::find()->where([
            'card_id' => $this->card_id,
            'work_date' => $this->work_date,
        ]);
        if (!$this->isNewRecord) {
            $query->andWhere(['<>', 'id', $this->id]);
        }

        $used = (float) $query->sum('liters');
        $total = FillAmountCalculator::truncLiters($used + (float) $this->$attribute);
        if ($total > $max) {
            $this->addError($attribute, Yii::t('app', '该卡当日累计不得超过 {max} 升（已刷 {used} 升）。', [
                'max' => $max,
                'used' => $used,
            ]));
        }
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