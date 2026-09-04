<?php

namespace common\models;

use yii\db\ActiveRecord;

class GslPlate extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%gsl_plate}}';
    }

    public function rules()
    {
        return [
            [['company_id', 'plate_no'], 'required'],
            [['company_id'], 'integer'],
            [['plate_no'], 'string', 'max' => 32],
            [['is_active'], 'boolean'],
        ];
    }

    public function getCompany()
    {
        return $this->hasOne(GslCompany::class, ['id' => 'company_id']);
    }

    public static function findOrCreate(int $companyId, string $plateNo): self
    {
        $plateNo = trim($plateNo);
        $plate = static::findOne(['company_id' => $companyId, 'plate_no' => $plateNo]);
        if ($plate) {
            return $plate;
        }
        $plate = new static([
            'company_id' => $companyId,
            'plate_no' => $plateNo,
            'is_active' => 1,
        ]);
        $plate->save(false);
        return $plate;
    }
}
