<?php

namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

class GslChit extends ActiveRecord
{
    /**
     * 油类型取值（忽略价格，仅取名称）
     */
    public const OIL_TYPES = [
        'RON97',
        'RON95',
        'E5 B12, B15 Diesel',
        'E5 B7 Diesel',
    ];

    public static function tableName()
    {
        return '{{%gsl_chit}}';
    }

    public function behaviors()
    {
        return [TimestampBehavior::class];
    }

    /**
     * @return array<string,string> 油类型下拉选项
     */
    public static function oilTypeOptions(): array
    {
        $items = [];
        foreach (self::OIL_TYPES as $type) {
            $items[$type] = $type;
        }

        return $items;
    }

    public function rules()
    {
        return [
            [['work_date', 'counter_id'], 'required'],
            [['work_date'], 'date', 'format' => 'php:Y-m-d'],
            [['counter_id', 'customer_id'], 'integer'],
            [['amount'], 'required'],
            [['amount'], 'number'],
            [['operator_no'], 'in', 'range' => array_keys(GslCounter::POS_NO_OPTIONS)],
            [['oil_type'], 'in', 'range' => self::OIL_TYPES],
            [['chit_stub_no', 'receipt_no'], 'string', 'max' => 64],
        ];
    }

    public function attributeLabels()
    {
        return [
            'work_date' => Yii::t('app', '日期'),
            'counter_id' => Yii::t('app', '柜台'),
            'customer_id' => Yii::t('app', '油票客户'),
            'oil_type' => Yii::t('app', '油类型'),
            'operator_no' => Yii::t('app', '柜员号'),
            'amount' => Yii::t('app', '金额'),
            'chit_stub_no' => Yii::t('app', '票根号'),
            'receipt_no' => Yii::t('app', 'Receipt 号'),
        ];
    }

    public function getCounter()
    {
        return $this->hasOne(GslCounter::class, ['id' => 'counter_id']);
    }

    public function getCustomer()
    {
        return $this->hasOne(GslChitCustomer::class, ['id' => 'customer_id']);
    }
}