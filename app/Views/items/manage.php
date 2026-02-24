<?php
/**
 * @var string $controller_name
 * @var string $table_headers
 * @var array $filters
 * @var array $stock_locations
 * @var int $stock_location
 * @var array $config
 */

use App\Models\Employee;

?>
<?= view('partial/header') ?>

<style>
    tr.editing-mode {
        background-color: #f0f8ff !important;
    }
    
    td.editing-cell {
        position: relative;
        box-shadow: inset 0 0 0 2px #ffc107;
    }
    
    td.editing-cell input.form-control {
        border: 1px solid #fFC107;
        box-shadow: 0 0 5px rgba(255, 193, 7, 0.3);
    }
    
    td.editing-cell::before {
        content: '✎';
        position: absolute;
        right: 5px;
        top: 5px;
        color: #ffc107;
        font-size: 12px;
        font-weight: bold;
    }
</style>

<?php if (session()->has('error')): ?>
    <div class="alert alert-danger"><?= session('error') ?></div>
<?php elseif (session()->has('success')): ?>
    <div class="alert alert-success"><?= session('success') ?></div>
<?php endif; ?>

<script type="application/javascript">
$(document).ready(function()
{
    var editing_mode = false;

    $('#generate_barcodes').click(function()
    {
        window.open(
            'index.php/items/generateBarcodes/'+table_support.selected_ids().join(':'),
            '_blank'
        );
    });

    $('#bulk_edit').click(function(e)
    {
        e.preventDefault();
        if (!editing_mode) {
            var selected_ids = table_support.selected_ids();
            if (selected_ids.length === 0) {
                alert('Please select at least one item to edit');
                return;
            }
            enable_inline_editing(selected_ids);
            editing_mode = true;
        }
    });

    $('#save_edits').click(function()
    {
        if (editing_mode) {
            save_inline_edits();
        }
    });

    $('#cancel_edits').click(function()
    {
        if (editing_mode) {
            disable_inline_editing();
        }
    });

    function enable_inline_editing(selected_ids) {
        var table = $('#table').bootstrapTable('getData');
        
        // Define which fields are editable based on the dropdown column menu
        // Fields from your column dropdown that make sense to bulk edit:
        var editableFields = [
            'item_number',      // Barcode
            'name',             // Item Name
            'category',         // Category
            'company_name',     // Company Name
            'cost_price',       // Wholesale Price
            'unit_price',       // Retail Price
            'quantity',         // Quantity
            'tax_percents',     // Tax Percent(s)
            '1', '3', '4', '5', '6', '7', '9', '10', '12'  // Custom attributes
        ];
        
        table.forEach(function(row) {
            if (selected_ids.includes(row['items.item_id'])) {
                var $row = $('tr[data-uniqueid="' + row['items.item_id'] + '"]');
                $row.addClass('editing-mode');
                
                // Get table columns configuration
                var colHeaders = $('#table').bootstrapTable('getOptions').columns[0];
                var $cells = $row.find('td');
                
                // Iterate through each cell and make editable ones into inputs
                $cells.each(function(cellIndex) {
                    var $cell = $(this);
                    var cellText = $cell.text().trim();
                    
                    // Skip empty cells and action cells (first 5 columns with icons)
                    if (cellIndex < 5 || cellText === '') {
                        return;
                    }
                    
                    // Get the corresponding header
                    var headerIndex = cellIndex - 1;
                    var fieldName = (headerIndex < colHeaders.length) ? colHeaders[headerIndex].field : null;
                    
                    // Check if this field should be editable
                    if (fieldName && editableFields.includes(fieldName)) {
                        // Store original value
                        $cell.data('original-value', cellText);
                        
                        // Determine input type based on field name
                        var inputType = 'text';
                        if (fieldName === 'cost_price' || fieldName === 'unit_price' || fieldName === 'quantity') {
                            inputType = 'number';
                        }
                        
                        var inputStep = (inputType === 'number' && (fieldName === 'cost_price' || fieldName === 'unit_price')) ? ' step="0.01"' : '';
                        var inputMin = (inputType === 'number') ? ' min="0"' : '';
                        
                        // Create input with visual indicator
                        var inputHtml = '<input type="' + inputType + '" class="form-control input-sm editable-field" name="' + fieldName + '" value="' + cellText.replace(/"/g, '&quot;') + '" data-field="' + fieldName + '"' + inputStep + inputMin + '>';
                        
                        $cell.html(inputHtml);
                        $cell.addClass('editing-cell');
                        
                        // Add change detection listener
                        var $input = $cell.find('.editable-field');
                        $input.on('input change', function() {
                            var currentValue = $(this).val();
                            var originalValue = $cell.data('original-value');
                            
                            if (currentValue !== originalValue) {
                                // User made a change - highlight the cell
                                $cell.css({
                                    'background-color': '#fff3cd',
                                    'border': '2px solid #ffc107',
                                    'padding': '2px'
                                });
                            } else {
                                // Value reverted to original - remove highlight
                                $cell.css({
                                    'background-color': '',
                                    'border': '',
                                    'padding': ''
                                });
                            }
                        });
                    }
                });
            }
        });
        
        // Show save/cancel buttons and hide bulk edit
        $('#bulk_edit').hide();
        $('#save_edits').show();
        $('#cancel_edits').show();
    }

    function save_inline_edits() {
        var edits = [];
        var item_ids = [];
        
        $('.editing-mode').each(function() {
            var $row = $(this);
            var item_id = $row.data('uniqueid');
            item_ids.push(item_id);
            
            var editData = { item_id: item_id };
            
            // Collect all editable fields
            $row.find('.editable-field').each(function() {
                var $input = $(this);
                var fieldName = $input.attr('name');
                editData[fieldName] = $input.val();
            });
            
            edits.push(editData);
        });
        
        if (edits.length === 0) {
            $.notify('No items to save', {type: 'info'});
            return;
        }
        
        // Prepare data - apply same values to all selected items
        var post_data = { item_ids: item_ids };
        
        // Apply the first edited item's values to all (bulk update)
        if (edits.length > 0) {
            var firstEdit = edits[0];
            ['name', 'category', 'cost_price', 'unit_price'].forEach(function(field) {
                if (firstEdit[field]) {
                    post_data[field] = firstEdit[field];
                }
            });
        }
        
        $.ajax({
            url: '<?= esc($controller_name) ?>/bulkUpdate',
            type: 'POST',
            data: post_data,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $.notify('Items updated successfully', {type: 'success'});
                    disable_inline_editing();
                    table_support.refresh();
                } else {
                    $.notify(response.message || 'Error updating items', {type: 'danger'});
                }
            },
            error: function() {
                $.notify('Error saving changes', {type: 'danger'});
            }
        });
    }

    function disable_inline_editing() {
        $('.editing-mode').each(function() {
            var $row = $(this);
            $row.find('.editing-cell').each(function() {
                var $cell = $(this);
                var $input = $cell.find('.editable-field');
                var originalValue = $cell.data('original-value');
                
                $cell.html(originalValue);
                $cell.removeClass('editing-cell').css({
                    'background-color': '',
                    'border': '',
                    'padding': ''
                });
            });
            $row.removeClass('editing-mode');
        });
        
        editing_mode = false;
        $('#bulk_edit').show();
        $('#save_edits').hide();
        $('#cancel_edits').hide();
    }

    // when any filter is clicked and the dropdown window is closed
    $('#filters').on('hidden.bs.select', function(e)
    {
        table_support.refresh();
    });

    // load the preset daterange picker
    <?= view('partial/daterangepicker') ?>
    // set the beginning of time as starting date
    $('#daterangepicker').data('daterangepicker').setStartDate("<?= date($config['dateformat'], mktime(0,0,0,01,01,2010)) ?>");
    // update the hidden inputs with the selected dates before submitting the search data
    var start_date = "<?= date('Y-m-d', mktime(0,0,0,01,01,2010)) ?>";
    $("#daterangepicker").on('apply.daterangepicker', function(ev, picker) {
        table_support.refresh();
    });

    $("#stock_location").change(function() {
       table_support.refresh();
    });

    <?php
        echo view('partial/bootstrap_tables_locale');
        $employee = model(Employee::class);
    ?>

    table_support.init({
        employee_id: <?= $employee->get_logged_in_employee_info()->person_id ?>,
        resource: '<?= esc($controller_name) ?>',
        headers: <?= $table_headers ?>,
        pageSize: <?= $config['lines_per_page'] ?>,
        uniqueId: 'items.item_id',
        queryParams: function() {
            return $.extend(arguments[0], {
                "start_date": start_date,
                "end_date": end_date,
                "stock_location": $("#stock_location").val(),
                "filters": $("#filters").val()
            });
        },
        onLoadSuccess: function(response) {
            $('a.rollover').imgPreview({
                imgCSS: { width: 200 },
                distanceFromCursor: { top:10, left:-210 }
            })
        }
    });
});
</script>
<div id="title_bar" class="btn-toolbar print_hide">
    <button class='btn btn-info btn-sm pull-right modal-dlg' data-btn-submit='<?= lang('Common.submit') ?>' data-href='<?= "$controller_name/csvImport" ?>'
            title='<?= lang('Items.import_items_csv') ?>'>
        <span class="glyphicon glyphicon-import">&nbsp;</span><?= lang('Common.import_csv') ?>
    </button>

    <a class='btn btn-info btn-sm pull-right' href='<?= "$controller_name/view" ?>'
            title='<?= lang(ucfirst($controller_name) .".new") ?>'>
        <span class="glyphicon glyphicon-tag">&nbsp;</span><?= lang(ucfirst($controller_name) .".new") ?>
    </a>
</div>

<div id="toolbar">
    <div class="pull-left form-inline" role="toolbar">
        <button id="bulk_edit" class="btn btn-default btn-sm print_hide" title='<?= lang('Items.edit_multiple_items') ?>'>
            <span class="glyphicon glyphicon-edit">&nbsp;</span><?= lang('Items.bulk_edit') ?>
        </button>
        <button id="save_edits" class="btn btn-success btn-sm print_hide" style="display: none;" title='Save changes to database'>
            <span class="glyphicon glyphicon-ok">&nbsp;</span>Save Edits
        </button>
        <button id="cancel_edits" class="btn btn-warning btn-sm print_hide" style="display: none;" title='Cancel and discard edits'>
            <span class="glyphicon glyphicon-remove">&nbsp;</span>Cancel
        </button>
        <button id="generate_barcodes" class="btn btn-default btn-sm print_hide" data-href='<?= "$controller_name/generateBarcodes" ?>' title='<?= lang('Items.generate_barcodes') ?>'>
            <span class="glyphicon glyphicon-barcode">&nbsp;</span><?= lang('Items.generate_barcodes') ?>
        </button>
        <?= form_input (['name' => 'daterangepicker', 'class' => 'form-control input-sm', 'id' => 'daterangepicker']) ?>
        <?= form_multiselect(
            'filters[]',
            $filters,
            [''],
            [
                'id' => 'filters',
                'class' => 'selectpicker show-menu-arrow',
                'data-none-selected-text' => lang('Common.none_selected_text'),
                'data-selected-text-format' => 'count > 1',
                'data-style' => 'btn-default btn-sm',
                'data-width' => 'fit'
            ]) ?>
        <?php
        if (count($stock_locations) > 1)
        {
            echo form_dropdown(
            'stock_location',
                $stock_locations,
                $stock_location,
                [
                    'id' => 'stock_location',
                    'class' => 'selectpicker show-menu-arrow',
                    'data-style' => 'btn-default btn-sm',
                    'data-width' => 'fit'
                ]
            );
        }
        ?>
    </div>
</div>

<div id="table_holder">
    <table id="table"></table>
</div>

<?= view('partial/footer') ?>
