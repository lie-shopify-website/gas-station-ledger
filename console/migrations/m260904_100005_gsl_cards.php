<?php

use yii\db\Migration;

class m260904_100005_gsl_cards extends Migration
{
    public function safeUp()
    {
        $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';

        $this->createTable('{{%gsl_card}}', [
            'id' => $this->primaryKey(),
            'card_code' => $this->string(32)->notNull()->unique(),
            'sort_order' => $this->tinyInteger()->notNull()->defaultValue(0),
            'monthly_quota' => $this->decimal(10, 3)->notNull()->defaultValue(500),
            'is_active' => $this->boolean()->notNull()->defaultValue(1),
            'note' => $this->string(255)->null(),
        ], $tableOptions);
    }

    public function safeDown()
    {
        $this->dropTable('{{%gsl_card}}');
    }
}
