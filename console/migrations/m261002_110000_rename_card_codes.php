<?php

use yii\db\Migration;

/**
 * 油卡卡号按排序位置重命名：Card A/B/C... -> Card 1/2/3...
 * 幂等：已符合规范的卡不会再被改写。
 */
class m261002_110000_rename_card_codes extends Migration
{
    public function safeUp()
    {
        $cards = (new \yii\db\Query())
            ->select(['id', 'card_code'])
            ->from('{{%gsl_card}}')
            ->orderBy(['sort_order' => SORT_ASC, 'id' => SORT_ASC])
            ->all($this->db);

        $n = 0;
        foreach ($cards as $card) {
            $n++;
            $new = 'Card ' . $n;
            if ($card['card_code'] === $new) {
                continue;
            }
            $this->update('{{%gsl_card}}', ['card_code' => $new], ['id' => $card['id']]);
        }
    }

    public function safeDown()
    {
        // 卡号重命名不还原：存在人工改号的可能，逆映射不安全。
    }
}