<?php

namespace App\Models;

use CodeIgniter\Model;

class Vehicle extends Model
{
    protected $table = 'vehicles';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'object';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'vehicle_no',
        'kilometer',
        'last_avg_oil_km',
        'last_avg_km_day',
        'last_next_visit',
        'last_bill_id',
        'last_customer_id'
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    /**
     * Get vehicle by vehicle number
     */
    public function get_by_vehicle_no(string $vehicle_no)
    {
        return $this->where('vehicle_no', $vehicle_no)->first();
    }

    /**
     * Check if vehicle number exists
     */
    public function vehicle_exists(string $vehicle_no, int $exclude_id = null): bool
    {
        $builder = $this->where('vehicle_no', $vehicle_no);
        
        if ($exclude_id) {
            $builder->where('id !=', $exclude_id);
        }
        
        return $builder->countAllResults() > 0;
    }

    /**
     * Save or update vehicle information
     */
    public function save_vehicle(array $data): bool
    {
        // Clean empty values
        foreach ($data as $key => $value) {
            if ($value === '' || $value === null) {
                unset($data[$key]);
            }
        }

        if (isset($data['vehicle_no']) && !empty($data['vehicle_no'])) {
            $existing = $this->get_by_vehicle_no($data['vehicle_no']);
            
            if ($existing) {
                // Update existing vehicle
                return $this->update($existing->id, $data);
            }
        }
        
        // Insert new vehicle only if vehicle_no is provided
        if (isset($data['vehicle_no']) && !empty($data['vehicle_no'])) {
            return $this->insert($data) !== false;
        }
        
        return false;
    }

    /**
     * Get vehicle history/records
     */
    public function get_vehicle_history(string $vehicle_no, int $limit = 10)
    {
        return $this->where('vehicle_no', $vehicle_no)
                   ->orderBy('updated_at', 'DESC')
                   ->limit($limit)
                   ->find();
    }

    /**
     * Search vehicles
     */
    public function search_vehicles(string $search, int $limit = 25): array
    {
        $results = $this->like('vehicle_no', $search)
                       ->limit($limit)
                       ->orderBy('vehicle_no', 'ASC')
                       ->findAll();

        $suggestions = [];
        foreach ($results as $vehicle) {
            $suggestions[] = [
                'value' => $vehicle->vehicle_no,
                'label' => $vehicle->vehicle_no . ' (KM: ' . ($vehicle->kilometer ?? 'N/A') . ')'
            ];
        }

        return $suggestions;
    }
    
}