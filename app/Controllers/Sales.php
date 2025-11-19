<?php

namespace App\Controllers;

use App\Libraries\Barcode_lib;
use App\Libraries\Email_lib;
use App\Libraries\Sale_lib;
use App\Libraries\Tax_lib;
use App\Libraries\Token_lib;
use App\Models\Customer;
use App\Models\Customer_rewards;
use App\Models\Dinner_table;
use App\Models\Employee;
use App\Models\Giftcard;
use App\Models\Inventory;
use App\Models\Item;
use App\Models\Item_kit;
use App\Models\Sale;
use App\Models\Stock_location;
use App\Models\Tokens\Token_invoice_count;
use App\Models\Tokens\Token_customer;
use App\Models\Tokens\Token_invoice_sequence;
use App\Models\Vehicle;
use Config\Services;
use Config\OSPOS;
use ReflectionException;
use stdClass;

class Sales extends Secure_Controller
{
  protected $helpers = ['file'];
  private Barcode_lib $barcode_lib;
  private Email_lib $email_lib;
  private Sale_lib $sale_lib;
  private Tax_lib $tax_lib;
  private Token_lib $token_lib;
  private Customer $customer;
  private Vehicle $vehicle;
  private Customer_rewards $customer_rewards;
  private Dinner_table $dinner_table;
  protected Employee $employee;
  private Item $item;
  private Item_kit $item_kit;
  private Sale $sale;
  private Stock_location $stock_location;
  private array $config;
  private $db;

  public function __construct()
  {
    parent::__construct('sales');

    $this->session = session();
    $this->barcode_lib = new Barcode_lib();
    $this->email_lib = new Email_lib();
    $this->sale_lib = new Sale_lib();
    $this->tax_lib = new Tax_lib();
    $this->token_lib = new Token_lib();
    $this->config = config(OSPOS::class)->settings;

    $this->customer = model(Customer::class);
    $this->sale = model(Sale::class);
    $this->item = model(Item::class);
    $this->item_kit = model(Item_kit::class);
    $this->stock_location = model(Stock_location::class);
    $this->customer_rewards = model(Customer_rewards::class);
    $this->dinner_table = model(Dinner_table::class);
    $this->employee = model(Employee::class);
    $this->db = db_connect();
  }

  /**
   * @return void
   */
  public function getIndex(): void
  {
    $this->session->set('allow_temp_items', 1);
    $this->_reload();    //TODO: Hungarian Notation
  }

  /**
   * Load the sale edit modal. Used in app/Views/sales/register.php.
   *
   * @return void
   * @noinspection PhpUnused
   */
  public function getManage(): void
  {
    $person_id = $this->session->get('person_id');

    if (!$this->employee->has_grant('reports_sales', $person_id)) {
      redirect('no_access/sales/reports_sales');
    } else {
      $data['table_headers'] = get_sales_manage_table_headers();

      $data['filters'] = [
        'only_cash' => lang('Sales.cash_filter'),
        'only_due' => lang('Sales.due_filter'),
        'only_check' => lang('Sales.check_filter'),
        'only_creditcard' => lang('Sales.credit_filter'),
        'only_invoices' => lang('Sales.invoice_filter'),
        'selected_customer' => lang('Sales.selected_customer')
      ];

      if ($this->sale_lib->get_customer() != -1) {
        $selected_filters = ['selected_customer'];
        $data['customer_selected'] = true;
      } else {
        $data['customer_selected'] = false;
        $selected_filters = [];
      }
      $data['selected_filters'] = $selected_filters;

      echo view('sales/manage', $data);
    }
  }

  /**
   * @param int $row_id
   * @return void
   */
  public function getRow(int $row_id): void
  {
    $sale_info = $this->sale->get_info($row_id)->getRow();
    $data_row = get_sale_data_row($sale_info);

    echo json_encode($data_row);
  }

  /**
   * @return void
   */
  public function getSearch(): void
  {
    $search = $this->request->getGet('search', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $limit = $this->request->getGet('limit', FILTER_SANITIZE_NUMBER_INT);
    $offset = $this->request->getGet('offset', FILTER_SANITIZE_NUMBER_INT);
    $sort = $this->sanitizeSortColumn(sales_headers(), $this->request->getGet('sort', FILTER_SANITIZE_FULL_SPECIAL_CHARS), 'sale_id');
    $order = $this->request->getGet('order', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    $filters = [
      'sale_type' => 'all',
      'location_id' => 'all',
      'start_date' => $this->request->getGet('start_date', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
      'end_date' => $this->request->getGet('end_date', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
      'only_cash' => false,
      'only_due' => false,
      'only_check' => false,
      'selected_customer' => false,
      'only_creditcard' => false,
      'only_invoices' => $this->config['invoice_enable'] && $this->request->getGet('only_invoices', FILTER_SANITIZE_NUMBER_INT),
      'is_valid_receipt' => $this->sale->is_valid_receipt($search)
    ];

    // check if any filter is set in the multiselect dropdown
    $request_filters = array_fill_keys($this->request->getGet('filters', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? [], true);
    $filters = array_merge($filters, $request_filters);

    $sales = $this->sale->search($search, $filters, $limit, $offset, $sort, $order);
    $total_rows = $this->sale->get_found_rows($search, $filters);
    $payments = $this->sale->get_payments_summary($search, $filters);
    $payment_summary = get_sales_manage_payments_summary($payments);

    $data_rows = [];
    foreach ($sales->getResult() as $sale) {
      $data_rows[] = get_sale_data_row($sale);
    }

    if ($total_rows > 0) {
      $data_rows[] = get_sale_data_last_row($sales);
    }

    echo json_encode(['total' => $total_rows, 'rows' => $data_rows, 'payment_summary' => $payment_summary]);
  }

  /**
   * Gets search suggestions for an item or item kit. Used in app/Views/sales/register.php.
   *
   * @return void
   * @noinspection PhpUnused
   */
  public function getItemSearch(): void
  {
    $suggestions = [];
    $receipt = $search = $this->request->getGet('term') != ''
      ? $this->request->getGet('term')
      : null;

    if ($this->sale_lib->get_mode() == 'return' && $this->sale->is_valid_receipt($receipt)) {
      // if a valid receipt or invoice was found the search term will be replaced with a receipt number (POS #)
      $suggestions[] = $receipt;
    }
    $suggestions = array_merge($suggestions, $this->item->get_search_suggestions($search, ['search_custom' => false, 'is_deleted' => 0], true));
    $suggestions = array_merge($suggestions, $this->item_kit->get_search_suggestions($search));

    echo json_encode($suggestions);
  }

  /**
   * Initialize or get a sale record for the React sales register
   * Creates a new sale if sale_id not provided or doesn't exist
   * Loads existing sale with all related data if sale_id is valid
   * 
   * @return void
   * @noinspection PhpUnused
   */
  public function getInitSale(): void
  {
    $sale_id = $this->request->getGet('sale_id', FILTER_SANITIZE_NUMBER_INT);
    
    // If sale_id provided and valid, load and return it
    if($sale_id) {
      // Query the sales table directly to get sale info (don't use get_info which requires items)
      $sale_record = $this->db->table('sales')
        ->where('sale_id', $sale_id)
        ->get()
        ->getRow();
      
      if($sale_record) {
        $existing_sale = $sale_record;
        
        // Load associated vehicle data if vehicle_id exists and is not -1 (no vehicle)
        $vehicle_data = null;
        if(!empty($existing_sale->vehicle_id) && $existing_sale->vehicle_id != -1) {
          $vehicleModel = model(Vehicle::class);
          $vehicle_data = $vehicleModel->find($existing_sale->vehicle_id);
        }
        
        // Load customer data if customer_id exists
        $customer_data = null;
        if(!empty($existing_sale->customer_id) && $existing_sale->customer_id != -1) {
          $customer_data = $this->customer->get_info($existing_sale->customer_id);
        }
        
        // Load cart items for this sale from sales_items table with item details
        $cart_items = [];
        $items = $this->db->table('sales_items')
          ->select('sales_items.item_id, sales_items.line, items.name, sales_items.quantity_purchased, sales_items.item_unit_price, sales_items.discount, sales_items.discount_type, sales_items.description')
          ->join('items', 'items.item_id = sales_items.item_id', 'LEFT')
          ->where('sales_items.sale_id', $sale_id)
          ->get()
          ->getResult();
        
        if($items) {
          foreach($items as $item) {
            $cart_items[] = [
              'item_id' => $item->item_id,
              'line' => $item->line,
              'name' => $item->name ?? 'Unknown Item',
              'quantity_purchased' => $item->quantity_purchased,
              'item_unit_price' => $item->item_unit_price,
              'discount' => $item->discount,
              'discount_type' => $item->discount_type,
              'description' => $item->description ?? ''
            ];
          }
        }
        
        // Load payments for this sale from sales_payments table
        $payments_data = [];
        $payments = $this->db->table('sales_payments')
          ->where('sale_id', $sale_id)
          ->get()
          ->getResult();
        
        if($payments) {
          foreach($payments as $payment) {
            $payments_data[] = [
              'payment_id' => $payment->payment_id,
              'payment_type' => $payment->payment_type,
              'payment_amount' => $payment->payment_amount,
              'payment_time' => $payment->payment_time
            ];
          }
        }
        
        echo json_encode([
          'success' => true,
          'sale_id' => $sale_id,
          'sale' => $existing_sale,
          'vehicle' => $vehicle_data,
          'customer' => $customer_data,
          'cart_items' => $cart_items,
          'payments' => $payments_data,
          'message' => 'Sale loaded'
        ]);
        return;
      }
    }
    
    // Create new sale record if no valid sale_id was provided or sale doesn't exist
    $new_sale_data = [
        'customer_id' => -1,
        'employee_id' => $this->employee->get_logged_in_employee_info()->person_id,
        'location_id' => $this->sale_lib->get_sale_location(),
        'sale_time' => date('Y-m-d H:i:s'),
        'comment' => '',
        'mechanic_name' => '',
        'total' => 0.00,
        'amount_due' => 0.00,
        'sale_type' => 0 // SALE type (from config constants)
      ];
      
      // Insert new sale
      $this->sale->insert($new_sale_data);
      $new_sale_id = (int)$this->db->insertID();
      
      echo json_encode([
        'success' => true,
        'sale_id' => $new_sale_id,
        'message' => 'New sale created'
      ]);
  }

  /**
   * Save sale header data (customer, vehicle, comments, etc.)
   * Used by React sales register to persist changes directly to DB
   * 
   * @return void
   * @noinspection PhpUnused
   */
  public function postSaveSaleData(): void
  {
    $sale_id = $this->request->getPost('sale_id', FILTER_SANITIZE_NUMBER_INT);
    $customer_id = $this->request->getPost('customer_id', FILTER_SANITIZE_NUMBER_INT);
    $vehicle_id = $this->request->getPost('vehicle_id', FILTER_SANITIZE_NUMBER_INT);
    $comment = $this->request->getPost('comment', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $mechanic_name = $this->request->getPost('mechanic_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    
    // Payment data for saving
    $payment_type = $this->request->getPost('payment_type', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $payment_amount = $this->request->getPost('payment_amount', FILTER_SANITIZE_NUMBER_FLOAT);
    
    // Vehicle data for saving
    $vehicle_no = $this->request->getPost('vehicle_no', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $vehicle_kilometer = $this->request->getPost('vehicle_kilometer', FILTER_SANITIZE_NUMBER_FLOAT);
    $vehicle_avg_oil_km = $this->request->getPost('vehicle_avg_oil_km', FILTER_SANITIZE_NUMBER_FLOAT);
    $vehicle_avg_km_day = $this->request->getPost('vehicle_avg_km_day', FILTER_SANITIZE_NUMBER_FLOAT);
    $vehicle_next_visit = $this->request->getPost('vehicle_next_visit', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    
    if(!$sale_id) {
      echo json_encode([
        'success' => false,
        'message' => 'Invalid sale ID'
      ]);
      return;
    }
    
    // Update sale record with customer, mechanic name, comment, and vehicle data
    $update_data = [];
    if($customer_id !== null) {
      $update_data['customer_id'] = $customer_id;
    }
    if($comment !== null) {
      $update_data['comment'] = $comment;
    }
    if($mechanic_name !== null) {
      $update_data['mechanic_name'] = $mechanic_name;
    }

        // Save vehicle data if vehicle_no is provided
    $saved_vehicle_id = null;
    if($vehicle_no) {
      $vehicleModel = model(Vehicle::class);
      $vehicle_data = [
        'vehicle_no' => $vehicle_no,
        'kilometer' => $vehicle_kilometer ? (float)$vehicle_kilometer : 0,
        'last_avg_oil_km' => $vehicle_avg_oil_km ? (float)$vehicle_avg_oil_km : 0,
        'last_avg_km_day' => $vehicle_avg_km_day ? (float)$vehicle_avg_km_day : 0,
        'last_next_visit' => $vehicle_next_visit ? $vehicle_next_visit : '',
        'last_customer_id' => $customer_id ? (int)$customer_id : -1
      ];
      
      // Insert or update vehicle
      $existing_vehicle = $vehicleModel->where('vehicle_no', $vehicle_no)->first();
      if($existing_vehicle) {
        $vehicleModel->update($existing_vehicle->id, $vehicle_data);
        $saved_vehicle_id = $existing_vehicle->id;
      } else {
        $vehicleModel->insert($vehicle_data);
        $saved_vehicle_id = $vehicleModel->getInsertID();
      }
    }
    
    // Add vehicle data to sales table
    if($saved_vehicle_id !== null && $saved_vehicle_id > 0) {
      $update_data['vehicle_id'] = $saved_vehicle_id;
    }
    if($vehicle_kilometer !== null) {
      $update_data['vehicle_kilometer'] = (float)$vehicle_kilometer;
    }
    if($vehicle_avg_oil_km !== null) {
      $update_data['vehicle_avg_oil_km'] = (float)$vehicle_avg_oil_km;
    }
    if($vehicle_avg_km_day !== null) {
      $update_data['vehicle_avg_km_day'] = (float)$vehicle_avg_km_day;
    }
    
    if(!empty($update_data)) {
      $this->sale->update($sale_id, $update_data);
    }
    
    // Save payment if provided
    if($payment_type && $payment_amount !== null && $payment_amount > 0) {
      try {
        // Create a payment entry using sale_lib
        $this->sale_lib->add_payment($payment_type, (float)$payment_amount);
      } catch (\Exception $e) {
        // Log error but don't block the save
        log_message('error', 'Payment save error: ' . $e->getMessage());
      }
    }
    
    // Update session for compatibility
    if($customer_id !== null) {
      $this->sale_lib->set_customer($customer_id);
    }
    
    echo json_encode([
      'success' => true,
      'message' => 'Sale data updated',
      'sale_id' => $sale_id
    ]);
  }

  /**
   * Get the previous sale ID for navigation
   * Used by sales register to navigate backward through sales
   *
   * @param int $sale_id
   * @return void
   * @noinspection PhpUnused
   */
  /**
   * Get the previous sale ID for navigation
   * Dynamically queries database to find the sale with the ID right before the current one
   *
   * @param int $sale_id
   * @return void
   * @noinspection PhpUnused
   */
  public function getPreviousSale($sale_id = null): void
  {
    if (!$sale_id) {
      echo json_encode(['success' => false, 'message' => 'Invalid sale ID']);
      return;
    }

    // Find the sale with the highest ID that is less than the current sale_id
    $previous = $this->db->table('sales')
      ->select('sale_id')
      ->where('sale_id <', $sale_id)
      ->orderBy('sale_id', 'DESC')
      ->limit(1)
      ->get()
      ->getRow();

    if ($previous) {
      echo json_encode(['success' => true, 'sale_id' => $previous->sale_id]);
    } else {
      echo json_encode(['success' => false, 'message' => 'No previous sale']);
    }
  }

  /**
   * Get the next sale ID for navigation
   * Dynamically queries database to find the sale with the ID right after the current one
   *
   * @param int $sale_id
   * @return void
   * @noinspection PhpUnused
   */
  public function getNextSale($sale_id = null): void
  {
    if (!$sale_id) {
      echo json_encode(['success' => false, 'message' => 'Invalid sale ID']);
      return;
    }

    // Find the sale with the lowest ID that is greater than the current sale_id
    $next = $this->db->table('sales')
      ->select('sale_id')
      ->where('sale_id >', $sale_id)
      ->orderBy('sale_id', 'ASC')
      ->limit(1)
      ->get()
      ->getRow();

    if ($next) {
      echo json_encode(['success' => true, 'sale_id' => $next->sale_id]);
    } else {
      echo json_encode(['success' => false, 'message' => 'No next sale']);
    }
  }

  /**
   * Return current cart via AJAX
   * Used by the React frontend at /sales/getCart
   * If sale_id is provided, load items from database for that sale
   * Otherwise, return the session-based cart
   *
   * @return void
   * @noinspection PhpUnused
   */
  public function getCart(): void
  {
    // Check both GET and POST for sale_id parameter
    $sale_id = $this->request->getGet('sale_id') ?? $this->request->getPost('sale_id');
    $sale_id = $this->request->getVar('sale_id', FILTER_SANITIZE_NUMBER_INT);
    
    if ($sale_id) {
      // Load cart items for specific sale from database
      try {
        $items = $this->db->table('sales_items')
          ->select('sales_items.item_id, sales_items.line, items.name, sales_items.quantity_purchased, 
                    sales_items.item_unit_price, sales_items.discount, sales_items.discount_type, 
                    sales_items.description')
          ->join('items', 'items.item_id = sales_items.item_id', 'LEFT')
          ->where('sales_items.sale_id', $sale_id)
          ->orderBy('sales_items.line', 'ASC')
          ->get()
          ->getResult();
        
        // Convert to proper format for frontend
        $cart = [];
        if ($items) {
          foreach ($items as $item) {
            $cart[] = [
              'item_id' => $item->item_id,
              'line' => $item->line,
              'name' => $item->name ?? 'Unknown Item',
              'quantity_purchased' => $item->quantity_purchased,
              'quantity' => $item->quantity_purchased,
              'item_unit_price' => $item->item_unit_price,
              'price' => $item->item_unit_price,
              'discount' => $item->discount,
              'discount_type' => $item->discount_type,
              'description' => $item->description ?? ''
            ];
          }
        }
        
        echo json_encode([
          'success' => true,
          'cart' => $cart
        ]);
      } catch (\Exception $e) {
        echo json_encode([
          'success' => false,
          'message' => 'Error loading cart items: ' . $e->getMessage(),
          'cart' => []
        ]);
      }
    } else {
      // Fall back to current session-based cart if no sale_id provided
      $cart = $this->sale_lib->get_cart();

      echo json_encode([
        'success' => true,
        'cart' => $cart
      ]);
    }
  }

  /**
   * Return the currently selected customer for the sale (if any)
   * Used by the React frontend to pre-fill customer fields.
   *
   * @return void
   * @noinspection PhpUnused
   */
  public function getCurrentCustomer(): void
  {
    $customer_id = $this->sale_lib->get_customer();

    if ($customer_id != NEW_ENTRY && $customer_id != -1 && $this->customer->exists($customer_id)) {
      $cust = $this->customer->get_info($customer_id);

      $customer = [
        'person_id' => $customer_id,
        'first_name' => $cust->first_name ?? '',
        'last_name' => $cust->last_name ?? '',
        'full_name' => !empty($cust->company_name) ? $cust->company_name : trim(($cust->first_name ?? '') . ' ' . ($cust->last_name ?? '')),
        'phone_number' => $cust->phone_number ?? '',
        'email' => $cust->email ?? ''
      ];

      // Also include vehicle information where available. Prefer the currently-selected vehicle
      $vehicle_info = null;
      $vehicle_id = $this->sale_lib->get_vehicle();
      $vehicleModel = model(Vehicle::class);
      if ($vehicle_id && $vehicle_id != NEW_ENTRY && $vehicle_id != -1) {
        $vehicle = $vehicleModel->find($vehicle_id);
        if ($vehicle) {
          $vehicle_info = [
            'vehicle_no' => $vehicle->vehicle_no ?? '',
            'kilometer' => $vehicle->kilometer ?? '',
            'last_avg_oil_km' => $vehicle->last_avg_oil_km ?? '',
            'last_avg_km_day' => $vehicle->last_avg_km_day ?? '',
            'last_next_visit' => $vehicle->last_next_visit ?? ''
          ];
        }
      } else {
        // If no selected vehicle, try to find the most recent vehicle for this customer
        $recent = $vehicleModel->where('last_customer_id', $customer_id)->orderBy('updated_at', 'DESC')->first();
        if ($recent) {
          $vehicle_info = [
            'vehicle_no' => $recent->vehicle_no ?? '',
            'kilometer' => $recent->kilometer ?? '',
            'last_avg_oil_km' => $recent->last_avg_oil_km ?? '',
            'last_avg_km_day' => $recent->last_avg_km_day ?? '',
            'last_next_visit' => $recent->last_next_visit ?? ''
          ];
        }
      }

      echo json_encode(['success' => true, 'customer' => $customer, 'vehicle' => $vehicle_info]);
    } else {
      echo json_encode(['success' => false, 'customer' => null]);
    }
  }

  /**
   * @return void
   */
  public function suggest_search(): void
  {
    $search = $this->request->getPost('term') != ''
      ? $this->request->getPost('term')
      : null;

    $suggestions = $this->sale->get_search_suggestions($search);

    echo json_encode($suggestions);
  }

  /**
   * Set a given customer. Used in app/Views/sales/register.php.
   *
   * @return void
   * @noinspection PhpUnused
   */
  public function postSelectCustomer(): void
  {
    $customer_id = (int)$this->request->getPost('customer', FILTER_SANITIZE_NUMBER_INT);
    if ($this->customer->exists($customer_id)) {
      $this->sale_lib->set_customer($customer_id);
      $discount = $this->customer->get_info($customer_id)->discount;
      $discount_type = $this->customer->get_info($customer_id)->discount_type;

      // apply customer default discount to items that have 0 discount
      if ($discount != '') {
        $this->sale_lib->apply_customer_discount($discount, $discount_type);
      }
    }

    $this->_reload();
  }

  /**
   * Set a given vehicle. Used in app/Views/sales/register.php.
   *
   * @return void
   * @noinspection PhpUnused
   */
  public function postSelectVehicle(): void
  {
    $vehicle_id = (int)$this->request->getPost('vehicle', FILTER_SANITIZE_NUMBER_INT);
    if ($this->vehicle->exists($vehicle_id)) {
      $this->sale_lib->set_vehicle($vehicle_id);
    }
    $this->_reload();
  }

  /**
   * Changes the sale mode in the register to carry out different types of sales
   *
   * @return void
   * @noinspection PhpUnused
   */
  public function postChangeMode(): void
  {
    $mode = $this->request->getPost('mode', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $this->sale_lib->set_mode($mode);

    if ($mode == 'sale') {
      $this->sale_lib->set_sale_type(SALE_TYPE_POS);
    } else if ($mode == 'sale_quote') {
      $this->sale_lib->set_sale_type(SALE_TYPE_QUOTE);
    } else if ($mode == 'sale_work_order') {
      $this->sale_lib->set_sale_type(SALE_TYPE_WORK_ORDER);
    } else if ($mode == 'sale_invoice') {
      $this->sale_lib->set_sale_type(SALE_TYPE_INVOICE);
    } else {
      $this->sale_lib->set_sale_type(SALE_TYPE_RETURN);
    }

    if ($this->config['dinner_table_enable']) {
      $occupied_dinner_table = $this->request->getPost('dinner_table', FILTER_SANITIZE_NUMBER_INT);
      $released_dinner_table = $this->sale_lib->get_dinner_table();
      $occupied = $this->dinner_table->is_occupied($released_dinner_table);

      if ($occupied && ($occupied_dinner_table != $released_dinner_table)) {
        $this->dinner_table->swap_tables($released_dinner_table, $occupied_dinner_table);
      }

      $this->sale_lib->set_dinner_table($occupied_dinner_table);
    }

    $stock_location = $this->request->getPost('stock_location', FILTER_SANITIZE_NUMBER_INT);

    if (!$stock_location || $stock_location == $this->sale_lib->get_sale_location()) {
      //TODO: The code below was removed in 2017 by @steveireland. We either need to reinstate some of it or remove this entire if block but we can't leave an empty if block
      //            $dinner_table = $this->request->getPost('dinner_table');
      //            $this->sale_lib->set_dinner_table($dinner_table);
    } elseif ($this->stock_location->is_allowed_location($stock_location, 'sales')) {
      $this->sale_lib->set_sale_location($stock_location);
    }

    $this->sale_lib->empty_payments();

    $this->_reload();
  }

  /**
   * @param int $sale_type
   * @return void
   */
  public function change_register_mode(int $sale_type): void
  {
    $mode = match ($sale_type) {
      SALE_TYPE_QUOTE => 'sale_quote',
      SALE_TYPE_WORK_ORDER => 'sale_work_order',
      SALE_TYPE_INVOICE => 'sale_invoice',
      SALE_TYPE_RETURN => 'return',
      default => 'sale' //SALE_TYPE_POS
    };

    $this->sale_lib->set_mode($mode);
  }


  /**
   * Sets the sales comment. Used in app/Views/sales/register.php
   *
   * @return void
   * @noinspection PhpUnused
   */
  public function postSetComment(): void
  {
    $this->sale_lib->set_comment($this->request->getPost('comment', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
  }

  /**
   * Sets the invoice number. Used in app/Views/sales/register.php
   *
   * @return void
   * @noinspection PhpUnused
   */
  public function postSetInvoiceNumber(): void
  {
    $this->sale_lib->set_invoice_number($this->request->getPost('sales_invoice_number', FILTER_SANITIZE_NUMBER_INT));
  }

  /**
   * @return void
   */
  public function postSetPaymentType(): void    //TODO: This function does not appear to be called anywhere in the code.
  {
    $this->sale_lib->set_payment_type($this->request->getPost('selected_payment_type', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    $this->_reload();    //TODO: Hungarian notation.
  }

  /**
   * Sets PrintAfterSale flag. Used in app/Views/sales/register.php
   *
   * @return void
   * @noinspection PhpUnused
   */
  public function postSetPrintAfterSale(): void
  {
    $this->sale_lib->set_print_after_sale($this->request->getPost('sales_print_after_sale') != 'false');
  }

  /**
   * Sets the flag to include prices in the work order. Used in app/Views/sales/register.php
   *
   * @return void
   * @noinspection PhpUnused
   */
  public function postSetPriceWorkOrders(): void
  {
    $price_work_orders = parse_decimals($this->request->getPost('price_work_orders'));
    $this->sale_lib->set_price_work_orders($price_work_orders);
  }

  /**
   * Sets the flag to email receipt to the customer. Used in app/Views/sales/register.php
   *
   * @return void
   * @noinspection PhpUnused
   */
  public function postSetEmailReceipt(): void
  {
    $this->sale_lib->set_email_receipt($this->request->getPost('email_receipt', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
  }

  /**
   * Add a payment to the sale. Used in app/Views/sales/register.php
   *
   * @return void
   * @noinspection PhpUnused
   */
  public function postAddPayment(): void
  {
    $data = [];
    $giftcard = model(Giftcard::class);
    $payment_type = $this->request->getPost('payment_type', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    if ($payment_type !== lang('Sales.giftcard')) {
      $rules = ['amount_tendered' => 'trim|required|decimal_locale',];
      $messages = ['amount_tendered' => lang('Sales.must_enter_numeric')];
    } else {
      $rules = ['amount_tendered' => 'trim|required',];
      $messages = ['amount_tendered' => lang('Sales.must_enter_numeric_giftcard')];
    }

    if (!$this->validate($rules, $messages)) {
      $data['error'] = $payment_type === lang('Sales.giftcard')
        ? lang('Sales.must_enter_numeric_giftcard')
        : lang('Sales.must_enter_numeric');
    } else {
      if ($payment_type === lang('Sales.giftcard')) {
        //In the case of giftcard payment the register input amount_tendered becomes the giftcard number
        $amount_tendered = parse_decimals($this->request->getPost('amount_tendered'));
        $giftcard_num = $amount_tendered;

        $payments = $this->sale_lib->get_payments();
        $payment_type = $payment_type . ':' . $giftcard_num;
        $current_payments_with_giftcard = isset($payments[$payment_type]) ? $payments[$payment_type]['payment_amount'] : 0;
        $cur_giftcard_value = $giftcard->get_giftcard_value($giftcard_num);
        $cur_giftcard_customer = $giftcard->get_giftcard_customer($giftcard_num);
        $customer_id = $this->sale_lib->get_customer();

        if (isset($cur_giftcard_customer) && $cur_giftcard_customer != $customer_id) {
          $data['error'] = lang('Giftcards.cannot_use', [$giftcard_num]);
        } elseif (($cur_giftcard_value - $current_payments_with_giftcard) <= 0 && $this->sale_lib->get_mode() === 'sale') {
          $data['error'] = lang('Giftcards.remaining_balance', [$giftcard_num, $cur_giftcard_value]);
        } else {
          $new_giftcard_value = $giftcard->get_giftcard_value($giftcard_num) - $this->sale_lib->get_amount_due();
          $new_giftcard_value = max($new_giftcard_value, 0);
          $this->sale_lib->set_giftcard_remainder($new_giftcard_value);
          $new_giftcard_value = str_replace('$', '\$', to_currency($new_giftcard_value));
          $data['warning'] = lang('Giftcards.remaining_balance', [$giftcard_num, $new_giftcard_value]);
          $amount_tendered = min($this->sale_lib->get_amount_due(), $giftcard->get_giftcard_value($giftcard_num));

          // Delete existing payment of same type before adding new one
          $this->sale_lib->delete_payment($payment_type);
          $this->sale_lib->add_payment($payment_type, $amount_tendered);
        }
      } elseif ($payment_type === lang('Sales.rewards')) {
        $customer_id = $this->sale_lib->get_customer();
        $package_id = $this->customer->get_info($customer_id)->package_id;
        if (!empty($package_id)) {
          $package_name = $this->customer_rewards->get_name($package_id);    //TODO: this variable is never used.
          $points = $this->customer->get_info($customer_id)->points;
          $points = ($points == null ? 0 : $points);

          $payments = $this->sale_lib->get_payments();
          $current_payments_with_rewards = isset($payments[$payment_type]) ? $payments[$payment_type]['payment_amount'] : 0;
          $cur_rewards_value = $points;

          if (($cur_rewards_value - $current_payments_with_rewards) <= 0) {
            $data['error'] = lang('Sales.rewards_remaining_balance') . to_currency($cur_rewards_value);
          } else {
            $new_reward_value = $points - $this->sale_lib->get_amount_due();
            $new_reward_value = max($new_reward_value, 0);
            $this->sale_lib->set_rewards_remainder($new_reward_value);
            $new_reward_value = str_replace('$', '\$', to_currency($new_reward_value));
            $data['warning'] = lang('Sales.rewards_remaining_balance') . $new_reward_value;
            $amount_tendered = min($this->sale_lib->get_amount_due(), $points);

            // Delete existing payment of same type before adding new one
            $this->sale_lib->delete_payment($payment_type);
            $this->sale_lib->add_payment($payment_type, $amount_tendered);
          }
        }
      } elseif ($payment_type === lang('Sales.cash')) {
        $amount_due = $this->sale_lib->get_total();
        $sales_total = $this->sale_lib->get_total(false);
        $amount_tendered = parse_decimals($this->request->getPost('amount_tendered'));
        
        // Delete existing cash payment before adding new one
        $this->sale_lib->delete_payment($payment_type);
        $this->sale_lib->add_payment($payment_type, $amount_tendered);
        
        $cash_adjustment_amount = $amount_due - $sales_total;
        if ($cash_adjustment_amount <> 0) {
          $this->session->set('cash_mode', CASH_MODE_TRUE);
          // Also delete existing cash adjustment before adding new one
          $this->sale_lib->delete_payment(lang('Sales.cash_adjustment'));
          $this->sale_lib->add_payment(lang('Sales.cash_adjustment'), $cash_adjustment_amount, CASH_ADJUSTMENT_TRUE);
        }
      } else {
        $amount_tendered = parse_decimals($this->request->getPost('amount_tendered'));
        
        // Delete existing payment of same type before adding new one
        $this->sale_lib->delete_payment($payment_type);
        $this->sale_lib->add_payment($payment_type, $amount_tendered);
      }
    }

    $this->_reload($data);
  }

  /**
   * Multiple Payments. Used in app/Views/sales/register.php
   *
   * @param string $payment_id
   * @return void
   * @noinspection PhpUnused
   */
  public function getDeletePayment(string $payment_id): void
  {
    $this->sale_lib->delete_payment($payment_id);

    $this->_reload();    //TODO: Hungarian notation
  }

  /**
   * Add an item to the sale. Used in app/Views/sales/register.php
   *
   * @return void
   * @noinspection PhpUnused
   */
  public function postAdd(): void
  {
    $data = [];
    
    // Check if we're adding to a specific sale_id (from React component)
    $request_sale_id = $this->request->getPost('sale_id', FILTER_SANITIZE_NUMBER_INT);
    $use_database = !empty($request_sale_id);

    $discount = $this->config['default_sales_discount'];
    $discount_type = $this->config['default_sales_discount_type'];

    // Get customer ID - either from request or from session
    if($use_database) {
      $customer_id = $this->request->getPost('customer_id', FILTER_SANITIZE_NUMBER_INT) ?? -1;
    } else {
      $customer_id = $this->sale_lib->get_customer();
    }
    
    if ($customer_id != NEW_ENTRY && $customer_id != -1) {
      // load the customer discount if any
      $customer_discount = $this->customer->get_info($customer_id)->discount;
      $customer_discount_type = $this->customer->get_info($customer_id)->discount_type;
      if ($customer_discount != '') {
        $discount = $customer_discount;
        $discount_type = $customer_discount_type;
      }
    }

    $item_id_or_number_or_item_kit_or_receipt = $this->request->getPost('item', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    // Parse barcode may set $quantity and $price by reference when a
    // composite barcode (with qty/price) is scanned. We allow explicit
    // POST values to override those parsed defaults so the frontend can
    // send exact quantity/price/unit/discount values.
    $this->token_lib->parse_barcode($quantity, $price, $item_id_or_number_or_item_kit_or_receipt);

    // If the frontend posted explicit values, use them instead of parsed ones.
    $posted_quantity = $this->request->getPost('quantity');
    if ($posted_quantity !== null && $posted_quantity !== '') {
      $quantity = parse_decimals($posted_quantity);
    }

    $posted_price = $this->request->getPost('price');
    if ($posted_price !== null && $posted_price !== '') {
      $price = parse_decimals($posted_price);
    }

    // Unit is optional; if provided, pass it through. Default handling
    // elsewhere will apply if unit is not used.
    $unit = $this->request->getPost('unit', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? null;

    // Discount can be passed as absolute (currency) or percentage depending
    // on frontend toggle. Accept numeric discount value when provided.
    $posted_discount = $this->request->getPost('discount');
    if ($posted_discount !== null && $posted_discount !== '') {
      $discount = parse_decimals($posted_discount);
    }

    // discount_toggle indicates whether discount is currency (1) or percentage (0)
    // We'll capture it if provided so downstream logic can act accordingly.
    $discount_toggle_post = $this->request->getPost('discount_toggle');
    if ($discount_toggle_post !== null && $discount_toggle_post !== '') {
      // normalize to boolean/int as used elsewhere
      $discount_toggle = ($discount_toggle_post == '1' || strtolower($discount_toggle_post) === 'true') ? 1 : 0;
    }
    $mode = $this->sale_lib->get_mode();
    $quantity = ($mode == 'return') ? -$quantity : $quantity;

    // If the frontend posted a total price (not a unit price), convert it
    // to a unit price so downstream logic that multiplies unit * qty
    // doesn't double-multiply. The frontend may also send single_unit_quantity
    // and pack_name so we can correctly convert prices when the selected
    // unit differs from the product's pack unit.
    $posted_single_unit_quantity = $this->request->getPost('single_unit_quantity');
    $posted_pack_name = $this->request->getPost('pack_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    $unit_price = $price;
    if ($posted_price !== null && $posted_price !== '') {
      $abs_qty = $quantity != 0 ? abs($quantity) : 1;
      $unit_price = $price / ($abs_qty ?: 1);

      // If the frontend told us how many single-units are in the product's
      // pack and the selected unit matches the pack_name, then the posted
      // price was likely for the pack; convert to a per-single-unit price.
      if ($posted_single_unit_quantity !== null && $posted_single_unit_quantity !== '' && is_numeric($posted_single_unit_quantity) && (float)$posted_single_unit_quantity > 0) {
        if ($unit && $posted_pack_name && $unit == $posted_pack_name) {
          $unit_price = $unit_price / (float)$posted_single_unit_quantity;
        }
      }
    }
    $item_location = $this->sale_lib->get_sale_location();
    
    // Get the item location from request if in database mode
    if($use_database) {
      $posted_location = $this->request->getPost('location', FILTER_SANITIZE_NUMBER_INT);
      if($posted_location) {
        $item_location = $posted_location;
      }
    }
    
    // Get mode - for database mode, just use standard (no returns)
    $mode = $use_database ? 'sale' : $this->sale_lib->get_mode();

    if ($mode == 'return' && $this->sale->is_valid_receipt($item_id_or_number_or_item_kit_or_receipt)) {
      $this->sale_lib->return_entire_sale($item_id_or_number_or_item_kit_or_receipt);
    } elseif ($use_database && !empty($item_id_or_number_or_item_kit_or_receipt)) {
      // Add item directly to database for the specific sale
      // Get the item ID first (handle by ID, barcode, or SKU)
      $item = $this->item->get_info_by_id_or_number($item_id_or_number_or_item_kit_or_receipt);
      
      if(!$item) {
        $data['error'] = lang('Sales.unable_to_add_item');
      } else {
        // Get the next line number for this sale
        $last_line = $this->db->table('sales_items')
          ->selectMax('line')
          ->where('sale_id', $request_sale_id)
          ->get()
          ->getRow();
        $line = ($last_line && $last_line->line) ? (int)$last_line->line + 1 : 1;
        
        // Insert the item into sales_items table
        $insert_data = [
          'sale_id' => $request_sale_id,
          'item_id' => $item->item_id,
          'line' => $line,
          'quantity_purchased' => $quantity,
          'item_unit_price' => $unit_price,
          'discount' => $discount,
          'discount_type' => $discount_type,
          'description' => $item->description
        ];
        
        if($this->db->table('sales_items')->insert($insert_data)) {
          $data['success'] = lang('Sales.item_added');
          // Return success in JSON format for React component
          echo json_encode([
            'success' => true,
            'message' => lang('Sales.item_added'),
            'line' => $line
          ]);
          return;
        } else {
          $data['error'] = lang('Sales.unable_to_add_item');
        }
      }
    } elseif ($this->item_kit->is_valid_item_kit($item_id_or_number_or_item_kit_or_receipt)) {
      // Add kit item to order if one is assigned
      $pieces = explode(' ', $item_id_or_number_or_item_kit_or_receipt);

      $item_kit_id = (count($pieces) > 1) ? $pieces[1] : $item_id_or_number_or_item_kit_or_receipt;
      $item_kit_info = $this->item_kit->get_info($item_kit_id);
      $kit_item_id = $item_kit_info->kit_item_id;
      $kit_price_option = $item_kit_info->price_option;
      $kit_print_option = $item_kit_info->print_option; // 0-all, 1-priced, 2-kit-only

      if ($discount_type == $item_kit_info->kit_discount_type) {
        if ($item_kit_info->kit_discount > $discount) {
          $discount = $item_kit_info->kit_discount;
        }
      } else {
        $discount = $item_kit_info->kit_discount;
        $discount_type = $item_kit_info->kit_discount_type;
      }

      $print_option = PRINT_ALL; // Always include in list of items on invoice //TODO: This variable is never used in the code

      if (!empty($kit_item_id)) {
        if (!$this->sale_lib->add_item($kit_item_id, $item_location, $quantity, $discount, $discount_type, PRICE_MODE_KIT, $kit_price_option, $kit_print_option, $unit_price)) {
          $data['error'] = lang('Sales.unable_to_add_item');
        } else {
          $data['warning'] = $this->sale_lib->out_of_stock($item_kit_id, $item_location);
        }
      }

      // Add item kit items to order
      $stock_warning = null;
      if (!$this->sale_lib->add_item_kit($item_id_or_number_or_item_kit_or_receipt, $item_location, $discount, $discount_type, $kit_price_option, $kit_print_option, $stock_warning)) {
        $data['error'] = lang('Sales.unable_to_add_item');
      } elseif ($stock_warning != null) {
        $data['warning'] = $stock_warning;
      }
    } else {
      if ($item_id_or_number_or_item_kit_or_receipt == '' || !$this->sale_lib->add_item($item_id_or_number_or_item_kit_or_receipt, $item_location, $quantity, $discount, $discount_type, PRICE_MODE_STANDARD, null, null, $unit_price)) {
        $data['error'] = lang('Sales.unable_to_add_item');
      } else {
        $data['warning'] = $this->sale_lib->out_of_stock($item_id_or_number_or_item_kit_or_receipt, $item_location);
      }
    }

    // Return JSON for database mode, otherwise use session-based reload
    if($use_database) {
      if(isset($data['error'])) {
        echo json_encode([
          'success' => false,
          'message' => $data['error']
        ]);
      } else {
        echo json_encode([
          'success' => false,
          'message' => 'Unable to add item'
        ]);
      }
    } else {
      $this->_reload($data);
    }
  }

  /**
   * Edit an item in the sale. Used in app/Views/sales/register.php
   *
   * @param string $line
   * @return void
   * @noinspection PhpUnused
   */
  public function postEditItem(string $line): void
  {
    $data = [];

    $rules = [
      'price' => 'trim|required|decimal_locale',
      'quantity' => 'trim|required|decimal_locale',
      'discount' => 'trim|permit_empty|decimal_locale',
    ];

    if ($this->validate($rules)) {
      $description = $this->request->getPost('description', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
      $serialnumber = $this->request->getPost('serialnumber', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
      $price = parse_decimals($this->request->getPost('price'));
      $quantity = parse_decimals($this->request->getPost('quantity'));
      $discount_type = $this->request->getPost('discount_type', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
      $discount = $discount_type
        ? parse_quantity($this->request->getPost('discount'))
        : parse_decimals($this->request->getPost('discount'));

      $item_location = $this->request->getPost('location', FILTER_SANITIZE_NUMBER_INT);
      $discounted_total = $this->request->getPost('discounted_total') != ''
        ? parse_decimals($this->request->getPost('discounted_total') ?? '')
        : null;


      $this->sale_lib->edit_item($line, $description, $serialnumber, $quantity, $discount, $discount_type, $price, $discounted_total);

      $this->sale_lib->empty_payments();

      $data['warning'] = $this->sale_lib->out_of_stock($this->sale_lib->get_item_id($line), $item_location);
    } else {
      $data['error'] = lang('Sales.error_editing_item');
    }

    $this->_reload($data);
  }

  /**
   * Deletes an item specified in the parameter from the shopping cart. Used in app/Views/sales/register.php
   *
   * @param int $item_id
   * @return void
   * @throws ReflectionException
   * @noinspection PhpUnused
   */
  public function getDeleteItem(int $item_id): void
  {
    $this->sale_lib->delete_item($item_id);

    $this->sale_lib->empty_payments();

    $this->_reload();    //TODO: Hungarian notation
  }

  /**
   * Update an item in the sale via AJAX. Used by React frontend at /sales/updateItem
   *
   * @return void
   * @noinspection PhpUnused
   */
  public function postUpdateItem(): void
  {
    $response = ['success' => false, 'message' => lang('Sales.error_editing_item')];

    $rules = [
      'line_id' => 'trim|required|numeric',
      'price' => 'trim|required|decimal_locale',
      'quantity' => 'trim|required|decimal_locale',
      'discount' => 'trim|permit_empty|decimal_locale',
      'discount_toggle' => 'trim|permit_empty|in_list[0,1]',
    ];

    if ($this->validate($rules)) {
      $line_id = (int)$this->request->getPost('line_id');
      $price = parse_decimals($this->request->getPost('price'));
      $quantity = parse_decimals($this->request->getPost('quantity'));
      $unit = $this->request->getPost('unit', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: 'pcs';
      $discount_toggle = (bool)$this->request->getPost('discount_toggle');
      $discount_type = $discount_toggle ? '1' : '0';
      $discount = $discount_toggle
        ? parse_quantity($this->request->getPost('discount'))
        : parse_decimals($this->request->getPost('discount'));

      try {
        // Edit the item - description and serialnumber are empty for React frontend
        $this->sale_lib->edit_item($line_id, '', '', $quantity, $discount, $discount_type, $price, null);
        $this->sale_lib->empty_payments();

        $response['success'] = true;
        $response['message'] = lang('Sales.item_updated_successfully');
        $response['cart'] = $this->sale_lib->get_cart();
      } catch (\Exception $e) {
        $response['message'] = $e->getMessage();
      }
    }

    echo json_encode($response);
  }

  /**
   * Remove the current customer from the sale. Used in app/Views/sales/register.php
   *
   * @return void
   * @noinspection PhpUnused
   */
  public function getRemoveCustomer(): void
  {
    $this->sale_lib->clear_giftcard_remainder();
    $this->sale_lib->clear_rewards_remainder();
    $this->sale_lib->delete_payment(lang('Sales.rewards'));
    $this->sale_lib->clear_invoice_number();
    $this->sale_lib->clear_quote_number();
    $this->sale_lib->remove_customer();

    $this->_reload();    //TODO: Hungarian notation
  }

  /**
   * Complete and finalize a sale. Used in app/Views/sales/register.php
   *
   * @return void
   * @throws ReflectionException
   * @noinspection PhpUnused
   */
  public function postComplete(): void    //TODO: this function is huge.  Probably should be refactored.
  {
    // Check if sale_id is provided as a parameter for direct receipt retrieval
    $request_sale_id = $this->request->getPost('sale_id') ?? $this->request->getGet('sale_id');
    
    if($request_sale_id) {
      // Load specific sale from database
      $sale_id = $request_sale_id;
      $sale_record = $this->sale->get_info($sale_id)->getRow();
      
      if(!$sale_record) {
        echo json_encode([
          'success' => false,
          'message' => 'Sale not found'
        ]);
        return;
      }
      
      // Load all sale data from database instead of session
      $customer_id = $sale_record->customer_id;
      $vehicle_id = $sale_record->vehicle_id;
      $vehicle_kilometer = $sale_record->vehicle_kilometer;
      $vehicle_avg_oil_km = $sale_record->vehicle_avg_oil_km;
      $vehicle_avg_km_day = $sale_record->vehicle_avg_km_day;
      $next_visit = '';
      
      // Get cart items from database
      $builder = $this->db->table('sales_items');
      $builder->select('sales_items.*, items.name');
      $builder->join('items', 'sales_items.item_id = items.item_id', 'LEFT');
      $builder->where('sales_items.sale_id', $sale_id);
      $cart = $builder->get()->getResultArray();
      
      // Get payments from database
      $builder = $this->db->table('sales_payments');
      $builder->where('sale_id', $sale_id);
      $payments_records = $builder->get()->getResultArray();
      $payments = [];
      foreach($payments_records as $payment) {
        $payments[$payment['payment_type']] = [
          'payment_type' => $payment['payment_type'],
          'payment_amount' => $payment['payment_amount']
        ];
      }
    } else {
      // Use session-based data as before
      $sale_id = $this->sale_lib->get_sale_id();
      $vehicle_id = $this->sale_lib->get_vehicle();
      $vehicle_kilometer = $this->sale_lib->get_vehicle_kilometer();
      $vehicle_avg_oil_km = $this->sale_lib->get_vehicle_avg_oil_km();
      $vehicle_avg_km_day = $this->sale_lib->get_vehicle_avg_km_day();
      $next_visit = $this->session->get('vehicle_next_visit');
      $cart = $this->sale_lib->get_cart();
      $customer_id = $this->sale_lib->get_customer();
      $payments = $this->sale_lib->get_payments();
    }
    
    $data = [];
    $data['dinner_table'] = $this->sale_lib->get_dinner_table();

    $data['cart'] = $cart;
    $data['include_hsn'] = (bool)$this->config['include_hsn'];
    $__time = time();
    $data['transaction_time'] = to_datetime($__time);
    $data['transaction_date'] = to_date($__time);
    $data['show_stock_locations'] = $this->stock_location->show_locations('sales');
    $data['comments'] = $this->sale_lib->get_comment();
    $employee_id = $this->employee->get_logged_in_employee_info()->person_id;
    $employee_info = $this->employee->get_info($employee_id);
    $data['employee'] = $employee_info->first_name . ' ' . mb_substr($employee_info->last_name, 0, 1);

    $data['company_info'] = implode("\n", [$this->config['address'], $this->config['phone']]);

    if ($this->config['account_number']) {
      $data['company_info'] .= "\n" . lang('Sales.account_number') . ": " . $this->config['account_number'];
    }

    if ($this->config['tax_id'] != '') {
      $data['company_info'] .= "\n" . lang('Sales.tax_id') . ": " . $this->config['tax_id'];
    }

    $data['invoice_number_enabled'] = $this->sale_lib->is_invoice_mode();
    $data['cur_giftcard_value'] = $this->sale_lib->get_giftcard_remainder();
    $data['cur_rewards_value'] = $this->sale_lib->get_rewards_remainder();
    $data['print_after_sale'] = $this->session->get('sales_print_after_sale');
    $data['price_work_orders'] = $this->sale_lib->is_price_work_orders();
    $data['email_receipt'] = $this->sale_lib->is_email_receipt();
    $customer_id = $this->sale_lib->get_customer();
    $invoice_number = $this->sale_lib->get_invoice_number();
    $data["invoice_number"] = $invoice_number;
    $work_order_number = $this->sale_lib->get_work_order_number();
    $data["work_order_number"] = $work_order_number;
    $quote_number = $this->sale_lib->get_quote_number();
    $data["quote_number"] = $quote_number;
    $customer_info = $this->_load_customer_data($customer_id, $data);

    if ($customer_info != null) {
      $data["customer_comments"] = $customer_info->comments;
      $data['tax_id'] = $customer_info->tax_id;
    }
    $tax_details = $this->tax_lib->get_taxes($data['cart']);    //TODO: Duplicated code
    $data['taxes'] = $tax_details[0];
    $data['discount'] = $this->sale_lib->get_discount();
    $data['payments'] = $payments;

    // Returns 'subtotal', 'total', 'cash_total', 'payment_total', 'amount_due', 'cash_amount_due', 'payments_cover_total'
    $totals = $this->sale_lib->get_totals($tax_details[0]);
    $data['subtotal'] = $totals['subtotal'];
    $data['total'] = $totals['total'];
    $data['payments_total'] = $totals['payment_total'];
    $data['payments_cover_total'] = $totals['payments_cover_total'];
    $data['cash_rounding'] = $this->session->get('cash_rounding');
    $data['cash_mode'] = $this->session->get('cash_mode');    //TODO: Duplicated code
    $data['prediscount_subtotal'] = $totals['prediscount_subtotal'];
    $data['cash_total'] = $totals['cash_total'];
    $data['non_cash_total'] = $totals['total'];
    $data['cash_amount_due'] = $totals['cash_amount_due'];
    $data['non_cash_amount_due'] = $totals['amount_due'];

    if ($data['cash_mode'])    //TODO: Convert this to ternary notation
    {
      $data['amount_due'] = $totals['cash_amount_due'];
    } else {
      $data['amount_due'] = $totals['amount_due'];
    }

    $data['amount_change'] = $data['amount_due'] * -1;

    if ($data['amount_change'] > 0) {
      // Save cash refund to the cash payment transaction if found, if not then add as new Cash transaction

      if (array_key_exists(lang('Sales.cash'), $data['payments'])) {
        if (!isset($data['payments'][lang('Sales.cash')]['cash_refund'])) {
          $data['payments'][lang('Sales.cash')]['cash_refund'] = 0;
        }
        $data['payments'][lang('Sales.cash')]['cash_refund'] = $data['amount_change'];
      } else {
        $payment = [
          lang('Sales.cash') => [
            'payment_type' => lang('Sales.cash'),
            'payment_amount' => 0,
            'cash_refund' => $data['amount_change']
          ]
        ];

        $data['payments'] += $payment;
      }
    }

    $data['print_price_info'] = true;

    if ($this->sale_lib->is_invoice_mode()) {
      $invoice_format = $this->config['sales_invoice_format'];

      // generate final invoice number (if using the invoice in sales by receipt mode then the invoice number can be manually entered or altered in some way
      if (!empty($invoice_format) && $invoice_number == null) {
        // The user can retain the default encoded format or can manually override it.  It still passes through the rendering step.
        $invoice_number = $this->token_lib->render($invoice_format);
      }


      if ($sale_id == NEW_ENTRY && $this->sale->check_invoice_number_exists($invoice_number)) {
        $data['error'] = lang('Sales.invoice_number_duplicate', [$invoice_number]);
        $this->_reload($data);
      } else {
        $data['invoice_number'] = $invoice_number;
        $data['sale_status'] = COMPLETED;
        $sale_type = SALE_TYPE_INVOICE;

        // The PHP file name is the same as the invoice_type key
        $invoice_view = $this->config['invoice_type'];

        // Save the data to the sales table
        $data['sale_id_num'] = $this->sale->save_value(
          $sale_id,
          $data['sale_status'],
          $data['cart'],
          $customer_id,
          $employee_id,
          $data['comments'],
          $invoice_number,
          $work_order_number,
          $quote_number,
          $sale_type,
          $data['payments'],
          $data['dinner_table'],
          $tax_details,
          $vehicle_id,
          $vehicle_kilometer,    // Add this
          $vehicle_avg_oil_km,   // Add this
          $vehicle_avg_km_day    // Add this
        );
        $data['sale_id'] = 'POS ' . $data['sale_id_num'];

        // Resort and filter cart lines for printing
        $data['cart'] = $this->sale_lib->sort_and_filter_cart($data['cart']);

        if ($data['sale_id_num'] == NEW_ENTRY) {
          $data['error_message'] = lang('Sales.transaction_failed');
        } else {
          $data['barcode'] = $this->barcode_lib->generate_receipt_barcode($data['sale_id']);
          echo view('sales/' . $invoice_view, $data);
          $this->sale_lib->clear_all();
        }
      }
    } elseif ($this->sale_lib->is_work_order_mode()) {

      if (!($data['price_work_orders'] == 1)) {
        $data['print_price_info'] = false;
      }

      $data['sales_work_order'] = lang('Sales.work_order');
      $data['work_order_number_label'] = lang('Sales.work_order_number');

      if ($work_order_number == null) {
        // generate work order number
        $work_order_format = $this->config['work_order_format'];
        $work_order_number = $this->token_lib->render($work_order_format);
      }

      if ($sale_id == NEW_ENTRY && $this->sale->check_work_order_number_exists($work_order_number)) {
        $data['error'] = lang('Sales.work_order_number_duplicate');
        $this->_reload($data);
      } else {
        $data['work_order_number'] = $work_order_number;
        $data['sale_status'] = SUSPENDED;
        $sale_type = SALE_TYPE_WORK_ORDER;

        $data['sale_id_num'] = $this->sale->save_value(
          $sale_id,
          $data['sale_status'],
          $data['cart'],
          $customer_id,
          $employee_id,
          $data['comments'],
          $invoice_number,
          $work_order_number,
          $quote_number,
          $sale_type,
          $data['payments'],
          $data['dinner_table'],
          $tax_details,
          $vehicle_id,           // Add this
          $vehicle_kilometer,    // Add this
          $vehicle_avg_oil_km,   // Add this
          $vehicle_avg_km_day    // Add this
        );
        $this->sale_lib->set_suspended_id($data['sale_id_num']);

        $data['cart'] = $this->sale_lib->sort_and_filter_cart($data['cart']);

        $data['barcode'] = null;

        echo view('sales/work_order', $data);
        $this->sale_lib->clear_mode();
        $this->sale_lib->clear_all();
      }
    } elseif ($this->sale_lib->is_quote_mode()) {
      $data['sales_quote'] = lang('Sales.quote');
      $data['quote_number_label'] = lang('Sales.quote_number');

      if ($quote_number == null) {
        // generate quote number
        $quote_format = $this->config['sales_quote_format'];
        $quote_number = $this->token_lib->render($quote_format);
      }

      if ($sale_id == NEW_ENTRY && $this->sale->check_quote_number_exists($quote_number)) {
        $data['error'] = lang('Sales.quote_number_duplicate');
        $this->_reload($data);
      } else {
        $data['quote_number'] = $quote_number;
        $data['sale_status'] = SUSPENDED;
        $sale_type = SALE_TYPE_QUOTE;

        $data['sale_id_num'] = $this->sale->save_value(
          $sale_id,
          $data['sale_status'],
          $data['cart'],
          $customer_id,
          $employee_id,
          $data['comments'],
          $invoice_number,
          $work_order_number,
          $quote_number,
          $sale_type,
          $data['payments'],
          $data['dinner_table'],
          $tax_details,
          $vehicle_id,           // Add this
          $vehicle_kilometer,    // Add this
          $vehicle_avg_oil_km,   // Add this
          $vehicle_avg_km_day    // Add this
        );
        $this->sale_lib->set_suspended_id($data['sale_id_num']);

        $data['cart'] = $this->sale_lib->sort_and_filter_cart($data['cart']);
        $data['barcode'] = null;

        echo view('sales/quote', $data);
        $this->sale_lib->clear_mode();
        $this->sale_lib->clear_all();
      }
    } else {
      // Save the data to the sales table
      $data['sale_status'] = COMPLETED;
      if ($this->sale_lib->is_return_mode()) {
        $sale_type = SALE_TYPE_RETURN;
      } else {
        $sale_type = SALE_TYPE_POS;
      }

      $data['sale_id_num'] = $this->sale->save_value(
        $sale_id,
        $data['sale_status'],
        $data['cart'],
        $customer_id,
        $employee_id,
        $data['comments'],
        $invoice_number,
        $work_order_number,
        $quote_number,
        $sale_type,
        $data['payments'],
        $data['dinner_table'],
        $tax_details,
        $vehicle_id,           // Add this
        $vehicle_kilometer,    // Add this
        $vehicle_avg_oil_km,   // Add this
        $vehicle_avg_km_day    // Add this
      );

      $data['sale_id'] = 'POS ' . $data['sale_id_num'];

      $data['cart'] = $this->sale_lib->sort_and_filter_cart($data['cart']);

      if ($data['sale_id_num'] == NEW_ENTRY) {
        $data['error_message'] = lang('Sales.transaction_failed');
      } else {
        $data['barcode'] = $this->barcode_lib->generate_receipt_barcode($data['sale_id']);
        $this->sale_lib->clear_all();
        echo view('sales/receipt', $data);
      }
    }
  }

  /**
   * Email PDF invoice to customer. Used in app/Views/sales/form.php, invoice.php, quote.php, tax_invoice.php and work_order.php
   *
   * @param int $sale_id
   * @param string $type
   * @return bool
   * @noinspection PhpUnused
   */
  public function getSendPdf(int $sale_id, string $type = 'invoice'): bool
  {
    $sale_data = $this->_load_sale_data($sale_id);

    $result = false;
    $message = lang('Sales.invoice_no_email');

    if (!empty($sale_data['customer_email'])) {
      $to = $sale_data['customer_email'];
      $number = array_key_exists($type . "_number", $sale_data) ?  $sale_data[$type . "_number"] : "";
      $subject = lang('Sales.' . $type) . ' ' . $number;

      $text = $this->config['invoice_email_message'];
      $tokens = [
        new Token_invoice_sequence($number),
        new Token_invoice_count('POS ' . $sale_data['sale_id']),
        new Token_customer((array)$sale_data)
      ];
      $text = $this->token_lib->render($text, $tokens);
      $sale_data['mimetype'] = mime_content_type(FCPATH . 'uploads/' . $this->config['company_logo']);

      // generate email attachment: invoice in pdf format
      $view = Services::renderer();
      $html = $view->setData($sale_data)->render("sales/$type" . '_email', $sale_data);

      // load pdf helper
      helper(['dompdf', 'file']);
      $filename = sys_get_temp_dir() . '/' . lang('Sales.' . $type) . '-' . str_replace('/', '-', $number) . '.pdf';
      if (file_put_contents($filename, create_pdf($html)) !== false) {
        $result = $this->email_lib->sendEmail($to, $subject, $text, $filename);
      }

      $message = lang($result ? "Sales." . $type . "_sent" : "Sales." . $type . "_unsent") . ' ' . $to;
    }

    echo json_encode(['success' => $result, 'message' => $message, 'id' => $sale_id]);

    $this->sale_lib->clear_all();

    return $result;
  }

  /**
   * Emails sales receipt to customer. Used in app/Views/sales/receipt.php
   *
   * @param int $sale_id
   * @return bool
   * @noinspection PhpUnused
   */
  public function getSendReceipt(int $sale_id): bool
  {
    $sale_data = $this->_load_sale_data($sale_id);

    $result = false;
    $message = lang('Sales.receipt_no_email');

    if (!empty($sale_data['customer_email'])) {
      $sale_data['barcode'] = $this->barcode_lib->generate_receipt_barcode($sale_data['sale_id']);

      $to = $sale_data['customer_email'];
      $subject = lang('Sales.receipt');

      $view = Services::renderer();
      $text = $view->setData($sale_data)->render('sales/receipt_email');

      $result = $this->email_lib->sendEmail($to, $subject, $text);

      $message = lang($result ? 'Sales.receipt_sent' : 'Sales.receipt_unsent') . ' ' . $to;
    }

    echo json_encode(['success' => $result, 'message' => $message, 'id' => $sale_id]);

    $this->sale_lib->clear_all();

    return $result;
  }

  /**
   * @param int $customer_id
   * @param array $data
   * @param bool $stats
   * @return array|stdClass|string|null
   */
  private function _load_customer_data(int $customer_id, array &$data, bool $stats = false): array|string|stdClass|null
  {
      $customer_info = '';

      // Check if we need to auto-set customer based on vehicle
      $vehicle_id = $this->sale_lib->get_vehicle();
      
      // If no customer is selected but vehicle is selected, try to find customer from vehicle's sales history
      if (($customer_id == NEW_ENTRY || $customer_id == -1) && $vehicle_id && $vehicle_id != NEW_ENTRY && $vehicle_id != -1) {
          // Search for the most recent sale with this vehicle that has a customer
          $builder = $this->db->table('sales');
          $builder->select('customer_id');
          $builder->where('vehicle_id', $vehicle_id);
          $builder->where('customer_id IS NOT NULL');
          $builder->where('customer_id !=', NEW_ENTRY);
          $builder->where('customer_id !=', -1);
          $builder->where('sale_status', COMPLETED);
          $builder->orderBy('sale_time', 'DESC');
          $builder->limit(1);
          
          $vehicle_sale = $builder->get()->getRowArray();
          
          if ($vehicle_sale && isset($vehicle_sale['customer_id'])) {
              $found_customer_id = $vehicle_sale['customer_id'];
              
              // Verify the customer still exists
              if ($this->customer->exists($found_customer_id)) {
                  // Auto-set the customer in the sale_lib
                  $this->sale_lib->set_customer($found_customer_id);
                  $customer_id = $found_customer_id;
                  
                  // Apply customer discount if any
                  $customer_discount = $this->customer->get_info($customer_id)->discount;
                  $customer_discount_type = $this->customer->get_info($customer_id)->discount_type;
                  if ($customer_discount != '') {
                      $this->sale_lib->apply_customer_discount($customer_discount, $customer_discount_type);
                  }
              }
          }
      }

      if ($customer_id != NEW_ENTRY && $customer_id != -1) {
          $customer_info = $this->customer->get_info($customer_id);
          $data['customer_id'] = $customer_id;

          if (!empty($customer_info->company_name)) {
              $data['customer'] = $customer_info->company_name;
          } else {
              $data['customer'] = $customer_info->first_name . ' ' . $customer_info->last_name;
          }

          $data['first_name'] = $customer_info->first_name;
          $data['last_name'] = $customer_info->last_name;
          $data['customer_email'] = $customer_info->email;
          $data['customer_address'] = $customer_info->address_1;
          $data['customer_phone'] = $customer_info->phone_number ?? '';

          // Add vehicle information - make sure this persists on refresh
          $vehicle = model(\App\Models\Vehicle::class);
          $selected_vehicle = $this->sale_lib->get_vehicle();
          $customer_vehicle = null;
          
          if ($selected_vehicle && $selected_vehicle > 0) {
              $customer_vehicle = $vehicle->find($selected_vehicle);
          } else {
              $customer_vehicle = $vehicle->where('last_customer_id', $customer_id)->orderBy('updated_at', 'DESC')->first();
          }
          if ($customer_vehicle) {
              $data['customer_vehicle_no'] = $customer_vehicle->vehicle_no ?? '';
              $data['customer_kilometer'] = $customer_vehicle->kilometer ?? '';
              $data['customer_avg_oil_km'] = $customer_vehicle->last_avg_oil_km ?? '';
              $data['customer_avg_km_day'] = $customer_vehicle->last_avg_km_day ?? '';
              $data['customer_next_visit'] = $customer_vehicle->last_next_visit ?? '';
          } else {
              $data['customer_vehicle_no'] = '';
              $data['customer_kilometer'] = '';
              $data['customer_avg_oil_km'] = '';
              $data['customer_avg_km_day'] = '';
              $data['customer_next_visit'] = '';
          }

          if (!empty($customer_info->zip) || !empty($customer_info->city)) {
              $data['customer_location'] = $customer_info->zip . ' ' . $customer_info->city . "\n" . $customer_info->state;
          } else {
              $data['customer_location'] = '';
          }

          $data['customer_account_number'] = $customer_info->account_number;
          $data['customer_discount'] = $customer_info->discount;
          $data['customer_discount_type'] = $customer_info->discount_type;
          $package_id = $this->customer->get_info($customer_id)->package_id;

          if ($package_id != null) {
              $package_name = $this->customer_rewards->get_name($package_id);
              $points = $this->customer->get_info($customer_id)->points;
              $data['customer_rewards']['package_id'] = $package_id;
              $data['customer_rewards']['points'] = empty($points) ? 0 : $points;
              $data['customer_rewards']['package_name'] = $package_name;
          }

          if ($stats) {
              $cust_stats = $this->customer->get_stats($customer_id);
              $data['customer_total'] = empty($cust_stats) ? 0 : $cust_stats->total;
          }

          $data['customer_info'] = implode("\n", [
              $data['customer'],
              $data['customer_address'],
              $data['customer_location']
          ]);

          if ($data['customer_account_number']) {
              $data['customer_info'] .= "\n" . lang('Sales.account_number') . ": " . $data['customer_account_number'];
          }

          if ($customer_info->tax_id != '') {
              $data['customer_info'] .= "\n" . lang('Sales.tax_id') . ": " . $customer_info->tax_id;
          }
          $data['tax_id'] = $customer_info->tax_id;
      }

      return $customer_info;
  }

  /**
   * @param $sale_id
   * @return array
   */
  private function _load_sale_data($sale_id): array    //TODO: Hungarian notation
  {
    $this->sale_lib->clear_all();
    $cash_rounding = $this->sale_lib->reset_cash_rounding();
    $data['cash_rounding'] = $cash_rounding;

    $sale_info = $this->sale->get_info($sale_id)->getRowArray();
    $this->sale_lib->copy_entire_sale($sale_id);
    $data = [];
    $data['cart'] = $this->sale_lib->get_cart();
    $data['payments'] = $this->sale_lib->get_payments();
    $data['selected_payment_type'] = $this->sale_lib->get_payment_type();

    $tax_details = $this->tax_lib->get_taxes($data['cart'], $sale_id);
    $data['taxes'] = $this->sale->get_sales_taxes($sale_id);
    $data['discount'] = $this->sale_lib->get_discount();
    $data['transaction_time'] = to_datetime(strtotime($sale_info['sale_time']));
    $data['transaction_date'] = to_date(strtotime($sale_info['sale_time']));
    $data['show_stock_locations'] = $this->stock_location->show_locations('sales');

    $data['include_hsn'] = (bool)$this->config['include_hsn'];

    // Returns 'subtotal', 'total', 'cash_total', 'payment_total', 'amount_due', 'cash_amount_due', 'payments_cover_total'
    $totals = $this->sale_lib->get_totals($tax_details[0]);
    $this->session->set('cash_adjustment_amount', $totals['cash_adjustment_amount']);
    $data['subtotal'] = $totals['subtotal'];
    $data['payments_total'] = $totals['payment_total'];
    $data['payments_cover_total'] = $totals['payments_cover_total'];
    $data['cash_mode'] = $this->session->get('cash_mode');    //TODO: Duplicated code.
    $data['prediscount_subtotal'] = $totals['prediscount_subtotal'];
    $data['cash_total'] = $totals['cash_total'];
    $data['non_cash_total'] = $totals['total'];
    $data['cash_amount_due'] = $totals['cash_amount_due'];
    $data['non_cash_amount_due'] = $totals['amount_due'];

    if ($data['cash_mode'] && ($data['selected_payment_type'] === lang('Sales.cash') || $data['payments_total'] > 0)) {
      $data['total'] = $totals['cash_total'];
      $data['amount_due'] = $totals['cash_amount_due'];
    } else {
      $data['total'] = $totals['total'];
      $data['amount_due'] = $totals['amount_due'];
    }

    $data['amount_change'] = $data['amount_due'] * -1;

    $employee_info = $this->employee->get_info($this->sale_lib->get_employee());
    $data['employee'] = $employee_info->first_name . ' ' . mb_substr($employee_info->last_name, 0, 1);
    $this->_load_customer_data($this->sale_lib->get_customer(), $data);

    $data['sale_id_num'] = $sale_id;
    $data['sale_id'] = 'POS ' . $sale_id;
    $data['comments'] = $sale_info['comment'];
    $data['invoice_number'] = $sale_info['invoice_number'];
    $data['quote_number'] = $sale_info['quote_number'];
    $data['sale_status'] = $sale_info['sale_status'];

    $data['company_info'] = implode("\n", [$this->config['address'], $this->config['phone']]);    //TODO: Duplicated code.

    if ($this->config['account_number']) {
      $data['company_info'] .= "\n" . lang('Sales.account_number') . ": " . $this->config['account_number'];
    }
    if ($this->config['tax_id'] != '') {
      $data['company_info'] .= "\n" . lang('Sales.tax_id') . ": " . $this->config['tax_id'];
    }

    $data['barcode'] = $this->barcode_lib->generate_receipt_barcode($data['sale_id']);
    $data['print_after_sale'] = false;
    $data['price_work_orders'] = false;

    if ($this->sale_lib->get_mode() == 'sale_invoice')    //TODO: Duplicated code.
    {
      $data['mode_label'] = lang('Sales.invoice');
      $data['customer_required'] = lang('Sales.customer_required');
    } elseif ($this->sale_lib->get_mode() == 'sale_quote') {
      $data['mode_label'] = lang('Sales.quote');
      $data['customer_required'] = lang('Sales.customer_required');
    } elseif ($this->sale_lib->get_mode() == 'sale_work_order') {
      $data['mode_label'] = lang('Sales.work_order');
      $data['customer_required'] = lang('Sales.customer_required');
    } elseif ($this->sale_lib->get_mode() == 'return') {
      $data['mode_label'] = lang('Sales.return');
      $data['customer_required'] = lang('Sales.customer_optional');
    } else {
      $data['mode_label'] = lang('Sales.receipt');
      $data['customer_required'] = lang('Sales.customer_optional');
    }

    $invoice_type = $this->config['invoice_type'];
    $data['invoice_view'] = $invoice_type;
    return $data;
  }

  /**
   * @param array $data
   * @return void
   */
  private function _reload(array $data = []): void    //TODO: Hungarian notation
  {
    $sale_id = $this->session->get('sale_id');    //TODO: This variable is never used

    if ($sale_id == '') {
      $sale_id = NEW_ENTRY;
      $this->session->set('sale_id', NEW_ENTRY);
    }
    $cash_rounding = $this->sale_lib->reset_cash_rounding();

    // cash_rounding indicates only that the site is configured for cash rounding
    $data['cash_rounding'] = $cash_rounding;

    $data['cart'] = $this->sale_lib->get_cart();
    $customer_info = $this->_load_customer_data($this->sale_lib->get_customer(), $data, true);

    $data['modes'] = $this->sale_lib->get_register_mode_options();
    $data['mode'] = $this->sale_lib->get_mode();
    $data['selected_table'] = $this->sale_lib->get_dinner_table();
    $data['empty_tables'] = $this->sale_lib->get_empty_tables($data['selected_table']);
    $data['stock_locations'] = $this->stock_location->get_allowed_locations('sales');
    $data['stock_location'] = $this->sale_lib->get_sale_location();
    $data['tax_exclusive_subtotal'] = $this->sale_lib->get_subtotal(true, true);
    $tax_details = $this->tax_lib->get_taxes($data['cart']);    //TODO: Duplicated code.
    $data['taxes'] = $tax_details[0];
    $data['discount'] = $this->sale_lib->get_discount();
    $data['payments'] = $this->sale_lib->get_payments();

    // Returns 'subtotal', 'total', 'cash_total', 'payment_total', 'amount_due', 'cash_amount_due', 'payments_cover_total'
    $totals = $this->sale_lib->get_totals($tax_details[0]);

    $data['item_count'] = $totals['item_count'];
    $data['total_units'] = $totals['total_units'];
    $data['subtotal'] = $totals['subtotal'];
    $data['total'] = $totals['total'];
    $data['payments_total'] = $totals['payment_total'];
    $data['payments_cover_total'] = $totals['payments_cover_total'];

    // cash_mode indicates whether this sale is going to be processed using cash_rounding
    $cash_mode = $this->session->get('cash_mode');
    $data['cash_mode'] = $cash_mode;
    $data['prediscount_subtotal'] = $totals['prediscount_subtotal'];    //TODO: Duplicated code.
    $data['cash_total'] = $totals['cash_total'];
    $data['non_cash_total'] = $totals['total'];
    $data['cash_amount_due'] = $totals['cash_amount_due'];
    $data['non_cash_amount_due'] = $totals['amount_due'];

    $data['selected_payment_type'] = $this->sale_lib->get_payment_type();

    if ($data['cash_mode'] && ($data['selected_payment_type'] == lang('Sales.cash') || $data['payments_total'] > 0)) {
      $data['total'] = $totals['cash_total'];
      $data['amount_due'] = $totals['cash_amount_due'];
    } else {
      $data['total'] = $totals['total'];
      $data['amount_due'] = $totals['amount_due'];
    }

    $data['amount_change'] = $data['amount_due'] * -1;

    $data['comment'] = $this->sale_lib->get_comment();
    $data['email_receipt'] = $this->sale_lib->is_email_receipt();

    if ($customer_info && $this->config['customer_reward_enable']) {
      $data['payment_options'] = $this->sale->get_payment_options(true, true);
    } else {
      $data['payment_options'] = $this->sale->get_payment_options();
    }

    $data['items_module_allowed'] = $this->employee->has_grant('items', $this->employee->get_logged_in_employee_info()->person_id);
    $data['change_price'] = $this->employee->has_grant('sales_change_price', $this->employee->get_logged_in_employee_info()->person_id);

    $temp_invoice_number = $this->sale_lib->get_invoice_number();
    $invoice_format = $this->config['sales_invoice_format'];

    if ($temp_invoice_number == null || $temp_invoice_number == '') {
      $temp_invoice_number = $this->token_lib->render($invoice_format, [], false);
    }

    $data['invoice_number'] = $temp_invoice_number;

    $data['print_after_sale'] = $this->sale_lib->is_print_after_sale();
    $data['price_work_orders'] = $this->sale_lib->is_price_work_orders();

    $data['pos_mode'] = $data['mode'] == 'sale' || $data['mode'] == 'return';

    $data['quote_number'] = $this->sale_lib->get_quote_number();
    $data['work_order_number'] = $this->sale_lib->get_work_order_number();

    //TODO: the if/else set below should be converted to a switch
    if ($this->sale_lib->get_mode() == 'sale_invoice')    //TODO: Duplicated code.
    {
      $data['mode_label'] = lang('Sales.invoice');
      $data['customer_required'] = lang('Sales.customer_required');
    } elseif ($this->sale_lib->get_mode() == 'sale_quote') {
      $data['mode_label'] = lang('Sales.quote');
      $data['customer_required'] = lang('Sales.customer_required');
    } elseif ($this->sale_lib->get_mode() == 'sale_work_order') {
      $data['mode_label'] = lang('Sales.work_order');
      $data['customer_required'] = lang('Sales.customer_required');
    } elseif ($this->sale_lib->get_mode() == 'return') {
      $data['mode_label'] = lang('Sales.return');
      $data['customer_required'] = lang('Sales.customer_optional');
    } else {
      $data['mode_label'] = lang('Sales.receipt');
      $data['customer_required'] = lang('Sales.customer_optional');
    }

    // Expose the currently-selected vehicle id and vehicle_no to the view so
    // the React frontend can pre-load the vehicle on refresh.
    $selected_vehicle_id = $this->sale_lib->get_vehicle();
    $data['selected_vehicle_id'] = $selected_vehicle_id;
    $data['selected_vehicle_no'] = '';
    if ($selected_vehicle_id && $selected_vehicle_id != NEW_ENTRY && $selected_vehicle_id != -1) {
      $vehicleModel = model(Vehicle::class);
      $vehicle = $vehicleModel->find($selected_vehicle_id);
      if ($vehicle) {
        $data['selected_vehicle_no'] = $vehicle->vehicle_no ?? '';
      }
    }

    echo view("sales/register", $data);
  }

  /**
   * Load the sales receipt for a sale. Used in app/Views/sales/form.php
   *
   * @param int $sale_id
   * @return void
   * @noinspection PhpUnused
   */
  public function getReceipt(int $sale_id): void
  {
    $data = $this->_load_sale_data($sale_id);
    echo view('sales/receipt', $data);
    $this->sale_lib->clear_all();
  }

  /**
   * @param int $sale_id
   * @return void
   */
  public function getInvoice(int $sale_id): void
  {
    $data = $this->_load_sale_data($sale_id);

    echo view('sales/' . $data['invoice_view'], $data);
    $this->sale_lib->clear_all();
  }

  /**
   * @param int $sale_id
   * @return void
   */
  public function getEdit(int $sale_id): void
  {
    $data = [];

    $sale_info = $this->sale->get_info($sale_id)->getRowArray();
    $data['selected_customer_id'] = $sale_info['customer_id'];
    $data['selected_customer_name'] = $sale_info['customer_name'];
    $employee_info = $this->employee->get_info($sale_info['employee_id']);
    $data['selected_employee_id'] = $sale_info['employee_id'];
    $data['selected_employee_name'] = $employee_info->first_name . ' ' . $employee_info->last_name;
    $data['sale_info'] = $sale_info;
    $balance_due = round($sale_info['amount_due'] - $sale_info['amount_tendered'] + ($sale_info['cash_refund'] ?? 0), totals_decimals(), PHP_ROUND_HALF_UP);

    if (!$this->sale_lib->reset_cash_rounding() && $balance_due < 0) {
      $balance_due = 0;
    }

    $data['payments'] = [];

    foreach ($this->sale->get_sale_payments($sale_id)->getResult() as $payment) {
      foreach (get_object_vars($payment) as $property => $value) {
        $payment->$property = $value;
      }
      $data['payments'][] = $payment;
    }

    $data['payment_type_new'] = PAYMENT_TYPE_UNASSIGNED;
    $data['payment_amount_new'] = $balance_due;

    $data['balance_due'] = $balance_due != 0;

    // don't allow gift card to be a payment option in a sale transaction edit because it's a complex change
    $payment_options = $this->sale->get_payment_options(false);

    if ($this->sale_lib->reset_cash_rounding()) {
      $payment_options[lang('Sales.cash_adjustment')] = lang('Sales.cash_adjustment');
    }

    $data['payment_options'] = $payment_options;

    // Set up a slightly modified list of payment types for new payment entry
    $payment_options["--"] = lang('Common.none_selected_text');

    $data['new_payment_options'] = $payment_options;

    // echo view('sales/form', $data);
    echo view('sales/form', $data);

  }

  /**
   * @throws ReflectionException
   */
  public function postDelete(int $sale_id = NEW_ENTRY, bool $update_inventory = true): void
  {
    $employee_id = $this->employee->get_logged_in_employee_info()->person_id;
    $has_grant = $this->employee->has_grant('sales_delete', $employee_id);

    if (!$has_grant) {
      echo json_encode(['success' => false, 'message' => lang('Sales.not_authorized')]);
    } else {
      $sale_ids = $sale_id == NEW_ENTRY ? $this->request->getPost('ids', FILTER_SANITIZE_NUMBER_INT) : [$sale_id];

      if ($this->sale->delete_list($sale_ids, $employee_id, $update_inventory)) {
        echo json_encode([
          'success' => true,
          'message' => lang('Sales.successfully_deleted') . ' ' . count($sale_ids) . ' ' . lang('Sales.one_or_multiple'),
          'ids' => $sale_ids
        ]);
      } else {
        echo json_encode(['success' => false, 'message' => lang('Sales.unsuccessfully_deleted')]);
      }
    }
  }

  /**
   * @param int $sale_id
   * @param bool $update_inventory
   * @return void
   */
  public function restore(int $sale_id = NEW_ENTRY, bool $update_inventory = true): void
  {
    $employee_id = $this->employee->get_logged_in_employee_info()->person_id;
    $has_grant = $this->employee->has_grant('sales_delete', $employee_id);

    if (!$has_grant) {
      echo json_encode(['success' => false, 'message' => lang('Sales.not_authorized')]);
    } else {
      $sale_ids = $sale_id == NEW_ENTRY ? $this->request->getPost('ids', FILTER_SANITIZE_NUMBER_INT) : [$sale_id];

      if ($this->sale->restore_list($sale_ids, $employee_id, $update_inventory)) {
        echo json_encode([
          'success' => true,
          'message' => lang('Sales.successfully_restored') . ' ' . count($sale_ids) . ' ' . lang('Sales.one_or_multiple'),
          'ids' => $sale_ids
        ]);
      } else {
        echo json_encode(['success' => false, 'message' => lang('Sales.unsuccessfully_restored')]);
      }
    }
  }

  /**
   * This saves the sale from the update sale view (sales/form).
   * It only updates the sales table and payments.
   * @param int $sale_id
   * @throws ReflectionException
   */
  public function postSave(int $sale_id = NEW_ENTRY): void
  {
    $newdate = $this->request->getPost('date', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $employee_id = $this->employee->get_logged_in_employee_info()->person_id;
    $inventory = model(Inventory::class);
    $date_formatter = date_create_from_format($this->config['dateformat'] . ' ' . $this->config['timeformat'], $newdate);
    $sale_time = $date_formatter->format('Y-m-d H:i:s');

    $sale_data = [
      'sale_time' => $sale_time,
      'customer_id' => $this->request->getPost('customer_id') != '' ? $this->request->getPost('customer_id', FILTER_SANITIZE_NUMBER_INT) : null,
      'employee_id' => $this->request->getPost('employee_id') != '' ? $this->request->getPost('employee_id', FILTER_SANITIZE_NUMBER_INT) : null,
      'comment' => $this->request->getPost('comment', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
      'invoice_number' => $this->request->getPost('invoice_number') != '' ? $this->request->getPost('invoice_number', FILTER_SANITIZE_FULL_SPECIAL_CHARS) : null
    ];

    // In order to maintain tradition the only element that can change on prior payments is the payment type
    $amount_tendered = 0;
    $number_of_payments = $this->request->getPost('number_of_payments', FILTER_SANITIZE_NUMBER_INT);
    for ($i = 0; $i < $number_of_payments; ++$i) {
      $payment_id = $this->request->getPost("payment_id_$i", FILTER_SANITIZE_NUMBER_INT);
      $payment_type = $this->request->getPost("payment_type_$i", FILTER_SANITIZE_FULL_SPECIAL_CHARS);
      $payment_amount = parse_decimals($this->request->getPost("payment_amount_$i"));
      $refund_type = $this->request->getPost("refund_type_$i", FILTER_SANITIZE_FULL_SPECIAL_CHARS);
      $cash_refund = parse_decimals($this->request->getPost("refund_amount_$i"));

      $cash_adjustment = $payment_type == lang('Sales.cash_adjustment') ? CASH_ADJUSTMENT_TRUE : CASH_ADJUSTMENT_FALSE;

      if (!$cash_adjustment) {
        $amount_tendered += $payment_amount - $cash_refund;
      }

      //Non-cash positive refund amounts
      if (empty(strstr($refund_type, lang('Sales.cash'))) && $cash_refund > 0)    //TODO: This if and the one below can be combined.
      {
        //Change it to be a new negative payment (a "non-cash refund")
        $payment_type = $refund_type;
        $payment_amount = $payment_amount - $cash_refund;
        $cash_refund = 0.00;
      }

      $sale_data['payments'][] = [
        'payment_id' => $payment_id,
        'payment_type' => $payment_type,
        'payment_amount' => $payment_amount,
        'cash_refund' => $cash_refund,
        'cash_adjustment' => $cash_adjustment,
        'employee_id' => $employee_id
      ];
    }

    $payment_id = NEW_ENTRY;
    $payment_amount_new = $this->request->getPost('payment_amount_new');
    $payment_type = $this->request->getPost('payment_type_new', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    if ($payment_type != PAYMENT_TYPE_UNASSIGNED && !empty($payment_amount_new)) {
      $payment_amount = parse_decimals($payment_amount_new);
      $cash_refund = 0;
      if ($payment_type == lang('Sales.cash_adjustment')) {
        $cash_adjustment = CASH_ADJUSTMENT_TRUE;
      } else {
        $cash_adjustment = CASH_ADJUSTMENT_FALSE;
        $amount_tendered += $payment_amount;
        $sale_info = $this->sale->get_info($sale_id)->getRowArray();

        if ($amount_tendered > $sale_info['amount_due']) {
          $cash_refund = $amount_tendered - $sale_info['amount_due'];
        }
      }

      $sale_data['payments'][] = [
        'payment_id' => $payment_id,
        'payment_type' => $payment_type,
        'payment_amount' => $payment_amount,
        'cash_refund' => $cash_refund,
        'cash_adjustment' => $cash_adjustment,
        'employee_id' => $employee_id
      ];
    }

    $inventory->update('POS ' . $sale_id, ['trans_date' => $sale_time]);    //TODO: Reflection Exception
    if ($this->sale->update($sale_id, $sale_data)) {
      echo json_encode(['success' => true, 'message' => lang('Sales.successfully_updated'), 'id' => $sale_id]);
    } else {
      echo json_encode(['success' => false, 'message' => lang('Sales.unsuccessfully_updated'), 'id' => $sale_id]);
    }
  }

  /**
   * This is used to cancel a suspended pos sale, quote.
   * Completed sales (POS Sales or Invoiced Sales) can not be removed from the system
   * Work orders can be canceled but are not physically removed from the sales history.
   * Used in app/Views/sales/register.php
   *
   * @throws ReflectionException
   * @noinspection PhpUnused
   */
  public function postCancel(): void
  {
    $sale_id = $this->sale_lib->get_sale_id();
    if ($sale_id != NEW_ENTRY && $sale_id != '') {
      $sale_type = $this->sale_lib->get_sale_type();

      if ($this->config['dinner_table_enable']) {
        $dinner_table = $this->sale_lib->get_dinner_table();
        $this->dinner_table->release($dinner_table);
      }

      if ($sale_type == SALE_TYPE_WORK_ORDER) {
        $this->sale->update_sale_status($sale_id, CANCELED);
      } else {
        $this->sale->delete($sale_id);
        $this->session->set('sale_id', NEW_ENTRY);
      }
    } else {
      $this->sale_lib->remove_temp_items();
    }

    $this->sale_lib->clear_all();
    $this->_reload();    //TODO: Hungarian notation
  }

  /**
   * Discards the suspended sale. Used in app/Views/sales/quote.php
   *
   * @return void
   * @noinspection PhpUnused
   */
  public function getDiscardSuspendedSale(): void
  {
    $suspended_id = $this->sale_lib->get_suspended_id();
    $this->sale_lib->clear_all();
    $this->sale->delete_suspended_sale($suspended_id);
    $this->_reload();    //TODO: Hungarian notation
  }

  /**
   * Suspend the current sale.
   * If the current sale is already suspended then update the existing suspended sale otherwise create
   * it as a new suspended sale. Used in app/Views/sales/register.php.
   *
   * @throws ReflectionException
   * @noinspection PhpUnused
   */
  public function postSuspend(): void
  {
    $sale_id = $this->sale_lib->get_sale_id();
    $dinner_table = $this->sale_lib->get_dinner_table();
    $cart = $this->sale_lib->get_cart();
    $payments = $this->sale_lib->get_payments();
    $employee_id = $this->employee->get_logged_in_employee_info()->person_id;
    $customer_id = $this->sale_lib->get_customer();
    $invoice_number = $this->sale_lib->get_invoice_number();
    $work_order_number = $this->sale_lib->get_work_order_number();
    $quote_number = $this->sale_lib->get_quote_number();
    $sale_type = $this->sale_lib->get_sale_type();

    if ($sale_type == '') {
      $sale_type = SALE_TYPE_POS;
    }

    $comment = $this->sale_lib->get_comment();
    $sale_status = SUSPENDED;

    $data = [];
    $sales_taxes = [[], []];

    if ($this->sale->save_value($sale_id, $sale_status, $cart, $customer_id, $employee_id, $comment, $invoice_number, $work_order_number, $quote_number, $sale_type, $payments, $dinner_table, $sales_taxes) == '-1') {
      $data['error'] = lang('Sales.unsuccessfully_suspended_sale');
    } else {
      $data['success'] = lang('Sales.successfully_suspended_sale');
    }

    $this->sale_lib->clear_all();

    $this->_reload($data);    //TODO: Hungarian notation
  }

  /**
   * List suspended sales
   */
  public function getSuspended(): void
  {
    $data = [];
    $customer_id = $this->sale_lib->get_customer();
    $data['suspended_sales'] = $this->sale->get_all_suspended($customer_id);
    echo view('sales/suspended', $data);
  }

  /**
   * Unsuspended sales are now left in the tables and are only removed
   * when they are intentionally cancelled. Used in app/Views/sales/suspended.php.
   *
   *
   * @return void
   * @noinspection PhpUnused
   */
  public function postUnsuspend(): void
  {
    $sale_id = $this->request->getPost('suspended_sale_id', FILTER_SANITIZE_NUMBER_INT);
    $this->sale_lib->clear_all();

    if ($sale_id > 0) {
      $this->sale_lib->copy_entire_sale($sale_id);
    }

    // Set current register mode to reflect that of unsuspended order type
    $this->change_register_mode($this->sale_lib->get_sale_type());

    $this->_reload();    //TODO: Hungarian notation
  }

  /**
   * Show Keyboard shortcut modal. Used in app/Views/sales/register.php
   *
   * @return void
   * @noinspection PhpUnused
   */
  public function getSalesKeyboardHelp(): void
  {
    echo view('sales/help');
  }

  /**
   * Check the validity of an invoice number. Used in app/Views/sales/form.php.
   *
   * @return void
   * @noinspection PhpUnused
   */
  public function postCheckInvoiceNumber(): void
  {
    $sale_id = $this->request->getPost('sale_id', FILTER_SANITIZE_NUMBER_INT);
    $invoice_number = $this->request->getPost('invoice_number', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $exists = !empty($invoice_number) && $this->sale->check_invoice_number_exists($invoice_number, $sale_id);
    echo !$exists ? 'true' : 'false';
  }

  /**
   * @param array $cart
   * @return array
   */
  public function get_filtered(array $cart): array
  {
    $filtered_cart = [];
    foreach ($cart as $id => $item) {
      if ($item['print_option'] == PRINT_ALL) // always include
      {
        $filtered_cart[$id] = $item;
      } elseif ($item['print_option'] == PRINT_PRICED && $item['price'] != 0)  // include only if the price is not zero
      {
        $filtered_cart[$id] = $item;
      }
      // print_option 2 is never included
    }

    return $filtered_cart;
  }

  /**
   * Update the item number in the register. Used in app/Views/sales/register.php
   *
   * @return void
   * @noinspection PhpUnused
   */
  public function postChangeItemNumber(): void
  {
    $item_id = $this->request->getPost('item_id', FILTER_SANITIZE_NUMBER_INT);
    $item_number = $this->request->getPost('item_number', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $this->item->update_item_number($item_id, $item_number);
    $cart = $this->sale_lib->get_cart();
    $x = $this->search_cart_for_item_id($item_id, $cart);
    if ($x != null) {
      $cart[$x]['item_number'] = $item_number;
    }
    $this->sale_lib->set_cart($cart);
  }

  /**
   * Change a given item name. Used in app/Views/sales/register.php.
   *
   * @return void
   * @noinspection PhpUnused
   */
  public function postChangeItemName(): void
  {
    $item_id = $this->request->getPost('item_id', FILTER_SANITIZE_NUMBER_INT);
    $name = $this->request->getPost('item_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    $this->item->update_item_name($item_id, $name);

    $cart = $this->sale_lib->get_cart();
    $x = $this->search_cart_for_item_id($item_id, $cart);

    if ($x != null) {
      $cart[$x]['name'] = $name;
    }

    $this->sale_lib->set_cart($cart);
  }

  /**
   * Update the given item description.  Used in app/Views/sales/register.php
   *
   * @return void
   * @noinspection PhpUnused
   */
  public function postChangeItemDescription(): void
  {
    $item_id = $this->request->getPost('item_id', FILTER_SANITIZE_NUMBER_INT);
    $description = $this->request->getPost('item_description', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    $this->item->update_item_description($item_id, $description);

    $cart = $this->sale_lib->get_cart();
    $x = $this->search_cart_for_item_id($item_id, $cart);

    if ($x != null) {
      $cart[$x]['description'] = $description;
    }

    $this->sale_lib->set_cart($cart);
  }

  /**
   * @param int $search_item_id
   * @param array $shopping_cart
   * @return int|string|null
   */
  public function search_cart_for_item_id(int $search_item_id, array $shopping_cart): int|string|null
  {
    foreach ($shopping_cart as $key => $val) {
      if ($val['item_id'] === $search_item_id) {
        return $key;
      }
    }

    return null;
  }

  /**
   * Select customer by ID
   */
  public function selectCustomerById(): void
  {
    $customer_id = $this->request->getPost('customer_id');

    if ($customer_id) {
      $this->session->set('customer_id', $customer_id);
      echo json_encode(['success' => true]);
    } else {
      echo json_encode(['success' => false]);
    }
  }

  /**
   * Get all past sales with items for a specific customer
   * 
   * @param int $customer_id
   * @return array
   */
  private function getCustomerSalesWithItems(int $customer_id): array
  {
    // Get all sales for this customer using the Sale model
    $sales_query = $this->sale->where('customer_id', $customer_id)
      ->where('sale_status', COMPLETED)
      ->orderBy('sale_time', 'DESC')
      ->limit(20)
      ->findAll();

    $sales = [];

    foreach ($sales_query as $sale_row) {
      $sale_id = $sale_row['sale_id'];
      $vehicle_id = $sale_row['vehicle_id']; // Get vehicle_id
      // Get vehicle info if vehicle_id exists
      $vehicle_info = null;
      if ($sale_row['vehicle_id']) {
        $vehicle = model(\App\Models\Vehicle::class);
        $vehicle_info = $vehicle->find($sale_row['vehicle_id']);
      }
      // Get vehicle info if vehicle_id exists
      $vehicle_info = null;
      if ($vehicle_id) {
        $vehicle = model(\App\Models\Vehicle::class);
        $vehicle_info = $vehicle->find($vehicle_id);
      }
      // Get sale info using the existing method
      $sale_info = $this->sale->get_info($sale_id)->getRowArray();

      // Get employee info
      $employee_info = $this->employee->get_info($sale_info['employee_id']);

      // Get sale items using the existing method
      $sale_items_query = $this->sale->get_sale_items($sale_id);
      $sale_items = $sale_items_query->getResultArray();

      // Calculate totals from items
      $sale_total = 0;
      foreach ($sale_items as &$item) {
        $quantity = (float)$item['quantity_purchased'];
        $unit_price = (float)$item['item_unit_price'];
        $discount = (float)$item['discount'];
        $discount_type = (int)$item['discount_type'];

        $line_total = $quantity * $unit_price;

        if ($discount > 0) {
          if ($discount_type == PERCENTAGE) {
            $discount_amount = $line_total * ($discount / 100);
          } else {
            $discount_amount = $discount;
          }
          $line_total -= $discount_amount;
        }

        $item['discounted_total'] = $line_total;
        $sale_total += $line_total;
      }

      // Get payments using the existing method
      $payments_query = $this->sale->get_sale_payments($sale_id);
      $payments = $payments_query->getResultArray();

      // Build the sale array
      $sale = [
        'sale_id' => $sale_id,
        'sale_time' => $sale_info['sale_time'],
        'sale_status' => $sale_info['sale_status'],
        'invoice_number' => $sale_info['invoice_number'],
        'quote_number' => $sale_info['quote_number'],
        'employee_first_name' => $employee_info->first_name,
        'employee_last_name' => $employee_info->last_name,
        'sale_total' => $sale_total,
        'items' => $sale_items,
        'payments' => $payments,
        'total_items' => count($sale_items),
        'total_quantity' => array_sum(array_column($sale_items, 'quantity_purchased')),
        'total_payments' => array_sum(array_column($payments, 'payment_amount')),
        'vehicle_id' => $sale_row['vehicle_id'],
        'vehicle_kilometer' => $sale_row['vehicle_kilometer'],
        'vehicle_avg_oil_km' => $sale_row['vehicle_avg_oil_km'],
        'vehicle_avg_km_day' => $sale_row['vehicle_avg_km_day'],
        'vehicle_info' => $vehicle_info
      ];

      // Get primary payment type
      $primary_payment = 'Mixed';
      if (!empty($payments)) {
        if (count($payments) == 1) {
          $primary_payment = $payments[0]['payment_type'];
        } else {
          $largest_payment = array_reduce($payments, function ($carry, $payment) {
            return (!$carry || $payment['payment_amount'] > $carry['payment_amount']) ? $payment : $carry;
          });
          $primary_payment = $largest_payment['payment_type'] ?? 'Mixed';
        }
      }
      $sale['primary_payment_type'] = $primary_payment;

      $sales[] = $sale;
    }

    return $sales;
  }

  /**
   * Get customer sales history via AJAX
   * 
   * @return \CodeIgniter\HTTP\ResponseInterface
   * @noinspection PhpUnused
   */
  public function getCustomerSalesHistory(): void
  {
    $customer_id = (int)$this->request->getGet('customer_id', FILTER_SANITIZE_NUMBER_INT);

    if (!$customer_id || !$this->customer->exists($customer_id)) {
      echo json_encode(['success' => false, 'message' => 'Invalid customer']);
      return;
    }

    try {
      $sales_history = $this->getCustomerSalesWithItems($customer_id);

      if (!empty($sales_history)) {
        echo json_encode([
          'success' => true,
          'customer_id' => $customer_id,
          'sales_count' => count($sales_history),
          'sales' => $sales_history
        ]);
      } else {
        echo json_encode([
          'success' => true,
          'customer_id' => $customer_id,
          'sales_count' => 0,
          'sales' => [],
          'message' => 'No sales found'
        ]);
      }
    } catch (\Exception $e) {
      log_message('error', 'Customer sales history error: ' . $e->getMessage());
      echo json_encode([
        'success' => false,
        'message' => 'Error retrieving sales history: ' . $e->getMessage()
      ]);
    }
  }
}
