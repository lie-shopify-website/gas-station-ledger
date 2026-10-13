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

        // Deduplicate: same owner_name + effective_from may have multiple rows
        // (from when rate was per-card and one owner had several cards)
        $this->execute('DELETE r1 FROM {{%gsl_card_rate}} r1 INNER JOIN {{%gsl_card_rate}} r2 ON r1.owner_name = r2.owner_name AND r1.effective_from = r2.effective_from AND r1.id < r2.id');

        // Remove rows with empty owner_name (orphaned data)
        $this->delete('{{%gsl_card_rate}}', ['owner_name' => '']);

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
