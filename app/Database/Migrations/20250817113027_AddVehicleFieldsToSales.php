<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddVehicleFieldsToSales extends Migration
{
    public function up()
    {
        $fields = [
            'vehicle_id' => [
                'type' => 'INT',
                'constraint' => 10,
                'null' => true,
                'after' => 'customer_id'
            ],
            'vehicle_kilometer' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
                'after' => 'vehicle_id'
            ],
            'vehicle_avg_oil_km' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
                'after' => 'vehicle_kilometer'
            ],
            'vehicle_avg_km_day' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
                'after' => 'vehicle_avg_oil_km'
            ]
        ];

        $this->forge->addColumn('sales', $fields);

        // Add index for vehicle_id
        $this->forge->addKey('vehicle_id');
    }

    public function down()
    {
        $this->forge->dropColumn('sales', ['vehicle_id', 'vehicle_kilometer', 'vehicle_avg_oil_km', 'vehicle_avg_km_day']);
    }
}