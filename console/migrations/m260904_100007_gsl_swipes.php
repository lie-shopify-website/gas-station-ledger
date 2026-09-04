<?php

use yii\db\Migration;

class m260904_100007_gsl_swipes extends Migration
{
    public function safeUp()
    {
        $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';

        $this->createTable('{{%gsl_swipe}}', [
            'id' => $this->primaryKey(),
            'work_date' => $this->date()->notNull(),
            'company_id' => $this->integer()->notNull(),
            'card_id' => $this->integer()->notNull(),
            'liters' => $this->decimal(10, 3)->notNull()->defaultValue(0),
            'swipe_receipt' => $this->string(64)->null(),
            'note' => $this->string(255)->null(),
            'created_at' => $this->integer()->notNull(),
        ], $tableOptions);

        $this->createIndex('idx-gsl_swipe-date_company', '{{%gsl_swipe}}', ['work_date', 'company_id']);
        $this->createIndex('idx-gsl_swipe-card_date', '{{%gsl_swipe}}', ['card_id', 'work_date']);

        $this->addForeignKey('fk-gsl_swipe-company', '{{%gsl_swipe}}', 'company_id', '{{%gsl_company}}', 'id', 'RESTRICT', 'CASCADE');
        $this->addForeignKey('fk-gsl_swipe-card', '{{%gsl_swipe}}', 'card_id', '{{%gsl_card}}', 'id', 'RESTRICT', 'CASCADE');
    }

    public function safeDown()
    {
        $this->dropTable('{{%gsl_swipe}}');
    }
}
