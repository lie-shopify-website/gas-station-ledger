<?php

use yii\db\Migration;

class m260904_100001_user_extend extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%user}}', 'must_change_password', $this->boolean()->notNull()->defaultValue(1));
        $this->addColumn('{{%user}}', 'password_changed_at', $this->integer()->null());
    }

    public function safeDown()
    {
        $this->dropColumn('{{%user}}', 'password_changed_at');
        $this->dropColumn('{{%user}}', 'must_change_password');
    }
}
