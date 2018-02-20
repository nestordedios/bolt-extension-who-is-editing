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
        $this->table->addColumn('id', 'integer', ['autoincrement' => true]);
        $this->table->addColumn('user_id', 'integer',  []);
        $this->table->addColumn('contenttype', 'string', []);
        $this->table->addColumn('record_id', 'integer', []);
        $this->table->addColumn('action', 'string', []);
        $this->table->addColumn('date', 'datetime', []);
    }

    protected function addIndexes()
    {
        $this->table->addIndex(['user_id']);
        $this->table->addIndex(['contenttype', 'record_id']);
    }

    /**
     * {@inheritdoc}
     */
    protected function setPrimaryKey()
    {
        $this->table->setPrimaryKey(['id']);
    }
}