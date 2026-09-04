<?php

use yii\db\Migration;

class m260904_100004_gsl_prices extends Migration
{
    public function safeUp()
    {
        $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';

        $this->createTable('{{%gsl_price_period}}', [
            'id' => $this->primaryKey(),
            'valid_from' => $this->date()->notNull(),
            'valid_to' => $this->date()->notNull(),
            'list_price' => $this->decimal(10, 2)->notNull(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], $tableOptions);

        $this->createTable('{{%gsl_price_discount}}', [
            'id' => $this->primaryKey(),
            'price_period_id' => $this->integer()->notNull(),
            'company_id' => $this->integer()->notNull(),
            'discount_price' => $this->decimal(10, 2)->notNull(),
        ], $tableOptions);
        $this->createIndex('idx-gsl_price_discount-period_company', '{{%gsl_price_discount}}', ['price_period_id', 'company_id'], true);
        $this->addForeignKey('fk-gsl_price_discount-period', '{{%gsl_price_discount}}', 'price_period_id', '{{%gsl_price_period}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk-gsl_price_discount-company', '{{%gsl_price_discount}}', 'company_id', '{{%gsl_company}}', 'id', 'CASCADE', 'CASCADE');
    }

    public function safeDown()
    {
        $this->dropTable('{{%gsl_price_discount}}');
        $this->dropTable('{{%gsl_price_period}}');
    }
}
