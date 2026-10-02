<?php

namespace backend\modules\settings\models;

use common\models\GslSetting;
use Yii;
use yii\base\Model;

class SettingsForm extends Model
{
    public $ledger_month;
    public $cost_per_liter;
    public $swipe_max_per_time;
    public $swipe_max_per_card_day;

    public function rules()
    {
        return [
            [['ledger_month', 'cost_per_liter', 'swipe_max_per_time', 'swipe_max_per_card_day'], 'required'],
            ['ledger_month', 'match', 'pattern' => '/^\d{4}-\d{2}$/', 'message' => Yii::t('app', '月份格式应为 YYYY-MM')],
            [['cost_per_liter', 'swipe_max_per_time', 'swipe_max_per_card_day'], 'number', 'min' => 0],
        ];
    }

    public function attributeLabels()
    {
        return [
            'ledger_month' => Yii::t('app', '台账月份'),
            'cost_per_liter' => Yii::t('app', '每升成本'),
            'swipe_max_per_time' => Yii::t('app', '单次刷卡上限'),
            'swipe_max_per_card_day' => Yii::t('app', '单卡每日上限'),
        ];
    }

    public function loadFromSettings(): void
    {
        $this->ledger_month = GslSetting::getValue('ledger_month', date('Y-m'));
        $this->cost_per_liter = GslSetting::getValue('cost_per_liter', '0');
        $this->swipe_max_per_time = GslSetting::getValue('swipe_max_per_time', '250');
        $this->swipe_max_per_card_day = GslSetting::getValue('swipe_max_per_card_day', '500');
    }

    public function save(): bool
    {
        if (!$this->validate()) {
            return false;
        }
        GslSetting::setValue('ledger_month', $this->ledger_month);
        GslSetting::setValue('cost_per_liter', (string) $this->cost_per_liter);
        GslSetting::setValue('swipe_max_per_time', (string) $this->swipe_max_per_time);
        GslSetting::setValue('swipe_max_per_card_day', (string) $this->swipe_max_per_card_day);
        return true;
    }
}
