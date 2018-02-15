<?php

namespace Bolt\Extension\TwoKings\EditorsTrack\Storage\Schema\Table;

use Bolt\Storage\Database\Schema\Table\BaseTable;

/**
 * ActionsTable class will create the table to store
 * editors actions.
 *
 * @author Néstor de Dios Fernández <nestor@twokings.nl>
 */

class ActionsTable extends BaseTable
{
    /**
     * {@inheritdoc}
     */
    protected function addColumns()
    {
        $this->table->addColumn('id',        'integer', ['autoincrement' => true]);
        $this->table->addColumn('user_id',     'integer',  []);
        $this->table->addColumn('performed_on_contenttype', 'string', []);
        $this->table->addColumn('performed_on_record_id', 'integer', []);
        $this->table->addColumn('performed_action', 'string', []);
        $this->table->addColumn('performed_date', 'datetime', []);
    }

    protected function addIndexes()
    {
        // This will create a joint index of the columns
        $this->table->addIndex(['user_id', 'performed_on_record_id', 'performed_date', 'performed_action']);
    }

    /**
     * {@inheritdoc}
     */
    protected function setPrimaryKey()
    {
        $this->table->setPrimaryKey(['id']);
    }
}