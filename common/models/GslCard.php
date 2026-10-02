<?php

namespace common\models;

use yii\db\ActiveRecord;

class GslCard extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%gsl_card}}';
    }

    public function rules()
    {
        return [
            [['card_code'], 'required'],
            [['card_code'], 'string', 'max' => 32],
            [['real_card_no'], 'string', 'max' => 64],
            [['card_type_id', 'sort_order'], 'integer'],
            [['monthly_quota'], 'number'],
            [['is_active'], 'boolean'],
            [['note'], 'string', 'max' => 255],
        ];
    }

    public function attributeLabels()
    {
        return [
            'card_code' => \Yii::t('app', '卡号'),
            'card_type_id' => \Yii::t('app', '卡类型'),
            'real_card_no' => \Yii::t('app', '真实卡号'),
            'monthly_quota' => \Yii::t('app', '月配额'),
            'sort_order' => \Yii::t('app', '排序'),
            'is_active' => \Yii::t('app', '状态'),
            'note' => \Yii::t('app', '备注'),
        ];
    }

    public function getCardType()
    {
        return $this->hasOne(GslCardType::class, ['id' => 'card_type_id']);
    }

    public function getCardTypeName(): string
    {
        return $this->cardType ? $this->cardType->name : '';
    }

    public static function findByCode(string $code): ?self
    {
        return static::findOne(['card_code' => trim($code)]);
    }

    public function getUsedLiters(): float
    {
        return (float) GslSwipe::find()->where(['card_id' => $this->id])->sum('liters');
    }
}
