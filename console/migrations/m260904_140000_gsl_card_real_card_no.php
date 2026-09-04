<?php

use yii\db\Migration;

class m260904_140000_gsl_card_real_card_no extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%gsl_card}}', 'real_card_no', $this->string(64)->null()->after('card_code'));
    }

    public function safeDown()
    {
        $this->dropColumn('{{%gsl_card}}', 'real_card_no');
    }
}
