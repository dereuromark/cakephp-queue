
<?php

use Phinx\Migration\AbstractMigration;

class IncreaseDataSize extends AbstractMigration {
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-abstractmigration-class
     */
    public function change() {
        $table = $this->table('queued_tasks');

        $table->changeColumn('data', 'text', [
            'length' => 4294967295,
            'null' => true,
            'default' => null,
        ]);
    }
}
