<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;

class GslCounter extends ActiveRecord
{
    /**
     * Pos No. 取值：仅 1、2
     */
    public const POS_NO_OPTIONS = ['1' => '1', '2' => '2'];

    /**
     * @return array<string,string> Pos No. 下拉选项
     */
    public static function posNoOptions(): array
    {
        return self::POS_NO_OPTIONS;
    }

    public static function tableName()
    {
        return '{{%gsl_counter}}';
    }

    public function rules()
    {
        return [
            [['code'], 'required'],
            [['code'], 'string', 'length' => 4],
            [['name'], 'string', 'max' => 32],
            [['operator_no'], 'in', 'range' => array_keys(self::POS_NO_OPTIONS)],
            [['sort_order'], 'integer'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'code' => Yii::t('app', '代号'),
            'name' => Yii::t('app', '柜台名称'),
            'operator_no' => Yii::t('app', '柜员号'),
            'sort_order' => Yii::t('app', '排序'),
        ];
    }

    /**
     * 柜台显示名：优先 name，未维护时回退到 code。
     */
    public function getLabel(): string
    {
        return $this->name !== null && $this->name !== '' ? $this->name : $this->code;
    }

    /**
     * @return array<int,string> id => 显示名
     */
    public static function options(): array
    {
        $items = [];
        foreach (static::find()->orderBy(['sort_order' => SORT_ASC, 'code' => SORT_ASC])->all() as $counter) {
            $items[$counter->id] = $counter->getLabel();
        }

        return $items;
    }

    public static function findByCode(string $code): ?self
    {
        $code = str_pad(trim($code), 4, '0', STR_PAD_LEFT);
        return static::findOne(['code' => $code]);
    }
}