<?php

use yii\db\Migration;
use yii\db\Query;

/**
 * 油票预存（Top Up）：新建 gsl_chit_topup 表；把客户表 deposit_amount 现有数值回填为一条期初预存记录；删除 deposit_amount 列。
 */
class m261012_120000_gsl_chit_topup extends Migration
{
    public function safeUp()
    {
        $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';

        $this->createTable('{{%gsl_chit_topup}}', [
            'id' => $this->primaryKey(),
            'customer_id' => $this->integer()->notNull(),
            'amount' => $this->decimal(12, 2)->notNull(),
            'topup_date' => $this->date()->notNull(),
            'note' => $this->string(255)->null(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], $tableOptions);

        $this->createIndex('idx-gsl_chit_topup-customer_id', '{{%gsl_chit_topup}}', 'customer_id');
        $this->createIndex('idx-gsl_chit_topup-topup_date', '{{%gsl_chit_topup}}', 'topup_date');

        $this->addForeignKey(
            'fk-gsl_chit_topup-customer',
            '{{%gsl_chit_topup}}',
            'customer_id',
            '{{%gsl_chit_customer}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        // Backfill: migrate existing deposit_amount into an opening Top Up record per customer.
        $now = time();
        $rows = (new Query())
            ->select(['id', 'deposit_amount'])
            ->from('{{%gsl_chit_customer}}')
            ->where(['<>', 'deposit_amount', 0])
            ->all();

        foreach ($rows as $row) {
            $this->insert('{{%gsl_chit_topup}}', [
                'customer_id' => $row['id'],
                'amount' => $row['deposit_amount'],
                'topup_date' => date('Y-m-d', $now),
                'note' => '期初预存',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $this->dropColumn('{{%gsl_chit_customer}}', 'deposit_amount');
    }

    public function safeDown()
    {
        $this->addColumn('{{%gsl_chit_customer}}', 'deposit_amount', $this->decimal(12, 2)->notNull()->defaultValue(0));
        $this->dropForeignKey('fk-gsl_chit_topup-customer', '{{%gsl_chit_topup}}');
        $this->dropTable('{{%gsl_chit_topup}}');
    }
}