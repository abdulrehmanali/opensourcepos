<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddUnitFieldsToItems extends Migration
{
  public function up()
  {
    $fields = [
      'single_unit_quantity' => [
        'type'       => 'DECIMAL',
        'constraint' => '10,2',
        'default'    => 0,
        'null'       => false,
        'after'      => 'reorder_level' // adjust based on structure
      ],
    ];

    $this->forge->addColumn('items', $fields);
    $fields = [
      'pack_name' => [
        'type'       => 'VARCHAR',
        'constraint' => 50,
        'default'    => 'mL',
        'null'       => false,
        'after'      => 'single_unit_quantity'
      ],
    ];
    $this->forge->addColumn('items', $fields);
  }

  public function down()
  {
    $this->forge->dropColumn('items', 'single_unit_quantity');
    $this->forge->dropColumn('items', 'pack_name');
  }
}
