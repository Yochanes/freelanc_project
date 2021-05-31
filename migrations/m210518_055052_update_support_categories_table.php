<?php

use yii\db\Migration;

/**
 * Class m210518_055052_update_support_categories_table
 */
class m210518_055052_update_support_categories_table extends Migration
{
    private $table_name = '{{support_categories}}';

    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn($this->table_name, 'parent_id', $this->integer(11)->notNull()->defaultValue(0)->after('id'));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn($this->table_name, 'parent_id');
    }

}
