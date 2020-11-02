<?php

namespace craft\commerce\migrations;

use Craft;
use craft\commerce\elements\Subscription;
use craft\commerce\fields\Products;
use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\MigrationHelper;
use yii\db\Expression;

/**
 * m201102_064231_fix_deletedWithProduct_bool migration.
 */
class m201102_064231_fix_deletedWithProduct_bool extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // Now we can set the track column to NOT NULL
        if ($this->db->getIsPgsql()) {
            // Manually construct the SQL for Postgres
            // (see https://github.com/yiisoft/yii2/issues/12077)

            $this->execute('ALTER TABLE {{%commerce_variants}} ALTER COLUMN [[deletedWithProduct]] DROP DEFAULT;');
            $this->execute('ALTER TABLE {{%commerce_variants}} ALTER [[deletedWithProduct]] TYPE bool USING CASE WHEN [[deletedWithProduct]]=1 THEN TRUE CASE WHEN [[deletedWithProduct]]=0 THEN FALSE ELSE NULL END;');
            $this->execute('ALTER TABLE {{%commerce_variants}} ALTER COLUMN [[deletedWithProduct]] SET DEFAULT NULL;');

        } else {
            $this->alterColumn('{{%commerce_variants}}', '[[deletedWithProduct]]', $this->boolean()->null());
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m201102_064231_fix_deletedWithProduct_bool cannot be reverted.\n";
        return false;
    }
}
