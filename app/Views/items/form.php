<?php

/**
 * @var object $item_info
 * @var array $categories
 * @var int $selected_category
 * @var bool $standard_item_locked
 * @var bool $item_kit_disabled
 * @var int $allow_temp_item
 * @var array $suppliers
 * @var int $selected_supplier
 * @var bool $use_destination_based_tax
 * @var float $default_tax_1_rate
 * @var float $default_tax_2_rate
 * @var string $tax_category
 * @var int $tax_category_id
 * @var bool $include_hsn
 * @var string $hsn_code
 * @var array $stock_locations
 * @var bool $logo_exists
 * @var string $image_path
 * @var string $pdf_path
 * @var string $selected_low_sell_item
 * @var int $selected_low_sell_item_id
 * @var string $controller_name
 * @var array $config
 */
?>
<?= view('partial/header') ?>
<style>
  .dropdown.bootstrap-select.form-control.open {
    z-index: 10;
  }

  /* Product search table styling */

  #products_table {
    margin-bottom: 20px;
  }

  #products_table th {
    background-color: #f5f5f5;
    font-weight: bold;
  }

  #products_table tbody tr:hover {
    background-color: #f9f9f9;
  }

  .duplicate-btn,
  .view-btn {
    margin-right: 5px;
  }

  .pagination {
    margin: 0;
  }

  .table-responsive {
    max-height: 400px;
    overflow-y: auto;
  }

  #product_search {
    margin-bottom: 15px;
  }
</style>
<ul id="error_message_box" class="error_message_box"></ul>
<div class="row">
  <div class="col-sm-6">
    <div id="required_fields_message"><?= lang('Common.fields_required_message') ?></div>
    <?= form_open("items/save/$item_info->item_id", ['id' => 'item_form', 'enctype' => 'multipart/form-data', 'class' => 'form-horizontal']) ?>
    <fieldset id="item_basic_info">
      <div class="form-group form-group-sm">
        <?= form_label(lang('Items.category'), 'category', ['class' => 'required control-label col-xs-3']) ?>
        <div class='col-xs-8'>
          <div class="input-group">
            <span class="input-group-addon input-sm"><span class="glyphicon glyphicon-tag"></span></span>
            <?php
            if ($config['category_dropdown']) {
              echo form_dropdown('category[]', $categories, $selected_category, ['class' => 'form-control', 'name' => 'category[]', 'data-selected_category' => json_encode($selected_category), 'data-definition-label' => lang('Items.category')]);
            } else {
              echo form_input([
                'name' => 'category',
                'id' => 'category',
                'class' => 'form-control input-sm',
                'value' => $item_info->category
              ]);
            }
            ?>
          </div>
        </div>
      </div>
      <div id="attributes">
        <script type="application/javascript">
          <?php $attr_load_id = isset($duplicate_from_id) ? $duplicate_from_id : $item_info->item_id; ?>
          $('#attributes').load('<?= "items/attributes/" . $attr_load_id ?>');
        </script>
      </div>
      <!-- Brand
      Made
      API Grade
      Viscocity
      Engine Oil
      Made
      OEM Part Number
      Part Number
      Reference Part Number
      Whole Sale Price
      Retail Price
      Quantity Stock
      Reciving Quantity Stock
      Reorder Level
      Single Unit -->
      <div class="form-group form-group-sm">
        <?= form_label('Single Unit Quantity (e.g. mL)', 'single_unit_quantity', ['class' => 'control-label col-xs-3']) ?>
        <div class='col-xs-4'>
          <?= form_input([
            'name' => 'single_unit_quantity',
            'id' => 'single_unit_quantity',
            'class' => 'form-control input-sm',
            'placeholder' => 'e.g. 250',
            'value' => isset($item_info->single_unit_quantity) ? to_quantity_decimals($item_info->single_unit_quantity) : to_quantity_decimals(0)
          ]) ?>
          <small id="per_unit_price" class="form-text text-muted">
            <?= !empty($price_per_unit) ? "Per $selected_unit price: $price_per_unit" : '' ?>
          </small>
        </div>
        <div class='col-xs-4'>
          <select name="pack_name" class="form-control input-sm" id="pack_name">
            <?php foreach ($unit_options as $value => $label): ?>
              <option value="<?= esc($value) ?>" <?= $value === $selected_unit ? 'selected' : '' ?>>
                <?= esc($label) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-group form-group-sm">
        <?= form_label(lang('Items.cost_price'), 'cost_price', ['class' => 'required control-label col-xs-3']) ?>
        <div class="col-xs-4">
          <div class="input-group input-group-sm">
            <?php if (!is_right_side_currency_symbol()): ?>
              <span class="input-group-addon input-sm"><b><?= esc($config['currency_symbol']) ?></b></span>
            <?php endif; ?>
            <?= form_input([
              'name' => 'cost_price',
              'id' => 'cost_price',
              'class' => 'form-control input-sm',
              'onClick' => 'this.select();',
              'value' => to_currency_no_money($item_info->cost_price)
            ]) ?>
            <?php if (is_right_side_currency_symbol()): ?>
              <span class="input-group-addon input-sm"><b><?= esc($config['currency_symbol']) ?></b></span>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="form-group form-group-sm">
        <?= form_label(lang('Items.unit_price'), 'unit_price', ['class' => 'required control-label col-xs-3']) ?>
        <div class='col-xs-4'>
          <div class="input-group input-group-sm">
            <?php if (!is_right_side_currency_symbol()): ?>
              <span class="input-group-addon input-sm"><b><?= esc($config['currency_symbol']) ?></b></span>
            <?php endif; ?>
            <?= form_input([
              'name' => 'unit_price',
              'id' => 'unit_price',
              'class' => 'form-control input-sm',
              'onClick' => 'this.select();',
              'value' => to_currency_no_money($item_info->unit_price)
            ]) ?>
            <?php if (is_right_side_currency_symbol()): ?>
              <span class="input-group-addon input-sm"><b><?= esc($config['currency_symbol']) ?></b></span>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <?php
      foreach ($stock_locations as $key => $location_detail) {
      ?>
        <div class="form-group form-group-sm">
          <?= form_label(lang('Items.quantity') . ' ' . $location_detail['location_name'], "quantity_$key", ['class' => 'required control-label col-xs-3']) ?>
          <div class='col-xs-4'>
            <?= form_input([
              'name' => "quantity_$key",
              'id' => "quantity_$key",
              'class' => 'required quantity form-control',
              'onClick' => 'this.select();',
              'value' => isset($item_info->item_id) ? to_quantity_decimals($location_detail['quantity']) : to_quantity_decimals(0)
            ]) ?>
          </div>
        </div>
      <?php
      }
      ?>

      <div class="form-group form-group-sm">
        <?= form_label(lang('Items.receiving_quantity'), 'receiving_quantity', ['class' => 'required control-label col-xs-3']) ?>
        <div class='col-xs-4'>
          <?= form_input([
            'name' => 'receiving_quantity',
            'id' => 'receiving_quantity',
            'class' => 'required form-control input-sm',
            'onClick' => 'this.select();',
            'value' => isset($item_info->item_id) ? to_quantity_decimals($item_info->receiving_quantity) : to_quantity_decimals(0)
          ]) ?>
        </div>
      </div>

      <div class="form-group form-group-sm">
        <?= form_label(lang('Items.reorder_level'), 'reorder_level', ['class' => 'required control-label col-xs-3']) ?>
        <div class='col-xs-4'>
          <?= form_input([
            'name' => 'reorder_level',
            'id' => 'reorder_level',
            'class' => 'form-control input-sm',
            'onClick' => 'this.select();',
            'value' => isset($item_info->item_id) ? to_quantity_decimals($item_info->reorder_level) : to_quantity_decimals(0)
          ]) ?>
        </div>
      </div>
      <div class="form-group form-group-sm">
        <?= form_label(lang('Items.item_number'), 'item_number', ['class' => 'control-label col-xs-3']) ?>
        <div class='col-xs-8'>
          <div class="input-group">
            <span class="input-group-addon input-sm"><span class="glyphicon glyphicon-barcode"></span></span>
            <?= form_input([
              'name' => 'item_number',
              'id' => 'item_number',
              'class' => 'form-control input-sm',
              'value' => $item_info->item_number
            ]) ?>
          </div>
        </div>
      </div>

      <div class="form-group form-group-sm">
        <?= form_label(lang('Items.name'), 'name', ['class' => 'required control-label col-xs-3']) ?>
        <div class='col-xs-8'>
          <?= form_input([
            'name' => 'name',
            'id' => 'name',
            'class' => 'form-control input-sm',
            'value' => $item_info->name
          ]) ?>
        </div>
      </div>
      <div class="form-group form-group-sm">
        <?= form_label(lang('Items.stock_type'), 'stock_type', !empty($basic_version) ? ['class' => 'required control-label col-xs-3'] : ['class' => 'control-label col-xs-3']) ?>
        <div class="col-xs-8">
          <label class="radio-inline">
            <?= form_radio([
              'name' => 'stock_type',
              'type' => 'radio',
              'id' => 'stock_type',
              'value' => 0,
              'checked' => $item_info->stock_type == HAS_STOCK
            ]) ?> <?= lang('Items.stock') ?>
          </label>
          <label class="radio-inline">
            <?= form_radio([
              'name' => 'stock_type',
              'type' => 'radio',
              'id' => 'stock_type',
              'value' => 1,
              'checked' => $item_info->stock_type == HAS_NO_STOCK
            ]) ?><?= lang('Items.nonstock') ?>
          </label>
        </div>
      </div>

      <div class="form-group form-group-sm">
        <?= form_label(lang('Items.type'), 'item_type', !empty($basic_version) ? ['class' => 'required control-label col-xs-3'] : ['class' => 'control-label col-xs-3']) ?>
        <div class="col-xs-8">
          <label class="radio-inline">
            <?php
            $radio_button = [
              'name' => 'item_type',
              'type' => 'radio',
              'id' => 'item_type',
              'value' => 0,
              'checked' => $item_info->item_type == ITEM
            ];

            if ($standard_item_locked) {
              $radio_button['disabled'] = true;
            }
            echo form_radio($radio_button) ?> <?= lang('Items.standard') ?>
          </label>
          <label class="radio-inline">
            <?php
            $radio_button = [
              'name' => 'item_type',
              'type' => 'radio',
              'id' => 'item_type',
              'value' => 1,
              'checked' => $item_info->item_type == ITEM_KIT
            ];

            if ($item_kit_disabled) {
              $radio_button['disabled'] = true;
            }
            echo form_radio($radio_button) ?> <?= lang('Items.kit') ?>
          </label>
          <?php
          if ($config['derive_sale_quantity'] == '1') {
          ?>
            <label class="radio-inline">
              <?= form_radio([
                'name' => 'item_type',
                'type' => 'radio',
                'id' => 'item_type',
                'value' => 2,
                'checked' => $item_info->item_type == ITEM_AMOUNT_ENTRY
              ]) ?><?= lang('Items.amount_entry') ?>
            </label>
          <?php
          }
          ?>
          <?php
          if ($allow_temp_item == 1) {
          ?>
            <label class="radio-inline">
              <?= form_radio([
                'name' => 'item_type',
                'type' => 'radio',
                'id' => 'item_type',
                'value' => 3,
                'checked' => $item_info->item_type == ITEM_TEMP
              ]) ?> <?= lang('Items.temp') ?>
            </label>
          <?php
          }
          ?>
        </div>
      </div>

      <div class="form-group form-group-sm">
        <?= form_label(lang('Items.supplier'), 'supplier', ['class' => 'control-label col-xs-3']) ?>
        <div class='col-xs-8'>
          <?= form_dropdown('supplier_id', $suppliers, $selected_supplier, ['class' => 'form-control']) ?>
        </div>
      </div>

      <?php
      if (!$use_destination_based_tax) {
      ?>
        <div class="form-group form-group-sm">
          <?= form_label(lang('Items.tax_1'), 'tax_percent_1', ['class' => 'control-label col-xs-3']) ?>
          <div class='col-xs-4'>
            <?= form_input([
              'name' => 'tax_names[]',
              'id' => 'tax_name_1',
              'class' => 'form-control input-sm',
              'value' => $item_tax_info[0]['name'] ?? $config['default_tax_1_name']
            ]) ?>
          </div>
          <div class="col-xs-4">
            <div class="input-group input-group-sm">
              <?= form_input([
                'name' => 'tax_percents[]',
                'id' => 'tax_percent_name_1',
                'class' => 'form-control input-sm',
                'value' => isset($item_tax_info[0]['percent']) ? to_tax_decimals($item_tax_info[0]['percent']) : to_tax_decimals($default_tax_1_rate)
              ]) ?>
              <span class="input-group-addon input-sm"><b>%</b></span>
            </div>
          </div>
        </div>

        <div class="form-group form-group-sm">
          <?= form_label(lang('Items.tax_2'), 'tax_percent_2', ['class' => 'control-label col-xs-3']) ?>
          <div class='col-xs-4'>
            <?= form_input([
              'name' => 'tax_names[]',
              'id' => 'tax_name_2',
              'class' => 'form-control input-sm',
              'value' => $item_tax_info[1]['name'] ?? $config['default_tax_2_name']
            ]) ?>
          </div>
          <div class="col-xs-4">
            <div class="input-group input-group-sm">
              <?= form_input([
                'name' => 'tax_percents[]',
                'class' => 'form-control input-sm',
                'id' => 'tax_percent_name_2',
                'value' => isset($item_tax_info[1]['percent']) ? to_tax_decimals($item_tax_info[1]['percent']) : to_tax_decimals($default_tax_2_rate)
              ]) ?>
              <span class="input-group-addon input-sm"><b>%</b></span>
            </div>
          </div>
        </div>
      <?php
      }
      ?>

      <?php if ($use_destination_based_tax): ?>
        <div class="form-group form-group-sm">
          <?= form_label(lang('Taxes.tax_category'), 'tax_category', ['class' => 'control-label col-xs-3']) ?>
          <div class='col-xs-8'>
            <div class="input-group input-group-sm">
              <?= form_input([
                'name' => 'tax_category',
                'id' => 'tax_category',
                'class' => 'form-control input-sm',
                'size' => '50',
                'value' => $tax_category
              ]) ?><?= form_hidden('tax_category_id', $tax_category_id) ?>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <?php if ($include_hsn): ?>
        <div class="form-group form-group-sm">
          <?= form_label(lang('Items.hsn_code'), 'category', ['class' => 'control-label col-xs-3']) ?>
          <div class='col-xs-8'>
            <div class="input-group">
              <?= form_input([
                'name' => 'hsn_code',
                'id' => 'hsn_code',
                'class' => 'form-control input-sm',
                'value' => $hsn_code
              ]) ?>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <div class="form-group form-group-sm">
        <?= form_label(lang('Items.description'), 'description', ['class' => 'control-label col-xs-3']) ?>
        <div class='col-xs-8'>
          <?= form_textarea([
            'name' => 'description',
            'id' => 'description',
            'class' => 'form-control input-sm',
            'value' => $item_info->description
          ]) ?>
        </div>
      </div>

      <div class="form-group form-group-sm">
        <?= form_label(lang('Items.image'), 'items_image', ['class' => 'control-label col-xs-3']) ?>
        <div class='col-xs-8'>
          <div class="fileinput <?= $logo_exists ? 'fileinput-exists' : 'fileinput-new' ?>" data-provides="fileinput">
            <div class="fileinput-new thumbnail" style="width: 100px; height: 100px;"></div>
            <div class="fileinput-preview fileinput-exists thumbnail" style="max-width: 100px; max-height: 100px;">
              <img data-src="holder.js/100%x100%" alt="<?= lang('Items.image') ?>"
                src="<?= $image_path ?>"
                style="max-height: 100%; max-width: 100%;">
            </div>
            <div>
              <span class="btn btn-default btn-sm btn-file">
                <span class="fileinput-new"><?= lang('Items.select_image') ?></span>
                <span class="fileinput-exists"><?= lang('Items.change_image') ?></span>
                <input type="file" name="items_image" accept="image/*">
              </span>
              <a href="#" class="btn btn-default btn-sm fileinput-exists" data-dismiss="fileinput"><?= lang('Items.remove_image') ?></a>
            </div>
          </div>
        </div>
      </div>

      <div class="form-group form-group-sm">
        <?= form_label(lang('Items.pdf'), 'items_pdf', ['class' => 'control-label col-xs-3']) ?>
        <div class='col-xs-8'>
          <div class="fileinput <?= $logo_exists ? 'fileinput-exists' : 'fileinput-new' ?>" data-provides="fileinput">
            <?= $pdf_path ? "<a href='" . lang('Items.view_pdf') . "'></a>" : '' ?>
            <div>
              <span class="btn btn-default btn-sm btn-file">
                <span class="fileinput-new"><?= lang('Items.select_pdf') ?></span>
                <span class="fileinput-exists"><?= lang('Items.change_pdf') ?></span>
                <input type="file" name="items_pdf" accept="application/pdf">
              </span>
              <a href="#" class="btn btn-default btn-sm fileinput-exists" data-dismiss="fileinput"><?= lang('Items.remove_pdf') ?></a>
            </div>
          </div>
        </div>
      </div>

      <div class="form-group form-group-sm">
        <?= form_label(lang('Items.allow_alt_description'), 'allow_alt_description', ['class' => 'control-label col-xs-3']) ?>
        <div class='col-xs-1'>
          <?= form_checkbox([
            'name' => 'allow_alt_description',
            'id' => 'allow_alt_description',
            'value' => 1,
            'checked' => $item_info->allow_alt_description == 1
          ]) ?>
        </div>
      </div>

      <div class="form-group form-group-sm">
        <?= form_label(lang('Items.is_serialized'), 'is_serialized', ['class' => 'control-label col-xs-3']) ?>
        <div class='col-xs-1'>
          <?= form_checkbox([
            'name' => 'is_serialized',
            'id' => 'is_serialized',
            'value' => 1,
            'checked' => $item_info->is_serialized == 1
          ]) ?>
        </div>
      </div>

      <?php
      if ($config['multi_pack_enabled'] == '1') {
      ?>
        <div class="form-group form-group-sm">
          <?= form_label(lang('Items.qty_per_pack'), 'qty_per_pack', ['class' => 'control-label col-xs-3']) ?>
          <div class='col-xs-4'>
            <?= form_input([
              'name' => 'qty_per_pack',
              'id' => 'qty_per_pack',
              'class' => 'form-control input-sm',
              'value' => isset($item_info->item_id) ? to_quantity_decimals($item_info->qty_per_pack) : to_quantity_decimals(0)
            ]) ?>
          </div>
        </div>
        <div class="form-group form-group-sm">
          <?= form_label(lang('Items.pack_name'), 'name', ['class' => 'control-label col-xs-3']) ?>
          <div class='col-xs-8'>
            <?= form_input([
              'name' => 'pack_name',
              'id' => 'pack_name',
              'class' => 'form-control input-sm',
              'value' => $item_info->pack_name
            ]) ?>
          </div>
        </div>
        <div class="form-group  form-group-sm">
          <?= form_label(lang('Items.low_sell_item'), 'low_sell_item_name', ['class' => 'control-label col-xs-3']) ?>
          <div class='col-xs-8'>
            <div class="input-group input-group-sm">
              <?= form_input([
                'name' => 'low_sell_item_name',
                'id' => 'low_sell_item_name',
                'class' => 'form-control input-sm',
                'value' => $selected_low_sell_item
              ]) ?><?= form_hidden('low_sell_item_id', $selected_low_sell_item_id) ?>
            </div>
          </div>
        </div>
      <?php
      }
      ?>

      <div class="form-group form-group-sm">
        <?= form_label(lang('Items.is_deleted'), 'is_deleted', ['class' => 'control-label col-xs-3']) ?>
        <div class='col-xs-1'>
          <?= form_checkbox([
            'name' => 'is_deleted',
            'id' => 'is_deleted',
            'value' => 1,
            'checked' => $item_info->deleted == 1
          ]) ?>
        </div>
      </div>

    </fieldset>
    <div class="text-center">
      <button class="btn btn-primary btn-block" id="submit">Submit</button>
    </div>
    <?= form_close() ?>
  </div>
  <div class="col-sm-6">
    <!-- Product Search and Selection Table -->
    <fieldset id="product_search_section">
      <legend><?= lang('Items.existing_products') ?></legend>

      <div class="form-group">
        <input type="text" id="product_search" class="form-control" placeholder="<?= lang('Items.search_products') ?>..." />
      </div>

      <div class="form-group">
        <div class="col-xs-12">
          <div class="table-responsive">
            <table id="products_table" class="table table-striped table-hover">
              <thead>
                <tr>
                  <th><?= lang('Common.id') ?></th>
                  <th><?= lang('Items.item_number') ?></th>
                  <th><?= lang('Items.name') ?></th>
                  <th><?= lang('Items.category') ?></th>
                  <th><?= lang('Items.cost_price') ?></th>
                  <th><?= lang('Items.unit_price') ?></th>
                  <th><?= lang('Items.quantity') ?></th>
                  <th><?= lang('Common.actions') ?></th>
                </tr>
              </thead>
              <tbody id="products_tbody">
                <!-- Products will be loaded here via AJAX -->
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="form-group">
        <div class="col-xs-12">
          <nav aria-label="Products pagination">
            <ul class="pagination" id="products_pagination">
              <!-- Pagination will be generated here -->
            </ul>
          </nav>
        </div>
      </div>
    </fieldset>
  </div>
</div>


<script type="application/javascript">
  //validation and submit handling
    // Define global submitItemForm to use ONLY ajaxSubmit, no fallback/default submission
    window.submitItemForm = function() {
      $('#item_form').ajaxSubmit({
        url: "<?= site_url('items/save/' . $item_info->item_id) ?>",
        success: function(response) {
          console.log(response);
          let stay_open = dialog_support.clicked_id() != 'submit';
          if (stay_open) {
            $('#item_form').attr('action', "<?= 'items/save/' ?>");
            $(':text, :password, :file, #description, #item_form').not('.quantity, #reorder_level, #tax_name_1, #receiving_quantity, ' +
              '#tax_percent_name_1, #category, #reference_number, #name, #cost_price, #unit_price, #taxed_cost_price, #taxed_unit_price, #definition_name, [name^="attribute_links"]').val('');
            $(':input', '#item_form').removeAttr('checked').removeAttr('selected');
          } else {
            dialog_support.hide();
          }
          alert(response.message);
          if (response.success) {
            location.replace('/public/items');
          }
        },
        dataType: 'json'
      });
    };

    // Use AJAX submit for #item_form, prevent default submission
    $('#item_form').on('submit', function(e) {
      e.preventDefault(); // Prevent default form submission
      submitItemForm(); // Use AJAX submit function
    });

    $('[name="category[]"]').select2({
      multiple: true,
      tags: true,
      allowClear: true,
      placeholder: "Select an category",
      createTag: function(params) {
        const term = params.term.trim();
        if (term === '') {
          return null;
        }

        // Capitalize first letter of each word (optional: you can just capitalize the first letter of the string instead)
        const capitalized = term.replace(/\b\w/g, (l) => l.toUpperCase());

        return {
          id: capitalized,
          text: capitalized,
          newTag: true // mark it as a new tag
        };
      }
    });

    function handleCategorySelectCheck() {
      const selectedOptions = $('select[name="category[]"]').data('selected_category')
      if (!selectedOptions || selectedOptions.length === 0 || selectedOptions[0] === '') {
        $('select[name="category[]"]').val(null).trigger('change');
      }
    }
    handleCategorySelectCheck()

    // $('[name="category"]').selectpicker();
    // $('#category').autocomplete({
    //   source: "<?= 'items/suggestCategory' ?>",
    //   delay: 10,
    //   appendTo: '.modal-content',
    //   select: function(event, ui) {
    //     event.preventDefault();
    //     // Check if option already exists
    //     if ($("#category option[value='" + ui.item.value + "']").length === 0) {
    //       $('#category').append(new Option(ui.item.label, ui.item.value, true, true)).trigger('change');
    //     } else {
    //       $('#category').val(ui.item.value).trigger('change');
    //     }
    //     $('#category').selectpicker('refresh');
    //   },
    //   focus: function(event, ui) {
    //     event.preventDefault();
    //     $('#category').val(ui.item.label);
    //   }
    // });

    // Prevent form submission on Enter in input fields except textarea
    $('#item_form').on('keydown', 'input', function(e) {
      if (e.key === 'Enter' && !$(this).is('textarea')) {
        e.preventDefault(); // Prevent form submission
      }
    });
    // Prevent form submission on Enter in input fields except textarea and tagsinput
    // $('#item_form').on('keydown', 'input', function(e) {
    //   // Only prevent if not textarea and not tagsinput
    //   if (
    //     e.key === 'Enter' &&
    //     !$(this).is('textarea') &&
    //     !($(this).data('role') === 'tagsinput')
    //   ) {
    //     e.preventDefault();
    //   }
    // });
    // $('#category').tagsinput({
    //   trimValue: true,
    //   maxTags: 10,
    //   tagClass: 'badge badge-primary',
    //   typeahead: {
    //     source: function(query, process) {
    //       $.ajax({
    //         url: "<?= 'items/suggestCategory' ?>",
    //         data: { term: query },
    //         success: function(data) {
    //           process(data);
    //         }
    //       });
    //     }
    //   }
    // });

    $('#category').on('keydown', function(e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        e.stopPropagation();
      }
    });

    $('#category').on('itemAdded', function(event) {
      window.lastTagInputEvent = 'itemAdded';
      setTimeout(function() {
        $('#category').tagsinput('focus');
      }, 0);
    });

    document.addEventListener('keydown', function(e) {
      if (e.ctrlKey && e.key === 's') {
        e.preventDefault(); // Stop the browser's "Save page" action
        $('#item_form').submit();
      }
    });

    $('#new').click(function() {
      let stay_open = true;
      $('#item_form').submit();
    });

    $('#submit').click(function() {
      let stay_open = false;
    });

    $("input[name='tax_category']").change(function() {
      !$(this).val() && $(this).val('');
    });

    var fill_tax_category_value = function(event, ui) {
      event.preventDefault();
      $("input[name='tax_category_id']").val(ui.item.value);
      $("input[name='tax_category']").val(ui.item.label);
    };

    $('#tax_category').autocomplete({
      source: "<?= 'taxes/suggestTaxCategories' ?>",
      minChars: 0,
      delay: 15,
      cacheLength: 1,
      appendTo: '.modal-content',
      select: fill_tax_category_value,
      focus: fill_tax_category_value
    });

    var fill_low_sell_value = function(event, ui) {
      event.preventDefault();
      $("input[name='low_sell_item_id']").val(ui.item.value);
      $("input[name='low_sell_item_name']").val(ui.item.label);
    };

    $('#low_sell_item_name').autocomplete({
      source: "<?= 'items/suggestLowSell' ?>",
      minChars: 0,
      delay: 15,
      cacheLength: 1,
      appendTo: '.modal-content',
      select: fill_low_sell_value,
      focus: fill_low_sell_value
    });

    $('#category').autocomplete({
      source: "<?= 'items/suggestCategory' ?>",
      delay: 10,
      appendTo: '.modal-content'
    });

    $('a.fileinput-exists').click(function() {
      $.ajax({
        type: 'GET',
        url: '<?= "$controller_name/removeLogo/$item_info->item_id" ?>',
        dataType: 'json'
      })
    });

    $.validator.addMethod('valid_chars', function(value, element) {
      return value.match(/(\||_)/g) == null;
    }, "<?= lang('Attributes.attribute_value_invalid_chars') ?>");

    var init_validation = function() {
      return
      $('#item_form').validate($.extend({
        submitHandler: function(form, event) {
          //event is not used as a parameter here
          $(form).ajaxSubmit({
            success: function(response) {
              let stay_open = dialog_support.clicked_id() != 'submit';
              if (stay_open) {
                // set action of item_form to url without item id, so a new one can be created
                $('#item_form').attr('action', "<?= 'items/save/' ?>");
                // use a whitelist of fields to minimize unintended side effects
                $(':text, :password, :file, #description, #item_form').not('.quantity, #reorder_level, #tax_name_1, #receiving_quantity, ' +
                  '#tax_percent_name_1, #category, #reference_number, #name, #cost_price, #unit_price, #taxed_cost_price, #taxed_unit_price, #definition_name, [name^="attribute_links"]').val('');
                // de-select any checkboxes, radios and drop-down menus
                $(':input', '#item_form').removeAttr('checked').removeAttr('selected');
              } else {
                dialog_support.hide();
              }
              // table_support.handle_submit('<?= 'items' ?>', response, stay_open);
              // init_validation();
              location.replace('/public/items')
            },
            dataType: 'json'
          });
        },

        errorLabelContainer: '#error_message_box',

        rules: {
          name: 'required',
          category: 'required',
          item_number: {
            required: false,
            remote: {
              url: "<?= esc("$controller_name/checkItemNumber") ?>",
              type: 'POST',
              data: {
                'item_id': "<?= $item_info->item_id ?>"
                // item_number should be passed into the function by default
              }
            }
          },
          cost_price: {
            required: true,
            remote: "<?= esc("$controller_name/checkNumeric") ?>"
          },
          unit_price: {
            required: true,
            remote: "<?= esc("$controller_name/checkNumeric") ?>"
          },
          <?php
          foreach ($stock_locations as $key => $location_detail) {
          ?>
            <?= 'quantity_' . $key ?>: {
              required: true,
              remote: "<?= esc("$controller_name/checkNumeric") ?>"
            },
          <?php
          }
          ?>
          receiving_quantity: {
            required: true,
            remote: "<?= esc("$controller_name/checkNumeric") ?>"
          },
          reorder_level: {
            required: true,
            remote: "<?= esc("$controller_name/checkNumeric") ?>"
          },
          tax_percent: {
            required: false,
            remote: "<?= esc("$controller_name/checkNumeric") ?>"
          }
        },

        messages: {
          name: "<?= lang('Items.name_required') ?>",
          item_number: "<?= lang('Items.item_number_duplicate') ?>",
          category: "<?= lang('Items.category_required') ?>",
          cost_price: {
            required: "<?= lang('Items.cost_price_required') ?>",
            number: "<?= lang('Items.cost_price_number') ?>"
          },
          unit_price: {
            required: "<?= lang('Items.unit_price_required') ?>",
            number: "<?= lang('Items.unit_price_number') ?>"
          },
          <?php
          foreach ($stock_locations as $key => $location_detail) {
          ?>
            <?= esc("quantity_$key", 'js') ?>: {
              required: "<?= lang('Items.quantity_required') ?>",
              number: "<?= lang('Items.quantity_number') ?>"
            },
          <?php
          }
          ?>
          receiving_quantity: {
            required: "<?= lang('Items.quantity_required') ?>",
            number: "<?= lang('Items.quantity_number') ?>"
          },
          reorder_level: {
            required: "<?= lang('Items.reorder_level_required') ?>",
            number: "<?= lang('Items.reorder_level_number') ?>"
          },
          tax_percent: {
            number: "<?= lang('Items.tax_percent_number') ?>"
          }
        }
      }, form_support.error))
    };

    init_validation();

    // Product search and table functionality
    let currentPage = 1;
    let currentSearch = '';
    const itemsPerPage = 10;

    function loadProducts(page = 1, search = '') {
      currentPage = page;
      currentSearch = search;

      // Show loading indicator
      $('#products_tbody').html('<tr><td colspan="8" class="text-center"><i class="glyphicon glyphicon-refresh glyphicon-spin"></i> Loading products...</td></tr>');
      $('#products_pagination').html('');

      $.ajax({
        url: '<?= site_url("items/search") ?>',
        type: 'GET',
        data: {
          search: search,
          limit: itemsPerPage,
          offset: (page - 1) * itemsPerPage,
          sort: 'name',
          order: 'asc',
          start_date: '2010-01-01',
          end_date: '<?= date('Y-m-d') ?>'
        },
        dataType: 'json',
        success: function(response) {
          displayProducts(response.rows);
          updatePagination(response.total, page);
        },
        error: function() {
          $('#products_tbody').html('<tr><td colspan="8" class="text-center text-danger"><i class="glyphicon glyphicon-exclamation-sign"></i> Error loading products</td></tr>');
        }
      });
    }

    // Load top 10 products initially
    function loadInitialProducts() {
      loadProducts(1, ''); // Load first page with no search filter to get top 10 products
    }

    function displayProducts(products) {
      let html = '';
      if (products.length === 0) {
        html = '<tr><td colspan="8" class="text-center">No products found</td></tr>';
      } else {
        products.forEach(function(product) {
          html += `
            <tr>
              <td>${product['items.item_id']}</td>
              <td>${product.item_number || ''}</td>
              <td>${product.name}</td>
              <td>${product.category}</td>
              <td>${product.cost_price}</td>
              <td>${product.unit_price}</td>
              <td>${product.quantity}</td>
              <td>
                <button class="btn btn-sm btn-primary duplicate-btn" 
                        data-item-id="${product['items.item_id']}" 
                        title="Duplicate this item">
                  <span class="glyphicon glyphicon-duplicate"></span> Duplicate
                </button>
                <button class="btn btn-sm btn-info view-btn" 
                        data-item-id="${product['items.item_id']}" 
                        title="View this item">
                  <span class="glyphicon glyphicon-eye-open"></span> View
                </button>
              </td>
            </tr>
          `;
        });
      }
      $('#products_tbody').html(html);
    }

    function updatePagination(total, currentPage) {
      const totalPages = Math.ceil(total / itemsPerPage);
      let html = '';

      if (totalPages > 1) {
        // Previous button
        if (currentPage > 1) {
          html += `<li><a href="#" data-page="${currentPage - 1}">&laquo; Previous</a></li>`;
        }

        // Page numbers
        for (let i = 1; i <= totalPages; i++) {
          if (i === currentPage) {
            html += `<li class="active"><a href="#" data-page="${i}">${i}</a></li>`;
          } else if (i === 1 || i === totalPages || (i >= currentPage - 2 && i <= currentPage + 2)) {
            html += `<li><a href="#" data-page="${i}">${i}</a></li>`;
          } else if (i === currentPage - 3 || i === currentPage + 3) {
            html += `<li class="disabled"><span>...</span></li>`;
          }
        }

        // Next button
        if (currentPage < totalPages) {
          html += `<li><a href="#" data-page="${currentPage + 1}">Next &raquo;</a></li>`;
        }
      }

      $('#products_pagination').html(html);
    }

    // Search functionality
    let searchTimeout;
    $('#product_search').on('input', function() {
      clearTimeout(searchTimeout);
      const searchTerm = $(this).val().trim();

      searchTimeout = setTimeout(function() {
        loadProducts(1, searchTerm);
      }, 300);
    });

    $('#clear_search').click(function() {
      $('#product_search').val('');
      loadInitialProducts(); // Return to top 10 products view
    });

    // Pagination click handler
    $(document).on('click', '#products_pagination a', function(e) {
      e.preventDefault();
      const page = parseInt($(this).data('page'));
      if (page && page !== currentPage) {
        loadProducts(page, currentSearch);
      }
    });

    // Duplicate button handler
    $(document).on('click', '.duplicate-btn', function() {
      const itemId = $(this).data('item-id');
      if (confirm('Are you sure you want to duplicate this item?')) {
        window.location.href = `<?= site_url('items/duplicate/') ?>${itemId}`;
      }
    });

    // View button handler
    $(document).on('click', '.view-btn', function() {
      const itemId = $(this).data('item-id');
      window.open(`<?= site_url('items/view/') ?>${itemId}`, '_blank');
    });

    // Load initial top 10 products when page loads
    loadInitialProducts();

    // Auto-capitalize item name input (capitalize first letter of each word)
    const nameInput = document.querySelector('input[name="name"]');
    if (nameInput) {
      const capitalizeWords = (str) => str.replace(/\b\w/g, function(ch) { return ch.toUpperCase(); });

      nameInput.addEventListener('input', function(e) {
        // preserve cursor position
        const start = this.selectionStart;
        const end = this.selectionEnd;
        this.value = capitalizeWords(this.value);
        try { this.setSelectionRange(start, end); } catch (err) { /* ignore */ }
      });

      // Trim whitespace on blur
      nameInput.addEventListener('blur', function() {
        this.value = this.value.trim();
      });
    }

    // Capitalize all text-like inputs and textareas (title-case) unless opted out with data-no-capitalize="true"
    const capitalizeWords = (str) => str.replace(/\b\w/g, function(ch) { return ch.toUpperCase(); });

    function shouldCap(el) {
      if (!el) return false;
      if (el.dataset && el.dataset.noCapitalize === 'true') return false;
      if (el.tagName.toLowerCase() === 'textarea') return true;
      if (el.tagName.toLowerCase() === 'input') {
        const t = (el.getAttribute('type') || 'text').toLowerCase();
        return ['text', 'search', 'tel'].includes(t);
      }
      return false;
    }

    function attachCapitalizer(el) {
      if (!shouldCap(el)) return;
      el.addEventListener('input', function() {
        const start = this.selectionStart;
        const end = this.selectionEnd;
        this.value = capitalizeWords(this.value);
        try { this.setSelectionRange(start, end); } catch (e) { /* ignore */ }
      });
      el.addEventListener('blur', function() { this.value = this.value.trim(); });
    }

    // attach to existing inputs
    document.querySelectorAll('input, textarea').forEach(attachCapitalizer);

    // delegate for dynamically loaded attribute inputs inside #attributes
    const attrs = document.getElementById('attributes');
    if (attrs) {
      // when attributes content is reloaded via AJAX we re-attach handlers
      const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function() {
          attrs.querySelectorAll('input, textarea, select').forEach(attachCapitalizer);
        });
      });
      observer.observe(attrs, { childList: true, subtree: true });
    }

    // Autofill Item Name (Category - Brand - Made - Part Number) only when name is empty
      function firstCategoryText() {
        var sel = document.querySelector('select[name="category[]"]');
        if (!sel) return '';
        var selected = sel.querySelectorAll('option:checked');
        if (selected && selected.length) return (selected[0].text || selected[0].value || '').trim();
        var opt = sel.options[sel.selectedIndex];
        return opt ? (opt.text || opt.value || '').trim() : '';
      }

      function attributeValueByLabelRegex(re) {
        var els = document.querySelectorAll('[data-definition-label]');
        for (var i = 0; i < els.length; i++) {
          var lbl = (els[i].getAttribute('data-definition-label') || '').trim();
          if (re.test(lbl)) {
            var node = els[i];
            if (node.tagName && node.tagName.toLowerCase() === 'select') {
              var selOpt = node.querySelector('option:checked') || node.options[node.selectedIndex];
              return selOpt ? (selOpt.text || selOpt.value || '').trim() : (node.value || '').trim();
            }
            return (node.value || '').trim();
          }
        }
        return '';
      }

      function updateAutoNameIfEmpty() {
        // if (nameInput.value && nameInput.value.trim() !== '') return;
        var parts = [];
        var cat = firstCategoryText(); if (cat) parts.push(cat);
        var brand = attributeValueByLabelRegex(/brand/i); if (brand) parts.push(brand);
        var eng_oil_des = attributeValueByLabelRegex(/engine\s*oil/i); if (eng_oil_des) parts.push(eng_oil_des);
        var made = attributeValueByLabelRegex(/made/i); if (made) parts.push(made);
        var partNum = attributeValueByLabelRegex(/part\s*number/i) || attributeValueByLabelRegex(/ref\s*part/i);
        if (partNum) parts.push(partNum);
        var newName = parts.filter(Boolean).join(' - ');
        if (newName) nameInput.value = newName;
      }

      // initial
      updateAutoNameIfEmpty();

      if ($) {
        $(document).on('change input', 'select[name="category[]"], [data-definition-label]', updateAutoNameIfEmpty);
      } else {
        var catEl = document.querySelector('select[name="category[]"]');
        if (catEl) catEl.addEventListener('change', updateAutoNameIfEmpty);
        var attrEls = document.querySelectorAll('[data-definition-label]');
        attrEls.forEach(function(el) { el.addEventListener('change', updateAutoNameIfEmpty); el.addEventListener('input', updateAutoNameIfEmpty); });
      }

      // Per-unit helper: show per-unit price based on unit_price / qty_per_pack and selected pack_name
      const unitPriceInput = document.querySelector('input[name="unit_price"]');
      const qtyPerPackInput = document.querySelector('input[name="qty_per_pack"]');
      const unitSelect = document.querySelector('select[name="pack_name"]');
      const helper = document.getElementById("per_unit_price");

      if (unitPriceInput && qtyPerPackInput && unitSelect && helper) {
        function updateHelper() {
          const price = parseFloat(unitPriceInput.value) || 0;
          const qty = parseFloat(qtyPerPackInput.value) || 0;
          const unit = unitSelect.value || 'unit';

          if (qty > 0) {
            const perUnit = (price / qty).toFixed(2);
            helper.textContent = 'Per ' + unit + ' price: ' + perUnit;
          } else {
            helper.textContent = '';
          }
        }

        unitPriceInput.addEventListener("input", updateHelper);
        qtyPerPackInput.addEventListener("input", updateHelper);
        unitSelect.addEventListener("change", updateHelper);

        updateHelper();
    }
</script>
<?= view('partial/footer') ?>