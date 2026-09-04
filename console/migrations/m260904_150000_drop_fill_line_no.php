<?php

use yii\db\Migration;

class m260904_150000_drop_fill_line_no extends Migration
{
    public function safeUp()
    {
        $this->dropIndex('idx-gsl_fill-line_no', '{{%gsl_fill}}');
        $this->dropColumn('{{%gsl_fill}}', 'line_no');
    }

    public function safeDown()
    {
        $this->addColumn('{{%gsl_fill}}', 'line_no', $this->integer()->notNull()->defaultValue(0));
        $this->createIndex('idx-gsl_fill-line_no', '{{%gsl_fill}}', 'line_no');
    }
}
