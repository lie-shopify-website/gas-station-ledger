<?php

use yii\db\Migration;

class m261002_100000_gsl_card_types_and_swipe_amounts extends Migration
{
    public function safeUp()
    {
        $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';

        // 卡类型字典表（可继续追加类型）
        $this->createTable('{{%gsl_card_type}}', [
            'id' => $this->primaryKey(),
            'code' => $this->string(32)->notNull()->unique(),
            'name' => $this->string(64)->notNull(),
            'sort_order' => $this->tinyInteger()->notNull()->defaultValue(0),
            'is_active' => $this->boolean()->notNull()->defaultValue(1),
        ], $tableOptions);

        $this->batchInsert('{{%gsl_card_type}}', ['code', 'name', 'sort_order', 'is_active'], [
            ['star', 'Star Card', 10, 1],
            ['cash', 'Cash Card', 20, 1],
        ]);

        // 油卡归属类型
        $this->addColumn('{{%gsl_card}}', 'card_type_id', $this->integer()->null()->after('card_code'));
        $this->createIndex('idx-gsl_card-card_type', '{{%gsl_card}}', 'card_type_id');
        $this->addForeignKey('fk-gsl_card-card_type', '{{%gsl_card}}', 'card_type_id', '{{%gsl_card_type}}', 'id', 'SET NULL', 'CASCADE');

        // 刷卡价格/金额快照
        $this->addColumn('{{%gsl_swipe}}', 'discount_price', $this->decimal(10, 2)->notNull()->defaultValue(0)->after('liters'));
        $this->addColumn('{{%gsl_swipe}}', 'amount_due', $this->decimal(12, 2)->notNull()->defaultValue(0)->after('discount_price'));

        // 刷卡限额默认值（可在系统设置中修改）
        $now = time();
        $this->batchInsert('{{%gsl_setting}}', ['setting_key', 'setting_value', 'updated_at'], [
            ['swipe_max_per_time', '250', $now],
            ['swipe_max_per_card_day', '500', $now],
        ]);
    }

    public function safeDown()
    {
        $this->delete('{{%gsl_setting}}', ['setting_key' => ['swipe_max_per_time', 'swipe_max_per_card_day']]);

        $this->dropColumn('{{%gsl_swipe}}', 'amount_due');
        $this->dropColumn('{{%gsl_swipe}}', 'discount_price');

        $this->dropForeignKey('fk-gsl_card-card_type', '{{%gsl_card}}');
        $this->dropIndex('idx-gsl_card-card_type', '{{%gsl_card}}');
        $this->dropColumn('{{%gsl_card}}', 'card_type_id');

        $this->dropTable('{{%gsl_card_type}}');
    }
}