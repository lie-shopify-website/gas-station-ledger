<?php

namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

class GslChitTopup extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%gsl_chit_topup}}';
    }

    public function behaviors()
    {
        return [TimestampBehavior::class];
    }

    public function rules()
    {
        return [
            [['customer_id', 'topup_date'], 'required'],
            [['topup_date'], 'date', 'format' => 'php:Y-m-d'],
            [['customer_id'], 'integer'],
            [['amount'], 'required'],
            [['amount'], 'number'],
            [['note'], 'string', 'max' => 255],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'customer_id' => Yii::t('app', '客户名称'),
            'amount' => Yii::t('app', '预存金额'),
            'topup_date' => Yii::t('app', '预存日期'),
            'note' => Yii::t('app', '备注'),
        ];
    }

    public function getCustomer()
    {
        return $this->hasOne(GslChitCustomer::class, ['id' => 'customer_id']);
    }

    /**
     * 按客户汇总预存金额。
     *
     * @return array<int,float> customer_id => 合计金额
     */
    public static function totalsByCustomer(): array
    {
        $rows = static::find()
            ->select(['customer_id', 'total' => 'ROUND(SUM([[amount]]), 2)'])
            ->groupBy('customer_id')
            ->asArray()
            ->all();

        $totals = [];
        foreach ($rows as $row) {
            $totals[(int) $row['customer_id']] = (float) $row['total'];
        }

        return $totals;
    }
}