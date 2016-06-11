<?php

use Phinx\Migration\AbstractMigration;

class AddSubscriptionPlanTable extends AbstractMigration
{
    public function up()
    {
        // create the table
        $table = $this->table('subscription_plan');
        $table->addColumn('locations', 'integer', ['null' => false, 'default' => 0])
            ->addColumn('sms_messages_per_location', 'integer', ['null' => false, 'default' => 0])
            ->addColumn('payment_plan', 'string', ['null' => false, 'default' => 'none', 'comment' => 'FR=Free plan;TR=Trial Plan;M=Monthly;Y=Yearly' ])
            ->addColumn('subscription_pricing_plan_id', 'integer', ['null' => false, 'default' => 0])
            ->addColumn('user_id', 'integer', [ 'signed' => false, 'limit' => 10, 'null' => false, 'default' => 0])
            ->addColumn('created_at', 'timestamp', [ 'null' => false, 'default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', [ 'null' => false ])
            ->addColumn('deleted_at', 'timestamp', [ 'null' => false ])
            ->addForeignKey('subscription_pricing_plan_id', 'subscription_pricing_plan', 'id', [ 'delete' => 'CASCADE', 'update' => 'CASCADE' ])
            ->addForeignKey('user_id', 'users', 'id', [ 'delete' => 'CASCADE', 'update' => 'CASCADE' ])
            ->create();
    }
    
    public function down()
    {
        $this->dropTable('subscription_plan');
    }
}
