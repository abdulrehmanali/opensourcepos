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
?>
<div class="row">
  <div id="register_wrapper" class="col-sm-7">
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

    <?php $tabindex = 7; ?>

    <div class="panel-body">
      <div class="row">
        <div class="col-sm-4">
          <div class="form-group form-group-sm">
            <?= form_label(lang('Vehicle No'), 'vehicle_no', ['class' => 'required control-label', 'style' => 'width:100%;']) ?>
            <?= form_input([
              'name' => 'vehicle_no',
              'id' => 'vehicle_no',
              'class' => 'form-control input-sm',
              'value' => isset($customer_vehicle_no) ? $customer_vehicle_no : '',
              'tabindex' => "1"
            ]) ?>
          </div>
          <div class="form-group form-group-sm">
            <?= form_label('Kilometer', 'vehicle_kilometer', ['class' => 'control-label', 'style' => 'width:100%;']) ?>
            <?= form_input([
              'name' => 'vehicle_kilometer',
              'id' => 'vehicle_kilometer',
              'class' => 'form-control input-sm',
              'value' => isset($customer_kilometer) ? $customer_kilometer : '',
              'type' => 'number',
              'tabindex' => "4"
            ]) ?>
          </div>
          <div class="form-group form-group-sm">
            <?= form_label('Next Visit', 'vehicle_next_visit', ['class' => 'control-label', 'style' => 'width:100%;']) ?>
            <?= form_input([
              'type' => 'date',
              'name' => 'vehicle_next_visit',
              'id' => 'vehicle_next_visit',
              'class' => 'form-control input-sm',
              'value' => isset($customer_next_visit) ? $customer_next_visit : '',
              'tabindex' => "7"
            ]) ?>
          </div>
        </div>

        <div class="col-sm-4">
          <div class="form-group form-group-sm">
            <?= form_label(lang('Customer Name'), 'customer_name', ['class' => 'required control-label', 'style' => 'width:100%;']) ?>
            <?= form_input([
              'name' => 'customer_name',
              'id' => 'customer_name',
              'class' => 'form-control input-sm',
              'value' => isset($customer) ? $customer : '',
              'tabindex' => "2"
            ]) ?>
          </div>
          <div class="form-group form-group-sm">
            <?= form_label('Avg KM / Day', 'vehicle_avg_km_day', ['class' => 'control-label', 'style' => 'width:100%;']) ?>
            <?= form_input([
              'name' => 'vehicle_avg_km_day',
              'id' => 'vehicle_avg_km_day',
              'type' => 'number',
              'class' => 'form-control input-sm',
              'value' => isset($customer_avg_km_day) ? $customer_avg_km_day : '',
              'tabindex' => "6"
            ]) ?>
          </div>
        </div>

        <div class="col-sm-4">
          <div class="form-group form-group-sm">
            <?= form_label(lang('Common.phone_number'), 'phone_number', ['class' => 'required control-label', 'style' => 'width:100%;']) ?>
            <?= form_input([
              'name' => 'phone_number',
              'id' => 'phone_number',
              'type' => 'number',
              'class' => 'form-control input-sm',
              'value' => isset($customer_phone) ? $customer_phone : '',
              'tabindex' => "3"
            ]) ?>
          </div>
          <div class="form-group form-group-sm">
            <?= form_label('Avg KM/ Oil', 'vehicle_avg_oil_km', ['class' => 'control-label', 'style' => 'width:100%;']) ?>
            <?= form_input([
              'name' => 'vehicle_avg_oil_km',
              'id' => 'vehicle_avg_oil_km',
              'type' => 'number',
              'class' => 'form-control input-sm',
              'value' => isset($customer_avg_oil_km) ? $customer_avg_oil_km : '',
              'tabindex' => "6"
            ]) ?>
          </div>
        </div>
      </div>
    </div>
    <?= form_open("$controller_name/add", ['id' => 'add_item_form', 'class' => 'form-horizontal panel panel-default']) ?>
      <label for="item" class='control-label'><?= lang(ucfirst($controller_name) . '.find_or_scan_item_or_receipt') ?></label>
      <div class="panel-body row" style="display: flex;justify-content: center;align-items: end;">
        <div class="col-sm-4">
          <label class="control-label" for="item">Product Name</label>
          <?= form_input(['name' => 'item', 'id' => 'item', 'class' => 'form-control input-sm', 'size' => '50', 'tabindex' => ++$tabindex]) ?>
          <span class="ui-helper-hidden-accessible" role="status"></span>
        </div>
        <div class="col-sm-2">
          <label class="control-label" for="price">Price</label>
          <?= form_input(['name' => 'price', 'id' => 'price', 'class' => 'form-control input-sm', 'size' => '10', 'tabindex' => ++$tabindex, 'placeholder' => '0.00', 'type' => 'number', 'step' => '0.01', 'min' => '0']) ?>
        </div>
        <div class="col-sm-2">
          <label class="control-label" for="quantity">Quantity</label>
          <div class="input-group">
            <?= form_input(['name' => 'quantity', 'id' => 'quantity', 'class' => 'form-control input-sm', 'size' => '5', 'tabindex' => ++$tabindex, 'placeholder' => '1', 'type' => 'number', 'step' => '1', 'min' => '1']) ?>
            <span class="input-group-addon" style="padding:0;">
              <?= form_dropdown(
                  'unit',
                  ['pcs' => 'pcs', 'kg' => 'kg', 'ltr' => 'ltr', 'mL' => 'mL'],
                  isset($item['unit']) ? $item['unit'] : 'pcs',
                  ['class' => 'form-control input-sm', 'id' => 'unit', 'tabindex' => ++$tabindex, 'style' => 'width: 45px;padding: 0;height: 33px;border: 0;']
              ) ?>
            </span>
          </div>
        </div>
        <div class="col-sm-2">
          <label class="control-label" for="discount">Discount</label>
          <div class="input-group">
            <?= form_input(['name' => 'discount', 'id' => 'discount', 'class' => 'form-control input-sm', 'value' => '0.00', 'tabindex' => ++$tabindex, 'onclick' => 'this.select();']) ?>
            <span class="input-group-btn">
              <input type="checkbox" name="discount_toggle" value="1" id="discount_toggle" data-toggle="toggle" data-size="small" data-onstyle="success" data-on="<b>Rs</b>" data-off="<b>%</b>" data-line="1">
            </span>
          </div>
        </div>
        <div class="col-sm-2">
          <label class="control-label" for="add_item">&nbsp;</label>
          <button type="submit" class="btn btn-primary btn-sm" id="add_item" tabindex="<?= ++$tabindex ?>">
            <span class="glyphicon glyphicon-plus"></span> Add
          </button>
        </div>
      </div>
<?= form_close() ?>


    <!-- Sale Items List -->

    <table class="sales_table_100" id="register">
      <thead>
        <tr>
          <th style="width: 5%; "><?= lang('Common.delete') ?></th>
          <th style="width: 15%;"><?= lang(ucfirst($controller_name) . '.item_number') ?></th>
          <th style="width: 30%;"><?= lang(ucfirst($controller_name) . '.item_name') ?></th>
          <th style="width: 10%;"><?= lang(ucfirst($controller_name) . '.price') ?></th>
          <th style="width: 10%;"><?= lang(ucfirst($controller_name) . '.quantity') ?></th>
          <th style="width: 15%;"><?= lang(ucfirst($controller_name) . '.discount') ?></th>
          <th style="width: 10%;"><?= lang(ucfirst($controller_name) . '.total') ?></th>
          <th style="width: 5%; "><?= lang(ucfirst($controller_name) . '.update') ?></th>
        </tr>
      </thead>

      <tbody id="cart_contents">
        <?php
        if (count($cart) == 0) {
        ?>
          <tr>
            <td colspan='8'>
              <div class='alert alert-dismissible alert-info'><?= lang(ucfirst($controller_name) . '.no_items_in_cart') ?></div>
            </td>
          </tr>
          <?php
        } else {
          foreach (array_reverse($cart, true) as $line => $item) {
          ?>
            <?= form_open("$controller_name/editItem/$line", ['class' => 'form-horizontal', 'id' => "cart_$line"]) ?>
            <tr>
              <td>
                <?php
                echo anchor("$controller_name/deleteItem/$line", '<span class="glyphicon glyphicon-trash"></span>');
                echo form_hidden('location', (string)$item['item_location']);
                echo form_input(['type' => 'hidden', 'name' => 'item_id', 'value' => $item['item_id']]);
                ?>
              </td>
              <?php
              if ($item['item_type'] == ITEM_TEMP) {
              ?>
                <td><?= form_input(['name' => 'item_number', 'id' => 'item_number', 'class' => 'form-control input-sm', 'value' => $item['item_number'], 'tabindex' => ++$tabindex]) ?></td>
                <td style="align: center;">
                  <?= form_input(['name' => 'name', 'id' => 'name', 'class' => 'form-control input-sm', 'value' => $item['name'], 'tabindex' => ++$tabindex]) ?>
                </td>
              <?php
              } else {
              ?>
                <td><?= esc($item['item_number']) ?></td>
                <td style="align: center;">
                  <?= esc($item['name']) . ' ' . implode(' ', [$item['attribute_values'], $item['attribute_dtvalues']]) ?>
                  <br />
                  <?php if ($item['stock_type'] == '0'): echo '[' . to_quantity_decimals($item['in_stock']) . ' in ' . $item['stock_name'] . ']';
                  endif; ?>
                </td>
              <?php
              }
              ?>

              <td>
                <?php
                if ($items_module_allowed && $change_price) {
                  echo form_input(['name' => 'price', 'class' => 'form-control input-sm', 'value' => to_currency_no_money($item['price']), 'tabindex' => ++$tabindex, 'onClick' => 'this.select();']);
                } else {
                  echo to_currency($item['price']);
                  echo form_hidden('price', to_currency_no_money($item['price']));
                }
                ?>
              </td>

              <td>
                <?php
                if ($item['is_serialized']) {
                  echo to_quantity_decimals($item['quantity']);
                  echo form_hidden('quantity', $item['quantity']);
                } else {
                  echo form_input(['name' => 'quantity', 'class' => 'form-control input-sm', 'value' => to_quantity_decimals($item['quantity']), 'tabindex' => ++$tabindex, 'onClick' => 'this.select();']);
                }
                ?>
              </td>

              <td>
                <div class="input-group">
                  <?= form_input(['name' => 'discount', 'class' => 'form-control input-sm', 'value' => $item['discount_type'] ? to_currency_no_money($item['discount']) : to_decimals($item['discount']), 'tabindex' => ++$tabindex, 'onClick' => 'this.select();']) ?>
                  <span class="input-group-btn">
                    <?= form_checkbox(['id' => 'discount_toggle', 'name' => 'discount_toggle', 'value' => 1, 'data-toggle' => "toggle", 'data-size' => 'small', 'data-onstyle' => 'success', 'data-on' => '<b>' . $config['currency_symbol'] . '</b>', 'data-off' => '<b>%</b>', 'data-line' => $line, 'checked' => $item['discount_type'] == 1]) ?>
                  </span>
                </div>
              </td>

              <td>
                <?php
                if ($item['item_type'] == ITEM_AMOUNT_ENTRY)    //TODO: === ?
                {
                  echo form_input(['name' => 'discounted_total', 'class' => 'form-control input-sm', 'value' => to_currency_no_money($item['discounted_total']), 'tabindex' => ++$tabindex, 'onClick' => 'this.select();']);
                } else {
                  echo to_currency($item['discounted_total']);
                }
                ?>
              </td>

              <td><a href="javascript:document.getElementById('<?= "cart_$line" ?>').submit();" title=<?= lang(ucfirst($controller_name) . '.update') ?>><span class="glyphicon glyphicon-refresh"></span></a></td>
            </tr>
            <tr>
              <?php
              if ($item['item_type'] == ITEM_TEMP) {
              ?>
                <td><?= form_input(['type' => 'hidden', 'name' => 'item_id', 'value' => $item['item_id']]) ?></td>
                <td style="align: center;" colspan="6">
                  <?= form_input(['name' => 'item_description', 'id' => 'item_description', 'class' => 'form-control input-sm', 'value' => $item['description'], 'tabindex' => ++$tabindex]) ?>
                </td>
                <td> </td>
              <?php
              } else {
              ?>
                <td> </td>
                <?php
                if ($item['allow_alt_description']) {
                ?>
                  <td style="color: #2F4F4F;"><?= lang(ucfirst($controller_name) . '.description_abbrv') ?></td>
                <?php
                }
                ?>

                <td colspan='2' style="text-align: left;">
                  <?php
                  if ($item['allow_alt_description']) {
                    echo form_input(['name' => 'description', 'class' => 'form-control input-sm', 'value' => $item['description'], 'onClick' => 'this.select();']);
                  } else {
                    if ($item['description'] != '') {
                      echo $item['description'];
                      echo form_hidden('description', $item['description']);
                    } else {
                      echo lang(ucfirst($controller_name) . '.no_description');
                      echo form_hidden('description', '');
                    }
                  }
                  ?>
                </td>
                <td>&nbsp;</td>
                <td style="color: #2F4F4F;">
                  <?php
                  if ($item['is_serialized']) {
                    echo lang(ucfirst($controller_name) . '.serial');
                  }
                  ?>
                </td>
                <td colspan='4' style="text-align: left;">
                  <?php
                  if ($item['is_serialized']) {
                    echo form_input(['name' => 'serialnumber', 'class' => 'form-control input-sm', 'value' => $item['serialnumber'], 'onClick' => 'this.select();']);
                  } else {
                    echo form_hidden('serialnumber', '');
                  }
                  ?>
                </td>
              <?php
              }
              ?>
            </tr>
            <?= form_close() ?>
        <?php
          }
        }
        ?>
      </tbody>
    </table>
    <!-- Overall Sale -->
    <div id="overall_sale" class="panel panel-default" style="width:100%">
      <div class="panel-body">
        <table class="sales_table_100" id="sale_totals">
          <tr>
            <th style="width: 55%;"><?= lang(ucfirst($controller_name) . '.quantity_of_items', [$item_count]) ?></th>
            <th style="width: 45%; text-align: right;"><?= $total_units ?></th>
          </tr>
          <tr>
            <th style="width: 55%;"><?= lang(ucfirst($controller_name) . '.sub_total') ?></th>
            <th style="width: 45%; text-align: right;"><?= to_currency($subtotal) ?></th>
          </tr>

          <?php
          foreach ($taxes as $tax_group_index => $tax) {
          ?>
            <tr>
              <th style="width: 55%;"><?= (float)$tax['tax_rate'] . '% ' . $tax['tax_group'] ?></th>
              <th style="width: 45%; text-align: right;"><?= to_currency_tax($tax['sale_tax_amount']) ?></th>
            </tr>
          <?php
          }
          ?>

          <tr>
            <th style="width: 55%; font-size: 150%"><?= lang(ucfirst($controller_name) . '.total') ?></th>
            <th style="width: 45%; font-size: 150%; text-align: right;"><span id="sale_total"><?= to_currency($total) ?></span></th>
          </tr>
        </table>
        <table class="sales_table_100" id="payment_totals">
          <tr>
            <th style="width: 55%;"><?= lang(ucfirst($controller_name) . '.payments_total') ?></th>
            <th style="width: 45%; text-align: right;"><?= to_currency($payments_total) ?></th>
          </tr>
          <tr>
            <th style="width: 55%; font-size: 120%"><?= lang(ucfirst($controller_name) . '.amount_due') ?></th>
            <th style="width: 45%; font-size: 120%; text-align: right;"><span id="sale_amount_due"><?= to_currency($amount_due) ?></span></th>
          </tr>
        </table>

        <div id="payment_details">
          <?= form_open("$controller_name/addPayment", ['id' => 'add_payment_form', 'class' => 'form-horizontal']) ?>
          <div class="row">
            <?php
            $col_count = 0;
            foreach ($payment_options as $key => $payment_type):
              $amount = null;
              foreach ($payments as $payment_id => $payment) {
                if ($payment['payment_type'] == $payment_type) {
                  if (!$amount) {
                    $amount += $payment['payment_amount'];
                  }
                }
              }
            ?>
              <div class="col-sm-3">
                <div class="form-group form-group-sm">
                  <label class="control-label"><?= esc($payment_type) ?></label>
                  <?= form_input([
                    'name' => 'payment_amounts[' . esc($key) . ']',
                    'id' => 'payment_' . str_replace(' ', '_', strtolower($key)),
                    'class' => 'form-control input-sm payment-amount',
                    'data-payment-type' => esc($key),
                    'value' =>  $amount,
                    'placeholder' => '0.00',
                    'onClick' => 'this.select();'
                  ]) ?>
                </div>
              </div>
            <?php
            endforeach;
            ?>
          </div>
          <?= form_close() ?>

          <!-- Hidden form for individual payment submission -->
          <?= form_open("$controller_name/addPayment", ['id' => 'single_payment_form', 'style' => 'display: none;']) ?>
          <?= form_input(['type' => 'hidden', 'name' => 'payment_type', 'id' => 'hidden_payment_type']) ?>
          <?= form_input(['type' => 'hidden', 'name' => 'amount_tendered', 'id' => 'hidden_amount_tendered']) ?>
          <?= form_close() ?>
        </div>

        <?php
        // Show Complete sale button instead of Add Payment if there is no amount due left
        if ($payments_cover_total) {
        ?>
          <?php
          // Only show this part if in sale or return mode
          if ($pos_mode) {
            $due_payment = false;

            if (count($payments) > 0) {
              foreach ($payments as $payment) {
                if ($payment['payment_type'] == lang(ucfirst($controller_name) . '.due')) {
                  $due_payment = true;
                }
              }
            }
          }
          ?>
        <?php
        }
        ?>
        <div class='btn btn-sm btn-success pull-right' id='finish_sale_button' tabindex="<?= ++$tabindex ?>"><span class="glyphicon glyphicon-ok">&nbsp</span><?= lang(ucfirst($controller_name) . '.complete_sale') ?></div>
        <?= form_open("$controller_name/cancel", ['id' => 'buttons_form']) ?>
        <div class="form-group" id="buttons_sale">
          <div class='btn btn-sm btn-default pull-left' id='suspend_sale_button'><span class="glyphicon glyphicon-align-justify">&nbsp</span><?= lang(ucfirst($controller_name) . '.suspend_sale') ?></div>
          <?php
          // Only show this part if the payment covers the total
          if (!$pos_mode && isset($customer)) {
          ?>
            <div class='btn btn-sm btn-success' id='finish_invoice_quote_button'><span class="glyphicon glyphicon-ok">&nbsp</span><?= esc($mode_label) ?></div>
          <?php
          }
          ?>

          <div class='btn btn-sm btn-danger pull-right' id='cancel_sale_button'><span class="glyphicon glyphicon-remove">&nbsp</span><?= lang(ucfirst($controller_name) . '.cancel_sale') ?></div>
        </div>
        <?= form_close() ?>

        <?php
        // Only show this part if the payment cover the total
        if ($payments_cover_total || !$pos_mode) {
        ?>
          <div class="container-fluid">
            <div class="no-gutter row">
              <div class="form-group form-group-sm">
                <div class="col-xs-12">
                  <?= form_label(lang('Common.comments'), 'comments', ['class' => 'control-label', 'id' => 'comment_label', 'for' => 'comment']) ?>
                  <?= form_textarea(['name' => 'comment', 'id' => 'comment', 'class' => 'form-control input-sm', 'value' => $comment, 'rows' => '2']) ?>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="form-group form-group-sm">
                <div class="col-xs-6">
                  <label for="sales_print_after_sale" class="control-label checkbox">
                    <?= form_checkbox(['name' => 'sales_print_after_sale', 'id' => 'sales_print_after_sale', 'value' => 1, 'checked' => $print_after_sale]) ?>
                    <?= lang(ucfirst($controller_name) . '.print_after_sale') ?>
                  </label>
                </div>

                <?php
                if (!empty($customer_email)) {
                ?>
                  <div class="col-xs-6">
                    <label for="email_receipt" class="control-label checkbox">
                      <?= form_checkbox(['name' => 'email_receipt', 'id' => 'email_receipt', 'value' => 1, 'checked' => $email_receipt]) ?>
                      <?= lang(ucfirst($controller_name) . '.email_receipt') ?>
                    </label>
                  </div>
                <?php
                }
                ?>
                <?php
                if ($mode == 'sale_work_order') {
                ?>
                  <div class="col-xs-6">
                    <label for="price_work_orders" class="control-label checkbox">
                      <?= form_checkbox(['name' => 'price_work_orders', 'id' => 'price_work_orders', 'value' => 1, 'checked' => $price_work_orders]) ?>
                      <?= lang(ucfirst($controller_name) . '.include_prices') ?>
                    </label>
                  </div>
                <?php
                }
                ?>
              </div>
            </div>
            <?php
            if (($mode == 'sale_invoice') && $config['invoice_enable']) {
            ?>
              <div class="row">
                <div class="form-group form-group-sm">
                  <div class="col-xs-6">
                    <label for="sales_invoice_number" class="control-label checkbox">
                      <?= lang(ucfirst($controller_name) . '.invoice_enable') ?>
                    </label>
                  </div>

                  <div class="col-xs-6">
                    <div class="input-group input-group-sm">
                      <span class="input-group-addon input-sm">#</span>
                      <?= form_input(['name' => 'sales_invoice_number', 'id' => 'sales_invoice_number', 'class' => 'form-control input-sm', 'value' => $invoice_number]) ?>
                    </div>
                  </div>
                </div>
              </div>
            <?php
            }
            ?>
          </div>
        <?php
        }
        ?>
      </div>
    </div>
  </div>
  <div class="col-sm-5">
    <div id="customer_sales_history_section" style="margin-top: 2em;">
      <h4>Customer Sales History</h4>
      <div id="customer_history_loading" style="display:none;">
        <span class="glyphicon glyphicon-refresh spinning"></span> Loading sales history...
      </div>
      <div id="customer_sales_history">
        <!-- Sales history will be loaded here by JS -->
      </div>
    </div>
  </div>
</div>
<script type="application/javascript">
  // Add the typewatch function at the top
  var typewatch = function() {
    var timer = 0;
    return function(callback, ms) {
      clearTimeout(timer);
      timer = setTimeout(callback, ms);
    }
  }();

  const RegisterJs = {
    init: function() {
      this.utils.focusInitialField();
      this.events.bindAll();
      this.handlers.setupAutocomplete();
      this.handlers.setupKeyboardShortcuts();
      this.handlers.setupPaymentAutoAdd();

      // Calculate average on page load if data exists
      this.utils.calculateAveragePerDay();

      // If vehicle data exists on load, show the calculation
      this.utils.showCalculatedAverageOnLoad();
      this.utils.updateHeaderText();
    },

    utils: {
      redirect: function() {
        window.location.href = "<?= site_url('sales'); ?>";
      },
      focusInitialField: function() {
        $('#item').focus();
      },
      calculateAveragePerDay: function() {
        const avgOilKm = parseFloat($('#vehicle_avg_oil_km').val()) || 0;
        const avgKmDay = parseFloat($('#vehicle_avg_km_day').val()) || 0;
        if (avgOilKm > 0 && avgKmDay > 0) {
          const avgPerDay = avgOilKm / avgKmDay;
          const days = Math.round(avgPerDay);

          // Display with better formatting
          $('#calculated_avg_per_day').text('Next Visit in: ' + days + (days === 1 ? ' day' : ' days'));

          // Only set next visit date if it's not already set from database
          if (!$('#vehicle_next_visit').val()) {
            const today = new Date();
            const futureDate = new Date(today);
            futureDate.setDate(today.getDate() + days);
            const formattedDate = futureDate.toISOString().split('T')[0];
            $('#vehicle_next_visit').val(formattedDate);
          }
        } else {
          $('#calculated_avg_per_day').text('');
          // Don't clear vehicle_next_visit if it has a value from database
          if (!$('#vehicle_next_visit').val()) {
            $('#vehicle_next_visit').val('');
          }
        }
      },

      showCalculatedAverageOnLoad: function() {
        // Show calculated average if both values exist on page load
        const avgOilKm = parseFloat($('#vehicle_avg_oil_km').val()) || 0;
        const avgKmDay = parseFloat($('#vehicle_avg_km_day').val()) || 0;
        if (avgOilKm > 0 && avgKmDay > 0) {
          const days = Math.round(avgOilKm / avgKmDay);
          $('#calculated_avg_per_day').text('Next Visit in: ' + days + (days === 1 ? ' day' : ' days'));
        }
      },
      displayCustomerSalesHistory: function(sales, sales_count) {
        if (!Array.isArray(sales) || sales.length === 0) {
          $('#customer_sales_history').html('<div class="alert alert-info">No sales history found for this customer.</div>');
          $('#customer_sales_history').show();
          return;
        }

        let html = '<table class="table table-bordered table-striped">';
        html += `
        <thead>
          <tr>
            <th>ID #</th>
            <th>Date</th>
            <th>Product</th>
            <th>Kilometer</th>
            <th>Avg Oil KM</th>
            <th>Avg KM/Day</th>
          </tr>
        </thead>
        <tbody>`;

        sales.forEach(function(sale) {
          sale.items.forEach(function(item) {
            // If item is not a service, skip it
            html += `<tr>
              <td>${sale.sale_id ? sale.sale_id : ''}</td>
              <td>${sale.sale_time ? sale.sale_time : ''}</td>
              <td>${item.name ? item.name : ''}</td>
              <td>${sale.vehicle_kilometer ? sale.vehicle_kilometer : ''}</td>
              <td>${sale.vehicle_avg_oil_km ? sale.vehicle_avg_oil_km : ''}</td>
              <td>${sale.vehicle_avg_km_day ? sale.vehicle_avg_km_day : ''}</td>
            </tr>`;
          });
        });

        html += '</tbody></table>';
        $('#customer_sales_history').html(html);
        $('#customer_sales_history').show();
      },
      updateHeaderText: function() {
        const vehicleNo = $('#vehicle_no').val();
        const customerName = $('#customer_name').val();
        let headerText = '';

        if (vehicleNo) {
          headerText += `${vehicleNo} | `;
        }
        if (customerName) {
          headerText += `${customerName}`;
        }

        document.title = headerText;
      }
    },

    ajax: {
      saveVehicleData: function(callback) {
        const vehicleData = {
          vehicle_no: $('#vehicle_no').val(),
          kilometer: $('#vehicle_kilometer').val(),
          last_avg_oil_km: $('#vehicle_avg_oil_km').val(),
          last_avg_km_day: $('#vehicle_avg_km_day').val(),
          last_next_visit: $('#vehicle_next_visit').val(),
          last_customer_id: <?= isset($customer_id) ? $customer_id : 'null' ?>
        };

        if (vehicleData.vehicle_no) {
          $.ajax({
            url: "<?= site_url('vehicles/save') ?>",
            method: 'POST',
            data: vehicleData,
            dataType: 'json',
            success: function(response) {
              if (callback) callback(true);
              $.notify({
                message: 'Vehicle data saved successfully'
              }, {
                type: 'success'
              });
            },
            error: function() {
              $.notify({
                message: 'Warning: Vehicle data could not be saved'
              }, {
                type: 'warning'
              });
              if (callback) callback(false);
            }
          });
        } else {
          if (callback) callback(false);
        }
      },

      saveCurrentVehicleData: function() {
        const vehicleData = {
          vehicle_no: $('#vehicle_no').val(),
          kilometer: $('#vehicle_kilometer').val(),
          last_avg_oil_km: $('#vehicle_avg_oil_km').val(),
          last_avg_km_day: $('#vehicle_avg_km_day').val(),
          last_next_visit: $('#vehicle_next_visit').val(),
          last_customer_id: <?= isset($customer_id) ? $customer_id : 'null' ?>
        };

        // Only save if we have a vehicle number and at least one other field
        if (vehicleData.vehicle_no && (vehicleData.kilometer || vehicleData.last_avg_oil_km || vehicleData.last_avg_km_day)) {
          $.ajax({
            url: "<?= site_url('vehicles/save') ?>",
            method: 'POST',
            data: vehicleData,
            dataType: 'json',
            success: function(response) {
              console.log('Vehicle data saved on selection');
            },
            error: function() {
              console.log('Failed to save vehicle data on selection');
            }
          });
        }
      },

      loadVehicleData: function(vehicle_no) {
        typewatch(function() {
          const kilometer = $('#vehicle_kilometer').val();
          const avg_oil_km = $('#vehicle_avg_oil_km').val();
          const avg_km_day = $('#vehicle_avg_km_day').val();
          const vehicle_next_visit = $('#vehicle_next_visit').val();
          const customer_id = <?= isset($customer_id) ? $customer_id : 'null' ?>;
          console.log({
              vehicle_no: vehicle_no,
              kilometer: kilometer,
              last_avg_oil_km: avg_oil_km,
              last_avg_km_day: avg_km_day,
              last_next_visit: vehicle_next_visit,
              last_customer_id: customer_id
            })
          $.ajax({
            url: "<?= site_url('vehicles/getOrCreateByVehicleNo') ?>",
            method: 'GET',
            data: {
              vehicle_no: vehicle_no,
              kilometer: kilometer,
              last_avg_oil_km: avg_oil_km,
              last_avg_km_day: avg_km_day,
              last_next_visit: vehicle_next_visit,
              last_customer_id: customer_id
            },
            dataType: 'json',
            success: function(response) {
              if (response.success && response.vehicle) {
                $('#vehicle_kilometer').val(response.vehicle.kilometer || '');
                $('#vehicle_avg_oil_km').val(response.vehicle.last_avg_oil_km || '');
                $('#vehicle_avg_km_day').val(response.vehicle.last_avg_km_day || '');
                $('#vehicle_next_visit').val(response.vehicle.last_next_visit || '');
                RegisterJs.utils.calculateAveragePerDay();

                if (response.vehicle.last_customer_id) {
                  RegisterJs.ajax.loadCustomerById(response.vehicle.last_customer_id);
                }

                // Show different messages for found vs created vehicles
                if (response.created) {
                  $.notify({
                    message: 'New vehicle created: ' + response.vehicle.vehicle_no
                  }, {
                    type: 'success'
                  });
                } else {
                  $.notify({
                    message: 'Vehicle data loaded successfully'
                  }, {
                    type: 'success'
                  });
                }
              } else {
                // Clear fields if vehicle not found and couldn't be created
                $('#vehicle_kilometer, #vehicle_avg_oil_km, #vehicle_avg_km_day, #vehicle_next_visit').val('');
                $('#calculated_avg_per_day').text('');
                $.notify({
                  message: response.message || 'Error processing vehicle'
                }, {
                  type: 'warning'
                });
              }
            },
            error: function() {
              $.notify({
                message: 'Error loading vehicle data'
              }, {
                type: 'danger'
              });
            }
          });
        }, 300);
      },

      loadCustomerByPhone: function(phone_number) {
        typewatch(function() {
          const customer_name = $('#customer_name').val();

          $.ajax({
            url: "<?= site_url('customers/byPhoneNumberOrCreateCustomer') ?>",
            method: 'GET',
            data: {
              phone_number: phone_number,
              customer_name: customer_name
            },
            dataType: 'json',
            success: function(response) {
              if (response.success && response.customer) {
                // Customer found or created - select them
                $.post("<?= site_url('sales/selectCustomer') ?>", {
                  customer: response.customer.person_id
                }, function() {
                  location.reload();
                }).fail(function() {
                  $.notify({
                    message: 'Error selecting customer'
                  }, {
                    type: 'danger'
                  });
                });

                $('#customer_name').val(`${response.customer.first_name} ${response.customer.last_name}`);

                // Show different messages for found vs created customers
                if (response.created) {
                  $.notify({
                    message: 'New customer created and selected: ' + response.customer.full_name
                  }, {
                    type: 'success'
                  });
                } else {
                  $.notify({
                    message: 'Customer found and selected: ' + response.customer.full_name
                  }, {
                    type: 'success'
                  });
                }
              } else {
                $.notify({
                  message: response.message || 'Error processing customer'
                }, {
                  type: 'danger'
                });
              }
            },
            error: function() {
              $.notify({
                message: 'Error searching for customer'
              }, {
                type: 'danger'
              });
            }
          });
        }, 200);
      },

      loadCustomerById: function(customer_id) {
        $.ajax({
          url: "<?= site_url('customers/customerById') ?>",
          method: 'GET',
          data: {
            customer_id
          },
          dataType: 'json',
          success: function(response) {
            if (response.success && response.customer) {
              $.post("<?= site_url('sales/selectCustomer') ?>", {
                customer: customer_id
              }, function() {
                location.reload();
              }).fail(function() {
                $.notify({
                  message: 'Error selecting customer'
                }, {
                  type: 'danger'
                });
              });

              $('#customer_name').val(`${response.customer.first_name} ${response.customer.last_name}`);
              $('#phone_number').val(response.customer.phone_number || '');
            }
          }
        });
      },
      loadCustomerSalesHistory: function(customer_id) {
        if (!customer_id) {
          console.log('No customer ID provided');
          return;
        }

        $.ajax({
          url: "<?= site_url('sales/customerSalesHistory') ?>", // Changed from customerSalesHistory to getCustomerSalesHistory
          method: 'GET',
          data: {
            customer_id: customer_id
          },
          dataType: 'json',
          beforeSend: function() {
            $('#customer_history_loading').show();
            $('#customer_sales_history').hide();
          },
          success: function(response) {
            if (response.success && response.sales) {
              RegisterJs.utils.displayCustomerSalesHistory(response.sales, response.sales_count);
              // $.notify({ 
              //   message: `Loaded ${response.sales_count} past sales for customer` 
              // }, { type: 'info' });
            } else {
              $('#customer_sales_history').html('<div class="alert alert-info">No sales history found for this customer.</div>');
              $('#customer_sales_history').show();
            }
          },
          error: function() {
            $('#customer_sales_history').html('<div class="alert alert-danger">Error loading customer sales history.</div>');
            $('#customer_sales_history').show();
            $.notify({
              message: 'Error loading customer sales history'
            }, {
              type: 'danger'
            });
          },
          complete: function() {
            $('#customer_history_loading').hide();
          }
        });
      }
    },

    handlers: {
      setupAutocomplete: function() {
        // Item search with typewatch
        $('#item').autocomplete({
          source: function(request, response) {
            typewatch(function() {
              $.ajax({
                url: "<?= esc("$controller_name/itemSearch") ?>",
                data: {
                  term: request.term
                },
                dataType: 'json',
                success: function(data) {
                  if (Array.isArray(data)) {
                    response(data);
                  } else {
                    response([]);
                  }
                },
                error: function() {
                  response([]);
                }
              });
            }, 400);
          },
          minLength: 1,
          delay: 0,
          select: function(event, ui) {
            $(this).val(ui.item.value);
            $('#add_item_form').submit();
            return false;
          }
        }).keypress(function(e) {
          if (e.which === 13) {
            $('#add_item_form').submit();
            return false;
          }
        });

        // Vehicle search with typewatch
        $('#vehicle_no').autocomplete({
          source: function(request, response) {
            typewatch(function() {
              console.log('Vehicle search triggered', new Date());
              $.ajax({
                url: "<?= site_url('vehicles/suggest') ?>",
                data: {
                  term: request.term
                },
                dataType: 'json',
                success: function(data) {
                  if (Array.isArray(data)) {
                    response(data);
                  } else {
                    response([]);
                  }
                },
                error: function() {
                  response([]);
                }
              });
            }, 400);
          },
          minLength: 1,
          delay: 0,
          select: function(event, ui) {
            $(this).val(ui.item.value);

            // Load vehicle data first
            RegisterJs.ajax.loadVehicleData(ui.item.value);

            // Save current vehicle data if any exists
            RegisterJs.ajax.saveCurrentVehicleData();

            return false;
          }
        });

        // Customer search with typewatch
        $('#customer').autocomplete({
          source: function(request, response) {
            typewatch(function() {
              $.ajax({
                url: "<?= site_url('customers/suggest') ?>",
                data: {
                  term: request.term
                },
                dataType: 'json',
                success: function(data) {
                  if (Array.isArray(data)) {
                    response(data);
                  } else {
                    response([]);
                  }
                },
                error: function() {
                  response([]);
                }
              });
            }, 300);
          },
          minLength: 1,
          delay: 0,
          select: function(event, ui) {
            $(this).val(ui.item.value);
            $('#select_customer_form').submit();
            return false;
          }
        }).keypress(function(e) {
          if (e.which === 13) {
            $('#select_customer_form').submit();
            return false;
          }
        }).blur(function() {
          $(this).val("<?= lang(ucfirst($controller_name) . '.start_typing_customer_name') ?>");
        });

        // Gift card input
        $('.giftcard-input').autocomplete({
          source: "<?= site_url('giftcards/suggest') ?>",
          minLength: 1,
          delay: 200,
          select: function(event, ui) {
            $(this).val(ui.item.value);
            $('#add_payment_form').submit();
            return false;
          }
        });
      },

      setupKeyboardShortcuts: function() {
        document.body.onkeyup = function(e) {
          const map = {
            49: "#item",
            50: "#customer",
            51: "#suspend_sale_button",
            52: "#show_suspended_sales_button",
            53: "#amount_tendered",
            54: "#add_payment_button",
            55: "#add_payment_button",
            56: "#finish_invoice_quote_button",
            57: "#show_keyboard_help"
          };

          if (e.altKey && map[e.keyCode]) {
            $(map[e.keyCode]).click().focus().select();
          }

          if (e.keyCode === 27) {
            $("#cancel_sale_button").click();
          }
        };
      },

      setupPaymentAutoAdd: function() {
        $('.payment-amount').keypress(function(e) {
          if (e.which === 13) {
            const paymentType = $(this).data('payment-type');
            const amount = parseFloat($(this).val()) || 0;
            if (amount > 0) {
              $('#hidden_payment_type').val(paymentType);
              $('#hidden_amount_tendered').val(amount.toFixed(2));
              $('#single_payment_form').submit();
            }
          }
        });

        // $('#payment_cash').focus();
      }
    },

    events: {
      bindAll: function() {
        // Vehicle input event with typewatch
        $('#vehicle_no').on('keydown', function(e) {
          const val = $('#vehicle_no').val().trim().toUpperCase();
          console.log('Vehicle input keydown', e.key, val, new Date());
          console.log(e.key)
          if (val.length >= 2 && e.key === 'Enter') {
            e.preventDefault(); // prevent form submission
            RegisterJs.ajax.loadVehicleData(val);
            // RegisterJs.ajax.saveCurrentVehicleData();
          }
        });


        // Save vehicle data when any vehicle-related field changes
        $('#vehicle_kilometer, #vehicle_avg_oil_km, #vehicle_avg_km_day, #vehicle_next_visit').on('blur', function() {
          RegisterJs.ajax.saveCurrentVehicleData();
        });

        // Phone number input with typewatch
        $('#phone_number').on('change', function() {
          const phone = $(this).val();
          if (phone && phone.trim() !== '' && phone.length >= 3) {
            RegisterJs.ajax.loadCustomerByPhone(phone);
          }
        });

        // Average calculation inputs with typewatch for auto-save
        $('#vehicle_avg_oil_km, #vehicle_avg_km_day').on('change', function() {
          RegisterJs.utils.calculateAveragePerDay();
          // Auto-save after calculation using typewatch
          typewatch(function() {
            RegisterJs.ajax.saveCurrentVehicleData();
          }, 1000);
        });

        // Comment with typewatch
        $('#comment').keyup(function() {
          typewatch(function() {
            $.post("<?= esc(site_url("$controller_name/setComment")) ?>", {
              comment: $('#comment').val()
            });
          }, 500);
        });

        // Settings change with typewatch
        $('#sales_print_after_sale, #price_work_orders, #email_receipt').change(function() {
          const setting = this.id;
          const value = $(this).is(':checked');

          typewatch(function() {
            $.post(`<?= esc(site_url("$controller_name/")) ?>set${setting.charAt(0).toUpperCase() + setting.slice(1)}`, {
              [setting]: value
            });
          }, 200);
        });

        // Button events
        $('#finish_sale_button, #finish_invoice_quote_button').click(function(e) {
          e.preventDefault();
          RegisterJs.ajax.saveVehicleData(() => {
            $('#buttons_form').attr('action', "<?= "$controller_name/complete" ?>").submit();
          });
        });

        $('#suspend_sale_button').click(function() {
          RegisterJs.ajax.saveVehicleData(() => {
            $('#buttons_form').attr('action', "<?= site_url("$controller_name/suspend") ?>").submit();
          });
        });

        $('#cancel_sale_button').click(function() {
          if (confirm("<?= lang(ucfirst($controller_name) . '.confirm_cancel_sale') ?>")) {
            $('#buttons_form').attr('action', "<?= site_url("$controller_name/cancel") ?>").submit();
          }
        });

        $('#add_payment_button').click(function() {
          $('#add_payment_form').submit();
        });
      }
    },
  };

  $(document).ready(function() {
    RegisterJs.init();
    <?php if (isset($customer_id) && $customer_id): ?>
      RegisterJs.ajax.loadCustomerSalesHistory(<?= $customer_id ?>);
    <?php endif; ?>
  });
</script>
<?= view('partial/footer') ?>