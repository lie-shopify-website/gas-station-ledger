<?php

namespace common\models;

use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

class GslCardType extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%gsl_card_type}}';
    }

    public function rules()
    {
        return [
            [['code', 'name'], 'required'],
            [['code'], 'string', 'max' => 32],
            [['name'], 'string', 'max' => 64],
            [['sort_order'], 'integer'],
            [['is_active'], 'boolean'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'code' => \Yii::t('app', '类型代码'),
            'name' => \Yii::t('app', '类型名称'),
            'sort_order' => \Yii::t('app', '排序'),
            'is_active' => \Yii::t('app', '状态'),
        ];
    }

    public function getCards()
    {
        return $this->hasMany(GslCard::class, ['card_type_id' => 'id']);
    }

    /**
     * @return array<int, string> 启用中的卡类型 id => name
     */
    public static function options(): array
    {
        return ArrayHelper::map(
            static::find()->where(['is_active' => 1])->orderBy('sort_order')->all(),
            'id',
            'name'
        );
    }
}