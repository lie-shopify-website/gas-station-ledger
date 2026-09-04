<?php

use yii\db\Migration;

class m260904_120000_user_language extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%user}}', 'language', $this->string(8)->notNull()->defaultValue('zh-CN'));
    }

    public function safeDown()
    {
        $this->dropColumn('{{%user}}', 'language');
    }
}
