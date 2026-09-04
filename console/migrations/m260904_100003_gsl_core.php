<?php

use yii\db\Migration;

class m260904_100003_gsl_core extends Migration
{
    public function safeUp()
    {
        $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';

        $this->createTable('{{%gsl_setting}}', [
            'id' => $this->primaryKey(),
            'setting_key' => $this->string(64)->notNull()->unique(),
            'setting_value' => $this->string(255)->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], $tableOptions);

        $this->createTable('{{%gsl_company}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(64)->notNull()->unique(),
            'payment_type' => $this->string(8)->notNull(),
            'sort_order' => $this->tinyInteger()->notNull()->defaultValue(0),
            'is_active' => $this->boolean()->notNull()->defaultValue(1),
        ], $tableOptions);

        $this->createTable('{{%gsl_plate}}', [
            'id' => $this->primaryKey(),
            'company_id' => $this->integer()->notNull(),
            'plate_no' => $this->string(32)->notNull(),
            'is_active' => $this->boolean()->notNull()->defaultValue(1),
        ], $tableOptions);
        $this->createIndex('idx-gsl_plate-company_plate', '{{%gsl_plate}}', ['company_id', 'plate_no'], true);
        $this->addForeignKey('fk-gsl_plate-company', '{{%gsl_plate}}', 'company_id', '{{%gsl_company}}', 'id', 'CASCADE', 'CASCADE');

        $this->createTable('{{%gsl_counter}}', [
            'id' => $this->primaryKey(),
            'code' => $this->char(4)->notNull()->unique(),
            'sort_order' => $this->tinyInteger()->notNull()->defaultValue(0),
        ], $tableOptions);
    }

    public function safeDown()
    {
        $this->dropTable('{{%gsl_plate}}');
        $this->dropTable('{{%gsl_counter}}');
        $this->dropTable('{{%gsl_company}}');
        $this->dropTable('{{%gsl_setting}}');
    }
}
