<?php

namespace common\models;

use yii\db\ActiveRecord;

class GslDailyTicketLog extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%gsl_daily_ticket_log}}';
    }

    public function rules()
    {
        return [
            [['work_date'], 'required'],
            [['work_date'], 'date', 'format' => 'php:Y-m-d'],
            [['tickets_manual'], 'integer'],
            [['updated_at'], 'integer'],
        ];
    }

    public static function systemTickets(string $workDate): int
    {
        return (int) GslFill::find()
            ->where(['work_date' => $workDate])
            ->andWhere(['>', 'liters', 0])
            ->count();
    }
}
