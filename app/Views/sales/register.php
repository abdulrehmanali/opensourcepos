<?php

/**
 * @var string $controller_name
 * @var array $modes
 * @var array $mode
 * @var array $empty_tables
 * @var array $selected_table
 * @var array $stock_locations
 * @var array $stock_location
 * @var array $cart
 * @var bool $items_module_allowed
 * @var bool $change_price
 * @var int $customer_id
 * @var int $customer_discount_type
 * @var float $customer_discount
 * @var float $customer_total
 * @var string $customer_required
 * @var string $customer_email
 * @var string $customer_phone
 * @var string $customer_address
 * @var string $customer_location
 * @var array $customer_rewards 
 * @var float|int $item_count
 * @var float|int $total_units
 * @var float $subtotal
 * @var array $taxes
 * @var float $total
 * @var float $payments_total
 * @var float $amount_due
 * @var bool $payments_cover_total
 * @var array $payment_options
 * @var array $selected_payment_type
 * @var bool $pos_mode
 * @var array $payments
 * @var string $mode_label
 * @var string $comment
 * @var bool $print_after_sale
 * @var bool $email_receipt
 * @var bool $price_work_orders
 * @var string $invoice_number
 * @var int $cash_mode
 * @var float $non_cash_total
 * @var float $cash_amount_due
 * @var array $config
 */

use App\Models\Employee;

?>
<?= view('partial/header') ?>

<?php
if (isset($error)) {
  echo "<div class='alert alert-dismissible alert-danger'>$error</div>";
}

if (!empty($warning)) {
  echo "<div class='alert alert-dismissible alert-warning'>$warning</div>";
}

if (isset($success)) {
  echo "<div class='alert alert-dismissible alert-success'>$success</div>";
}

// Check if we're in development mode
$isDevelopment = ENVIRONMENT === 'development' || get_cookie('debug') === 'true' || $this->request->getGet('debug') === 'true';
$devServerRunning = false;

if ($isDevelopment) {
    // Check if Vite dev server is running
    $context = stream_context_create(['http' => ['timeout' => 1]]);
    $devServerRunning = @file_get_contents('http://localhost:5173', false, $context) !== false;
}
?>

<div id="react-sales-register"></div>

<script>
  // Pass PHP data to React app
  window.salesRegisterProps = {
    controller_name: '<?= $controller_name ?>',
    customer_id: <?= isset($customer_id) ? $customer_id : 'null' ?>,
    modes: <?= json_encode($modes ?? []) ?>,
    mode: '<?= $mode ?? 'sale' ?>',
    payment_options: <?= json_encode($payment_options ?? []) ?>,
    cart: <?= json_encode($cart ?? []) ?>,
    total: <?= $total ?? 0 ?>,
    amount_due: <?= $amount_due ?? 0 ?>,
    config: <?= json_encode($config ?? []) ?>
  };
</script>

<?php if ($isDevelopment && $devServerRunning): ?>
  <!-- Development mode: Load from Vite dev server -->
  <script type="module" src="http://localhost:5173/@vite/client"></script>
  <script type="module" src="http://localhost:5173/src/main.jsx"></script>
<?php else: ?>
  <!-- Production mode: Load built assets -->
  <?php if (file_exists(FCPATH . 'sales-register/assets/index.css')): ?>
    <link rel="stylesheet" href="<?= base_url('sales-register/assets/index.css') ?>">
  <?php endif; ?>
  
  <?php if (file_exists(FCPATH . 'sales-register/assets/index.js')): ?>
    <script type="module" src="<?= base_url('sales-register/assets/index.js') ?>"></script>
  <?php else: ?>
    <div class="alert alert-warning">
      <strong>React app not built!</strong> 
      <p>Run the following commands to build the React app:</p>
      <pre>cd sales-register && npm run build</pre>
      <p>Or start the development server:</p>
      <pre>cd sales-register && npm run dev</pre>
    </div>
  <?php endif; ?>
<?php endif; ?>

<?= view('partial/footer') ?>