<?php

namespace common\models;

use yii\db\ActiveRecord;

class GslCompany extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%gsl_company}}';
    }

    public function rules()
    {
        return [
            [['name', 'payment_type'], 'required'],
            [['name'], 'string', 'max' => 64],
            [['payment_type'], 'in', 'range' => ['Cash', 'MCM']],
            [['sort_order'], 'integer'],
            [['is_active'], 'boolean'],
        ];
    }

    public function getPlates()
    {
        return $this->hasMany(GslPlate::class, ['company_id' => 'id']);
    }

    public static function findByName(string $name): ?self
    {
        return static::findOne(['name' => trim($name)]);
    }
}
