<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateItemMediaTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'item_id' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],
            'media_type' => [
                'type'       => 'ENUM',
                'constraint' => ['image', 'pdf'],
            ],
            'filename' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
            ],
            'original_name' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => true,
            ],
            'sort_order' => [
                'type'    => 'INT',
                'default' => 0,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('item_id');
        $this->forge->createTable('item_media');
    }

    public function down(): void
    {
        $this->forge->dropTable('item_media');
    }
}
