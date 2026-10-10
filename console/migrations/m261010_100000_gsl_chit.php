<?php

use yii\db\Migration;

/**
 * 油票登记：新建 gsl_chit 表；gsl_counter 增加 name（柜台显示名）与 operator_no（柜员号）。
 */
class m261010_100000_gsl_chit extends Migration
{
    public function safeUp()
    {
        $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';

        $this->addColumn('{{%gsl_counter}}', 'name', $this->string(32)->null()->after('code'));
        $this->addColumn('{{%gsl_counter}}', 'operator_no', $this->string(32)->null()->after('name'));

        $this->createTable('{{%gsl_chit}}', [
            'id' => $this->primaryKey(),
            'work_date' => $this->date()->notNull(),
            'counter_id' => $this->integer()->notNull(),
            'operator_no' => $this->string(32)->null(),
            'amount' => $this->decimal(12, 2)->notNull()->defaultValue(0),
            'chit_stub_no' => $this->string(64)->null(),
            'receipt_no' => $this->string(64)->null(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], $tableOptions);

        $this->createIndex('idx-gsl_chit-work_date', '{{%gsl_chit}}', 'work_date');
        $this->createIndex('idx-gsl_chit-counter_date', '{{%gsl_chit}}', ['counter_id', 'work_date']);

        $this->addForeignKey('fk-gsl_chit-counter', '{{%gsl_chit}}', 'counter_id', '{{%gsl_counter}}', 'id', 'RESTRICT', 'CASCADE');
    }

    public function safeDown()
    {
        $this->dropTable('{{%gsl_chit}}');
        $this->dropColumn('{{%gsl_counter}}', 'operator_no');
        $this->dropColumn('{{%gsl_counter}}', 'name');
    }
}