<?php

namespace App\Controllers;

use App\Controllers\Secure_Controller;
use App\Models\Vehicle;

class Vehicles extends Secure_Controller
{
    private Vehicle $vehicle;

    public function __construct()
    {
        parent::__construct('vehicles');
        $this->vehicle = model(Vehicle::class);
    }

    /**
     * Get vehicle suggestions for autocomplete
     */
    public function suggest(): void
    {
        $search = $this->request->getGet('term');
        $suggestions = $this->vehicle->search_vehicles($search);
        
        echo json_encode($suggestions);
    }

    /**
     * Get vehicle by vehicle number
     */
    public function getByVehicleNo(): void
    {
        $vehicle_no = $this->request->getGet('vehicle_no');
        
        if (empty($vehicle_no)) {
            echo json_encode([
                'success' => false,
                'message' => 'Vehicle number is required'
            ]);
            return;
        }

        $vehicle = $this->vehicle->get_by_vehicle_no($vehicle_no);
        
        if ($vehicle) {
            echo json_encode([
                'success' => true,
                'vehicle' => $vehicle
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Vehicle not found'
            ]);
        }
    }

    /**
     * Save vehicle data
     */
    public function save(): void
    {
        $data = [
            'vehicle_no' => $this->request->getPost('vehicle_no'),
            'kilometer' => $this->request->getPost('kilometer'),
            'last_avg_oil_km' => $this->request->getPost('last_avg_oil_km'),
            'last_avg_km_day' => $this->request->getPost('last_avg_km_day'),
            'last_next_visit' => $this->request->getPost('last_next_visit'),
            'last_customer_id' => $this->request->getPost('last_customer_id')
        ];
        // Taken from $this->sale_lib->set_vehicle_data($vehicle_data);
        $this->session->set('vehicle_kilometer', $data['kilometer']);
        $this->session->set('vehicle_avg_oil_km', $data['last_avg_oil_km']);
        $this->session->set('vehicle_avg_km_day', $data['last_avg_km_day']);
        $this->session->set('vehicle_next_visit', $data['last_next_visit']);

        // Get current sale ID if available
        $sale_id = session('current_sale_id') ?? null;
        if ($sale_id) {
            $data['last_bill_id'] = $sale_id;
        }

        $result = $this->vehicle->save_vehicle($data);
        
        echo json_encode([
            'success' => $result,
            'message' => $result ? 'Vehicle saved successfully' : 'Failed to save vehicle'
        ]);
    }

    /**
     * Get vehicle by vehicle number or create new vehicle if not found
     */
    public function getOrCreateByVehicleNo(): void
    {
        // Get parameters without default values to avoid the error
        $vehicle_no = $this->request->getGet('vehicle_no');
        $kilometer = $this->request->getGet('kilometer') ?? '';
        $last_avg_oil_km = $this->request->getGet('last_avg_oil_km') ?? '';
        $last_avg_km_day = $this->request->getGet('last_avg_km_day') ?? '';
        $last_next_visit = $this->request->getGet('last_next_visit') ?? '';
        $last_customer_id = $this->request->getGet('last_customer_id') ?? null;
        
        if (empty($vehicle_no)) {
            echo json_encode([
                'success' => false,
                'message' => 'Vehicle number is required'
            ]);
            return;
        }

        // First try to find existing vehicle
        $vehicle = $this->vehicle->get_by_vehicle_no($vehicle_no);
        
        if ($vehicle) {
            $this->session->set('sales_vehicle', $vehicle->id);
            // Vehicle found - return existing vehicle
            echo json_encode([
                'success' => true,
                'vehicle' => $vehicle,
                'created' => false
            ]);
            return;
        }

        // Vehicle not found - create new vehicle
        try {
            // Prepare vehicle data - convert empty strings to null
            $vehicle_data = [
                'vehicle_no' => $vehicle_no,
                'kilometer' => $kilometer ?: null,
                'last_avg_oil_km' => $last_avg_oil_km ?: null,
                'last_avg_km_day' => $last_avg_km_day ?: null,
                'last_next_visit' => $last_next_visit ?: null,
                'last_customer_id' => $last_customer_id
            ];

            // Get current sale ID if available
            $sale_id = session('current_sale_id') ?? null;
            if ($sale_id) {
                $vehicle_data['last_bill_id'] = $sale_id;
            }

            // Save the new vehicle
            if ($this->vehicle->save_vehicle($vehicle_data)) {
                // Get the newly created vehicle
                $new_vehicle = $this->vehicle->get_by_vehicle_no($vehicle_no);
                
                echo json_encode([
                    'success' => true,
                    'vehicle' => $new_vehicle,
                    'created' => true,
                    'message' => 'New vehicle created: ' . $vehicle_no
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to create new vehicle'
                ]);
            }
        } catch (\Exception $e) {
            log_message('error', 'Vehicle creation error: ' . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Error creating vehicle: ' . $e->getMessage()
            ]);
        }
    }
}