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
            [['owner_name'], 'string', 'max' => 64],
            [['card_type_id', 'sort_order'], 'integer'],
            [['monthly_quota', 'owner_rate'], 'number'],
            [['is_active'], 'boolean'],
            [['note'], 'string', 'max' => 255],
        ];
    }

    public function attributeLabels()
    {
        return [
            'card_code' => \Yii::t('app', '卡号'),
            'card_type_id' => \Yii::t('app', '卡类型'),
            'owner_name' => \Yii::t('app', '卡所属人'),
            'real_card_no' => \Yii::t('app', '真实卡号'),
            'monthly_quota' => \Yii::t('app', '可刷升数'),
            'owner_rate' => \Yii::t('app', '默认费率'),
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

    /**
     * 指定日期该卡的当期费率：优先费率表，费率表无记录时回退到卡上默认费率。
     */
    public function getCurrentRate(?string $date = null): float
    {
        $date = $date ?: date('Y-m-d');
        $rate = GslCardRate::resolveRate((int) $this->id, $date);

        return $rate === null ? (float) $this->owner_rate : $rate;
    }

    /**
     * 系统内已填写过的「卡所属人」，用于输入提示，避免 Lian / LIAN / lian 分家。
     *
     * @return string[]
     */
    public static function ownerNameOptions(): array
    {
        return static::find()
            ->select(['owner_name'])
            ->where(['not', ['owner_name' => null]])
            ->andWhere(['<>', 'owner_name', ''])
            ->distinct()
            ->orderBy(['owner_name' => SORT_ASC])
            ->column();
    }
}
