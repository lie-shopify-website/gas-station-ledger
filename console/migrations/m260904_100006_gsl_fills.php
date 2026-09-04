<?php

use yii\db\Migration;

class m260904_100006_gsl_fills extends Migration
{
    public function safeUp()
    {
        $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';

        $this->createTable('{{%gsl_fill}}', [
            'id' => $this->primaryKey(),
            'work_date' => $this->date()->notNull(),
            'company_id' => $this->integer()->notNull(),
            'plate_id' => $this->integer()->null(),
            'liters' => $this->decimal(10, 3)->notNull()->defaultValue(0),
            'counter_id' => $this->integer()->null(),
            'ticket_no' => $this->string(32)->null(),
            'note' => $this->string(255)->null(),
            'list_price' => $this->decimal(10, 2)->null(),
            'list_amount' => $this->decimal(12, 2)->null(),
            'discount_price' => $this->decimal(10, 2)->null(),
            'amount_due' => $this->decimal(12, 2)->null(),
            'cost' => $this->decimal(12, 2)->null(),
            'line_no' => $this->integer()->notNull()->defaultValue(0),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], $tableOptions);

        $this->createIndex('idx-gsl_fill-work_date', '{{%gsl_fill}}', 'work_date');
        $this->createIndex('idx-gsl_fill-company_date', '{{%gsl_fill}}', ['company_id', 'work_date']);
        $this->createIndex('idx-gsl_fill-counter_date', '{{%gsl_fill}}', ['counter_id', 'work_date']);
        $this->createIndex('idx-gsl_fill-line_no', '{{%gsl_fill}}', 'line_no');

        $this->addForeignKey('fk-gsl_fill-company', '{{%gsl_fill}}', 'company_id', '{{%gsl_company}}', 'id', 'RESTRICT', 'CASCADE');
        $this->addForeignKey('fk-gsl_fill-plate', '{{%gsl_fill}}', 'plate_id', '{{%gsl_plate}}', 'id', 'SET NULL', 'CASCADE');
        $this->addForeignKey('fk-gsl_fill-counter', '{{%gsl_fill}}', 'counter_id', '{{%gsl_counter}}', 'id', 'SET NULL', 'CASCADE');
    }

    public function safeDown()
    {
        $this->dropTable('{{%gsl_fill}}');
    }
}
