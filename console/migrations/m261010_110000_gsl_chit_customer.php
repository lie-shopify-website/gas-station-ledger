<?php

use yii\db\Migration;

/**
 * 油票客户：新建 gsl_chit_customer 表；gsl_chit 增加 customer_id（油票客户）与 oil_type（油类型）。
 */
class m261010_110000_gsl_chit_customer extends Migration
{
    public function safeUp()
    {
        $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';

        $this->createTable('{{%gsl_chit_customer}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(64)->notNull(),
            'deposit_amount' => $this->decimal(12, 2)->notNull()->defaultValue(0),
            'is_active' => $this->tinyInteger(1)->notNull()->defaultValue(1),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], $tableOptions);

        $this->createIndex('uk-gsl_chit_customer-name', '{{%gsl_chit_customer}}', 'name', true);

        $this->addColumn('{{%gsl_chit}}', 'customer_id', $this->integer()->null()->after('counter_id'));
        $this->addColumn('{{%gsl_chit}}', 'oil_type', $this->string(32)->null()->after('customer_id'));

        $this->createIndex('idx-gsl_chit-customer_id', '{{%gsl_chit}}', 'customer_id');

        $this->addForeignKey(
            'fk-gsl_chit-customer',
            '{{%gsl_chit}}',
            'customer_id',
            '{{%gsl_chit_customer}}',
            'id',
            'SET NULL',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk-gsl_chit-customer', '{{%gsl_chit}}');
        $this->dropIndex('idx-gsl_chit-customer_id', '{{%gsl_chit}}');
        $this->dropColumn('{{%gsl_chit}}', 'oil_type');
        $this->dropColumn('{{%gsl_chit}}', 'customer_id');
        $this->dropTable('{{%gsl_chit_customer}}');
    }
}