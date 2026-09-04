<?php

namespace backend\modules\settings\models;

use common\models\GslSetting;
use Yii;
use yii\base\Model;

class SettingsForm extends Model
{
    public $ledger_month;
    public $cost_per_liter;

    public function rules()
    {
        return [
            [['ledger_month', 'cost_per_liter'], 'required'],
            ['ledger_month', 'match', 'pattern' => '/^\d{4}-\d{2}$/', 'message' => Yii::t('app', '月份格式应为 YYYY-MM')],
            ['cost_per_liter', 'number', 'min' => 0],
        ];
    }

    public function attributeLabels()
    {
        return [
            'ledger_month' => Yii::t('app', '台账月份'),
            'cost_per_liter' => Yii::t('app', '每升成本'),
        ];
    }

    public function loadFromSettings(): void
    {
        $this->ledger_month = GslSetting::getValue('ledger_month', date('Y-m'));
        $this->cost_per_liter = GslSetting::getValue('cost_per_liter', '0');
    }

    public function save(): bool
    {
        if (!$this->validate()) {
            return false;
        }
        GslSetting::setValue('ledger_month', $this->ledger_month);
        GslSetting::setValue('cost_per_liter', (string) $this->cost_per_liter);
        return true;
    }
}
