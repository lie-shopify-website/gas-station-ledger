<?php

namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

class GslChitCustomer extends ActiveRecord
{
    /**
     * 启用状态取值
     */
    public const ACTIVE_OPTIONS = ['1' => '启用', '0' => '停用'];

    public static function tableName()
    {
        return '{{%gsl_chit_customer}}';
    }

    public function behaviors()
    {
        return [TimestampBehavior::class];
    }

    public function rules()
    {
        return [
            [['name'], 'required'],
            [['name'], 'string', 'max' => 64],
            [['name'], 'unique'],
            [['deposit_amount'], 'number'],
            [['deposit_amount'], 'default', 'value' => 0],
            [['is_active'], 'in', 'range' => array_keys(self::ACTIVE_OPTIONS)],
            [['is_active'], 'default', 'value' => 1],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => Yii::t('app', '客户名称'),
            'deposit_amount' => Yii::t('app', '预存金额'),
            'is_active' => Yii::t('app', '启用状态'),
        ];
    }

    /**
     * @return array<string,string> 启用状态下拉
     */
    public static function activeOptions(): array
    {
        $items = [];
        foreach (self::ACTIVE_OPTIONS as $value => $label) {
            $items[$value] = Yii::t('app', $label);
        }

        return $items;
    }

    /**
     * @return array<int,string> id => 客户名称
     */
    public static function options(): array
    {
        $items = [];
        foreach (static::find()->orderBy(['name' => SORT_ASC])->all() as $customer) {
            $items[$customer->id] = $customer->name;
        }

        return $items;
    }

    public function getIsActiveLabel(): string
    {
        return Yii::t('app', self::ACTIVE_OPTIONS[(string) $this->is_active] ?? '停用');
    }
}