<?php

use yii\db\Migration;

/**
 * 按卡的费率表 + 卡所属人 + 刷卡口径调整。
 * - 新增 gsl_card_rate：每张卡按周（周四 → 周三）一条费率，刷卡时按日期取当期费率并做快照。
 * - gsl_card 增加 owner_name（卡所属人 / 公司，手填文本）。
 * - 卡类型 cash → Subcard。
 * - gsl_swipe.company_id 改为可空（刷卡以卡号识别，公司仅作筛选，不参与计算）。
 */
class m261003_100000_gsl_card_rates extends Migration
{
    public function safeUp()
    {
        $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';

        $this->createTable('{{%gsl_card_rate}}', [
            'id' => $this->primaryKey(),
            'card_id' => $this->integer()->notNull(),
            'rate' => $this->decimal(4, 2)->notNull()->defaultValue(0),
            'effective_from' => $this->date()->notNull(),
            'effective_to' => $this->date()->notNull(),
            'note' => $this->string(255)->null(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->null(),
        ], $tableOptions);

        $this->createIndex('uk-gsl_card_rate-card_from', '{{%gsl_card_rate}}', ['card_id', 'effective_from'], true);
        $this->createIndex('idx-gsl_card_rate-card_range', '{{%gsl_card_rate}}', ['card_id', 'effective_from', 'effective_to']);
        $this->addForeignKey('fk-gsl_card_rate-card', '{{%gsl_card_rate}}', 'card_id', '{{%gsl_card}}', 'id', 'CASCADE', 'CASCADE');

        // 卡所属人 / 公司
        $this->addColumn('{{%gsl_card}}', 'owner_name', $this->string(64)->null()->after('card_type_id'));

        // 卡类型 cash → Subcard（幂等）
        $this->update('{{%gsl_card_type}}', ['code' => 'sub', 'name' => 'Subcard'], ['code' => 'cash']);

        // 刷卡公司可空
        $this->alterColumn('{{%gsl_swipe}}', 'company_id', $this->integer()->null());

        $this->backfillCurrentWeek();
    }

    public function safeDown()
    {
        $this->update(
            '{{%gsl_swipe}}',
            ['company_id' => new \yii\db\Expression('(SELECT MIN(id) FROM {{%gsl_company}})')],
            ['company_id' => null]
        );
        $this->alterColumn('{{%gsl_swipe}}', 'company_id', $this->integer()->notNull());

        $this->update('{{%gsl_card_type}}', ['code' => 'cash', 'name' => 'Cash Card'], ['code' => 'sub']);

        $this->dropColumn('{{%gsl_card}}', 'owner_name');

        $this->dropForeignKey('fk-gsl_card_rate-card', '{{%gsl_card_rate}}');
        $this->dropIndex('idx-gsl_card_rate-card_range', '{{%gsl_card_rate}}');
        $this->dropIndex('uk-gsl_card_rate-card_from', '{{%gsl_card_rate}}');
        $this->dropTable('{{%gsl_card_rate}}');
    }

    /**
     * 用卡上现有费率（owner_rate）回填「本周」费率，保证费率表有基线数据。
     */
    private function backfillCurrentWeek(): void
    {
        $cards = (new \yii\db\Query())
            ->select(['id', 'owner_rate'])
            ->from('{{%gsl_card}}')
            ->all($this->db);

        if (empty($cards)) {
            return;
        }

        [$from, $to] = self::weekOf(date('Y-m-d'));
        $now = time();

        foreach ($cards as $card) {
            $exists = (new \yii\db\Query())
                ->from('{{%gsl_card_rate}}')
                ->where(['card_id' => $card['id'], 'effective_from' => $from])
                ->exists($this->db);
            if ($exists) {
                continue;
            }

            $this->insert('{{%gsl_card_rate}}', [
                'card_id' => $card['id'],
                'rate' => $card['owner_rate'],
                'effective_from' => $from,
                'effective_to' => $to,
                'note' => null,
                'created_at' => $now,
                'updated_at' => null,
            ]);
        }
    }

    /**
     * @return array{0:string,1:string} 含指定日期的周区间（周四 ~ 周三）
     */
    private static function weekOf(string $date): array
    {
        $d = new \DateTimeImmutable($date);
        $n = (int) $d->format('N'); // 1=周一 … 7=周日
        $delta = $n >= 4 ? $n - 4 : $n + 3;
        $thursday = $d->modify('-' . $delta . ' days');

        return [$thursday->format('Y-m-d'), $thursday->modify('+6 days')->format('Y-m-d')];
    }
}
