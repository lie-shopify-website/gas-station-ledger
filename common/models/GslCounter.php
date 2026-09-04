<?php

namespace common\models;

use yii\db\ActiveRecord;

class GslCounter extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%gsl_counter}}';
    }

    public function rules()
    {
        return [
            [['code'], 'required'],
            [['code'], 'string', 'length' => 4],
            [['sort_order'], 'integer'],
        ];
    }

    public static function findByCode(string $code): ?self
    {
        $code = str_pad(trim($code), 4, '0', STR_PAD_LEFT);
        return static::findOne(['code' => $code]);
    }
}
