<?php

use yii\db\Migration;

/**
 * 刷卡口径修正：刷卡金额 = 卡费率(0.7/0.8) × 升数 = 应付持卡人。
 * - gsl_card 增加 owner_rate（费率，放卡上，与卡类型解耦）
 * - gsl_swipe 的 discount_price → owner_rate（费率快照），amount_due → owner_payout（应付持卡人）
 */
class m261002_120000_swipe_owner_rate extends Migration
{
    public function safeUp()
    {
        // 卡费率，默认 0.80
        $this->addColumn('{{%gsl_card}}', 'owner_rate', $this->decimal(4, 2)->notNull()->defaultValue(0.80)->after('card_type_id'));

        // 刷卡金额改为「卡费率 × 升数 = 应付持卡人」
        $this->renameColumn('{{%gsl_swipe}}', 'discount_price', 'owner_rate');
        $this->renameColumn('{{%gsl_swipe}}', 'amount_due', 'owner_payout');

        // 存量记录按所在卡费率回填
        $this->execute('UPDATE {{%gsl_swipe}} s JOIN {{%gsl_card}} c ON c.id = s.card_id SET s.owner_rate = c.owner_rate, s.owner_payout = ROUND(s.liters * c.owner_rate, 2)');
    }

    public function safeDown()
    {
        $this->renameColumn('{{%gsl_swipe}}', 'owner_payout', 'amount_due');
        $this->renameColumn('{{%gsl_swipe}}', 'owner_rate', 'discount_price');
        $this->dropColumn('{{%gsl_card}}', 'owner_rate');
    }
}