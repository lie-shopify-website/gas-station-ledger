<?php

use yii\db\Migration;

class m260904_100009_gsl_seed_counters extends Migration
{
    public function safeUp()
    {
        $rows = [
            ['0001', 1],
            ['0003', 2],
            ['0008', 3],
            ['0009', 4],
            ['0014', 5],
            ['0016', 6],
        ];
        foreach ($rows as $i => [$code, $sort]) {
            $this->insert('{{%gsl_counter}}', [
                'code' => $code,
                'sort_order' => $sort,
            ]);
        }
    }

    public function safeDown()
    {
        $this->delete('{{%gsl_counter}}');
    }
}
