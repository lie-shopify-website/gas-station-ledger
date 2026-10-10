<?php

namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

class GslChit extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%gsl_chit}}';
    }

    public function behaviors()
    {
        return [TimestampBehavior::class];
    }

    public function rules()
    {
        return [
            [['work_date', 'counter_id'], 'required'],
            [['work_date'], 'date', 'format' => 'php:Y-m-d'],
            [['counter_id'], 'integer'],
            [['amount'], 'required'],
            [['amount'], 'number'],
            [['operator_no'], 'string', 'max' => 32],
            [['chit_stub_no', 'receipt_no'], 'string', 'max' => 64],
        ];
    }

    public function attributeLabels()
    {
        return [
            'work_date' => Yii::t('app', '日期'),
            'counter_id' => Yii::t('app', '柜台'),
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
}