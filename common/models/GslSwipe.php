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
            [['work_date', 'card_id'], 'required'],
            [['work_date'], 'date', 'format' => 'php:Y-m-d'],
            [['company_id', 'card_id'], 'integer'],
            [['liters', 'owner_rate', 'owner_payout'], 'number'],
            [['swipe_receipt'], 'string', 'max' => 64],
            [['note'], 'string', 'max' => 255],
            ['liters', 'validatePositive'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'work_date' => Yii::t('app', '日期'),
            'company_id' => Yii::t('app', '公司'),
            'card_id' => Yii::t('app', '油卡'),
            'liters' => Yii::t('app', '升数'),
            'owner_rate' => Yii::t('app', '卡费率'),
            'owner_payout' => Yii::t('app', '应付持卡人'),
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

        // 刷卡金额口径：按刷卡日期取该卡当期费率（费率表），无记录时回退到卡上默认费率。
        // 费率与卡类型解耦；允许超刷，不做升数上限拦截。
        $rate = null;
        if ($this->card_id) {
            $rate = GslCardRate::resolveRate((int) $this->card_id, (string) $this->work_date);
            if ($rate === null && $this->card) {
                $rate = (float) $this->card->owner_rate;
            }
        }
        $this->owner_rate = FillAmountCalculator::roundPrice((float) $rate);
        $this->owner_payout = FillAmountCalculator::calcOwnerPayout((float) $this->liters, (float) $this->owner_rate);

        return true;
    }
}