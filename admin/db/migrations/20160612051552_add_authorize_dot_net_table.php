<?php

use Phinx\Migration\AbstractMigration;

class AddAuthorizeDotNetTable extends AbstractMigration
{
    public function up()
    {
        // create the table
        $table = $this->table('authorize_dot_net');
        $table->addColumn('user_id', 'integer', [ 'signed' => false, 'limit' => 10, 'null' => false, 'default' => 0])
              ->addColumn('customer_profile_id', 'string', ['null' => false, 'default' => ''])
              ->addColumn('subscription_id', 'string', ['null' => false, 'default' => 'N'])
              ->addColumn('created_at', 'timestamp', [ 'null' => false, 'default' => 'CURRENT_TIMESTAMP'])
              ->addColumn('updated_at', 'timestamp', [ 'null' => false ])
              ->addColumn('deleted_at', 'timestamp', [ 'null' => false ])
              ->addForeignKey('user_id', 'users', 'id', [ 'delete' => 'CASCADE', 'update' => 'CASCADE' ])
              ->create();
    }
    
    public function down()
    {
        $this->dropTable('authorize_dot_net');
    }
}
