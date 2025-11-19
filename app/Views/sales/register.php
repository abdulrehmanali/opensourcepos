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

  <!-- Top register controls -->
  <?= form_open("$controller_name/changeMode", ['id' => 'mode_form', 'class' => 'form-horizontal panel panel-default']) ?>
  <div class="panel-body form-group">
    <ul>
      <li class="pull-left first_li">
        <label class="control-label"><?= lang(ucfirst($controller_name) . '.mode') ?></label>
      </li>
      <li class="pull-left">
        <?= form_dropdown('mode', $modes, $mode, ['onchange' => "$('#mode_form').submit();", 'class' => 'selectpicker show-menu-arrow', 'data-style' => 'btn-default btn-sm', 'data-width' => 'fit']) ?>
      </li>

      <?php
      if ($config['dinner_table_enable']) {
      ?>
        <li class="pull-left first_li">
          <label class="control-label"><?= lang(ucfirst($controller_name) . '.table') ?></label>
        </li>
        <li class="pull-left">
          <?= form_dropdown('dinner_table', $empty_tables, $selected_table, ['onchange' => "$('#mode_form').submit();", 'class' => 'selectpicker show-menu-arrow', 'data-style' => 'btn-default btn-sm', 'data-width' => 'fit']) ?>
        </li>
      <?php
      }
      if (count($stock_locations) > 1) {
      ?>
        <li class="pull-left">
          <label class="control-label"><?= lang(ucfirst($controller_name) . '.stock_location') ?></label>
        </li>
        <li class="pull-left">
          <?= form_dropdown('stock_location', $stock_locations, $stock_location, ['onchange' => "$('#mode_form').submit();", 'class' => 'selectpicker show-menu-arrow', 'data-style' => 'btn-default btn-sm', 'data-width' => 'fit']) ?>
        </li>
      <?php
      }
      ?>

      <!-- Center navigation buttons for sales records -->
      <li class="pull-left" style="text-align: center; flex-grow: 1; display: flex; justify-content: center; gap: 10px; padding: 0 20px;">
        <button type="button" class='btn btn-default btn-sm' id='sales_back_button' title="Previous Sale">
          <span class="glyphicon glyphicon-chevron-left">&nbsp</span>Back
        </button>
        <button type="button" class='btn btn-primary btn-sm' id='sales_new_button' title="New Sale">
          <span class="glyphicon glyphicon-plus">&nbsp</span>New
        </button>
        <button type="button" class='btn btn-default btn-sm' id='sales_next_button' title="Next Sale">
          <span class="glyphicon glyphicon-chevron-right">&nbsp</span>Next
        </button>
      </li>

      <li class="pull-right">
        <button class='btn btn-default btn-sm modal-dlg' id='show_suspended_sales_button' data-href="<?= esc("$controller_name/suspended") ?>"
          title="<?= lang(ucfirst($controller_name) . '.suspended_sales') ?>">
          <span class="glyphicon glyphicon-align-justify">&nbsp</span><?= lang(ucfirst($controller_name) . '.suspended_sales') ?>
        </button>
      </li>

      <?php
      $employee = model(Employee::class);
      if ($employee->has_grant('reports_sales', session('person_id'))) {
      ?>
        <li class="pull-right">
          <?= anchor(
            "$controller_name/manage",
            '<span class="glyphicon glyphicon-list-alt">&nbsp</span>' . lang(ucfirst($controller_name) . '.takings'),
            array('class' => 'btn btn-primary btn-sm', 'id' => 'sales_takings_button', 'title' => lang(ucfirst($controller_name) . '.takings'))
          ) ?>
        </li>
      <?php
      }
      ?>
    </ul>
  </div>
  <?= form_close() ?>
  
<div id="sales-register-root"></div>

<script>
  // Pass PHP data to React app
  window.salesRegisterProps = {
    controller_name: '<?= $controller_name ?>',
    customer_id: <?= isset($customer_id) ? $customer_id : 'null' ?>,
    selected_vehicle_id: <?= isset($selected_vehicle_id) ? $selected_vehicle_id : 'null' ?>,
    selected_vehicle_no: <?= isset($selected_vehicle_no) ? json_encode($selected_vehicle_no) : '""' ?>,
    sale_id: <?= isset($sale_id) ? $sale_id : 'null' ?>,
    modes: <?= json_encode($modes ?? []) ?>,
    mode: '<?= $mode ?? 'sale' ?>',
    payment_options: <?= json_encode($payment_options ?? []) ?>,
    cart: <?= json_encode($cart ?? []) ?>,
    total: <?= $total ?? 0 ?>,
    amount_due: <?= $amount_due ?? 0 ?>,
    config: <?= json_encode($config ?? []) ?>
  };
</script>

<!-- Sales Navigation Script -->
<script>
  document.addEventListener('DOMContentLoaded', function() {
    const BASE_URL = '<?= base_url() ?>';
    const controller_name = '<?= $controller_name ?>';

    // Get current sale_id from URL
    function getCurrentSaleId() {
      const params = new URLSearchParams(window.location.search);
      return params.get('sale_id');
    }

    // Navigate to a new sale
    function navigateToSale(saleId) {
      if (!saleId) return;
      const newUrl = new URL(window.location);
      newUrl.searchParams.set('sale_id', saleId);
      window.location.href = newUrl.toString();
    }

    // New Sale - open in new window
    document.getElementById('sales_new_button').addEventListener('click', function() {
      const newWindow = window.open(window.location.href.split('?')[0], '_blank');
      if (newWindow) {
        newWindow.focus();
      }
    });

    // Back - get previous sale
    document.getElementById('sales_back_button').addEventListener('click', function() {
      const currentSaleId = getCurrentSaleId();
      if (!currentSaleId) return;

      fetch(`${BASE_URL}/${controller_name}/previousSale/${currentSaleId}`)
        .then(response => response.json())
        .then(data => {
          if (data.success && data.sale_id) {
            navigateToSale(data.sale_id);
          } else {
            alert('No previous sale found');
          }
        })
        .catch(err => console.error('Error fetching previous sale:', err));
    });

    // Next - get next sale
    document.getElementById('sales_next_button').addEventListener('click', function() {
      const currentSaleId = getCurrentSaleId();
      if (!currentSaleId) return;

      fetch(`${BASE_URL}/${controller_name}/getNextSale/${currentSaleId}`)
        .then(response => response.json())
        .then(data => {
          if (data.success && data.sale_id) {
            navigateToSale(data.sale_id);
          } else {
            alert('No next sale found');
          }
        })
        .catch(err => console.error('Error fetching next sale:', err));
    });
  });
</script>

<?php if ($isDevelopment && $devServerRunning): ?>
  <!-- Development mode: Load from Vite dev server -->
  <script type="module" src="http://localhost:5173/@vite/client"></script>
  <script type="module">
    import RefreshRuntime from "/@react-refresh";
    console.log('React Refresh Runtime loaded:', RefreshRuntime);
    RefreshRuntime.injectIntoGlobalHook(window);
    window.$RefreshReg$ = () => {};
    window.$RefreshSig$ = () => (type) => type;
    window.__vite_plugin_react_preamble_installed__ = true;
  </script>
  <script type="module" src="http://localhost:5173/src/main.jsx"></script>
<?php else: ?>
  <!-- Production mode: Load built assets -->
  <?php if (file_exists(FCPATH . 'sales-register/assets/index.css')): ?>
    <link rel="stylesheet" href="<?= base_url('sales-register/assets/index.css') ?>">
  <?php endif; ?>
  
  <?php if (file_exists(FCPATH . 'sales-register/assets/index.js')): ?>
    <script type="module" src="<?= base_url('sales-register/assets/index.js?v=12') ?>"></script>
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