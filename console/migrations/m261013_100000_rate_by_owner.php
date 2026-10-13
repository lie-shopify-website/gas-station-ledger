<?php

use yii\db\Migration;

/**
 * Changes gsl_card_rate from per-card to per-owner-name.
 */
class m261013_100000_rate_by_owner extends Migration
{
    public function safeUp()
    {
        // Add owner_name column
        $this->addColumn('{{%gsl_card_rate}}', 'owner_name', $this->string(64)->notNull()->defaultValue(''));

        // Backfill: map card_id → owner_name from gsl_card
        $this->execute('UPDATE {{%gsl_card_rate}} r INNER JOIN {{%gsl_card}} c ON r.card_id = c.id SET r.owner_name = COALESCE(c.owner_name, "")');

        // Drop foreign key FIRST (MySQL requires this before dropping the index it depends on)
        $this->dropForeignKey('fk-gsl_card_rate-card', '{{%gsl_card_rate}}');

        // Drop old indexes (uk- may or may not exist depending on partial runs)
        $indexes = $this->db->createCommand('SHOW INDEX FROM {{%gsl_card_rate}}')->queryAll();
        $names = array_unique(array_column($indexes, 'Key_name'));
        foreach (['uk-gsl_card_rate-card_from', 'idx-gsl_card_rate-card_range'] as $idx) {
            if (in_array($idx, $names, true)) {
                $this->dropIndex($idx, '{{%gsl_card_rate}}');
            }
        }

        // Drop card_id column
        $this->dropColumn('{{%gsl_card_rate}}', 'card_id');

        // New indexes
        $this->createIndex('uk-gsl_card_rate-owner_from', '{{%gsl_card_rate}}', ['owner_name', 'effective_from'], true);
        $this->createIndex('idx-gsl_card_rate-owner_range', '{{%gsl_card_rate}}', ['owner_name', 'effective_from', 'effective_to']);
    }

    public function safeDown()
    {
        $this->dropIndex('uk-gsl_card_rate-owner_from', '{{%gsl_card_rate}}');
        $this->dropIndex('idx-gsl_card_rate-owner_range', '{{%gsl_card_rate}}');

        $this->addColumn('{{%gsl_card_rate}}', 'card_id', $this->integer()->notNull()->defaultValue(0));
        $this->addForeignKey('fk-gsl_card_rate-card', '{{%gsl_card_rate}}', 'card_id', '{{%gsl_card}}', 'id', 'CASCADE', 'CASCADE');
        $this->createIndex('uk-gsl_card_rate-card_from', '{{%gsl_card_rate}}', ['card_id', 'effective_from'], true);
        $this->createIndex('idx-gsl_card_rate-card_range', '{{%gsl_card_rate}}', ['card_id', 'effective_from', 'effective_to']);

        $this->dropColumn('{{%gsl_card_rate}}', 'owner_name');
    }
}
