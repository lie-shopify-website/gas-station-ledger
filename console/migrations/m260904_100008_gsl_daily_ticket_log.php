<?php

use yii\db\Migration;

class m260904_100008_gsl_daily_ticket_log extends Migration
{
    public function safeUp()
    {
        $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';

        $this->createTable('{{%gsl_daily_ticket_log}}', [
            'id' => $this->primaryKey(),
            'work_date' => $this->date()->notNull()->unique(),
            'tickets_manual' => $this->integer()->null(),
            'updated_at' => $this->integer()->notNull(),
        ], $tableOptions);
    }

    public function safeDown()
    {
        $this->dropTable('{{%gsl_daily_ticket_log}}');
    }
}
