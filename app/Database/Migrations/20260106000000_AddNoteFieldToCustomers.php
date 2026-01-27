<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddNoteFieldToCustomers extends Migration
{
    public function up()
    {
        $fields = [
            'note' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'deleted'
            ]
        ];

        $this->forge->addColumn('customers', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('customers', ['note']);
    }
}
