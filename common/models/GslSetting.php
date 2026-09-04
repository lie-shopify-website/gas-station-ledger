<?php

namespace common\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

class GslSetting extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%gsl_setting}}';
    }

    public function rules()
    {
        return [
            [['setting_key', 'setting_value'], 'required'],
            [['setting_key'], 'string', 'max' => 64],
            [['setting_value'], 'string', 'max' => 255],
            [['updated_at'], 'integer'],
        ];
    }

    public static function getValue(string $key, $default = null)
    {
        $row = static::findOne(['setting_key' => $key]);
        return $row ? $row->setting_value : $default;
    }

    public static function setValue(string $key, string $value): void
    {
        $row = static::findOne(['setting_key' => $key]) ?? new static(['setting_key' => $key]);
        $row->setting_value = $value;
        $row->updated_at = time();
        $row->save(false);
    }
}
