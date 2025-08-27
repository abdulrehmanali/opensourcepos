<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateItemCategoryTable extends Migration
{
  public function up()
  {
    $this->forge->addField([
      'id' => [
        'type'           => 'INT',
        'unsigned'       => true,
        'auto_increment' => true,
      ],
      'item_id' => [
        'type'       => 'INT',
        'unsigned'   => true,
      ],
      'name' => [
        'type'       => 'VARCHAR',
        'constraint' => '255',
      ],
    ]);
    $this->forge->addKey('id', true);
    $this->forge->createTable('item_categories');
  }

  public function down()
  {
    $this->forge->dropTable('item_categories');
  }
}
