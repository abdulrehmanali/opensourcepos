<?php
/**
 * @var string $controller_name
 * @var string $table_headers
 * @var array $filters
 * @var array $selected_filters
 * @var array $config
 */
?>
<?= view('partial/header') ?>

<script type="application/javascript">
$(document).ready(function()
{
    var currentHeaders = <?= $table_headers ?>;
    var isSummaryView = true;

    // CSS for edited cells
    var style = document.createElement('style');
    style.innerHTML = `
        td.payment-type-cell.edited {
            background-color: #ffffcc !important;
        }
        td.payment-type-cell.editable-cell {
            cursor: pointer;
        }
    `;
    document.head.appendChild(style);

    // load the preset datarange picker
    <?= view('partial/daterangepicker') ?>

    function getQueryParams() {
        return {
            "search_sale_id": $("#search_sale_id").val(),
            "search_vehicle_no": $("#search_vehicle_no").val(),
            "search_item_name": $("#search_item_name").val(),
            "search_customer_name": $("#search_customer_name").val(),
            "search_customer_phone": $("#search_customer_phone").val(),
            "summary_view": $("#summary_view").is(':checked') ? 1 : 0,
            "start_date": start_date,
            "end_date": end_date,
            "filters": $("#filters").val()
        }
    }

    function buildTableHead() {
        var headers = currentHeaders;
        
        var thead = '<thead><tr>';
        // Add checkbox header for summary view
        if(isSummaryView) {
            thead += '<th style="width: 40px;"><input type="checkbox" id="select_all" class="select-all-checkbox" /></th>';
        }
        $.each(headers, function(i, header) {
            thead += '<th>' + header.title + '</th>';
        });
        thead += '</tr></thead>';
        return thead;
    }

    function buildTableBody(rows) {
        var tbody = '<tbody>';
        $.each(rows, function(i, row) {
            // Skip total row in summary view
            if(isSummaryView && row.sale_id === '-') {
                tbody += '<tr class="total-row">';
                if(isSummaryView) {
                    tbody += '<td></td>';
                }
                $.each(currentHeaders, function(j, header) {
                    var value = row[header.field] || '';
                    tbody += '<td>' + value + '</td>';
                });
                tbody += '</tr>';
                return;
            }
            
            tbody += '<tr data-sale-id="' + row.sale_id + '">';
            // Add checkbox for summary view only
            if(isSummaryView) {
                tbody += '<td><input type="checkbox" class="row-checkbox" value="' + row.sale_id + '" /></td>';
            }
            $.each(currentHeaders, function(j, header) {
                var value = row[header.field] || '';
                var cellClass = '';
                var cellAttrs = '';
                
                // Check if this is a payment_type field (payment_type_due, payment_type_cash, etc.)
                if(header.field && header.field.startsWith('payment_type_')) {
                    cellClass = 'payment-type-cell editable-cell';
                    var paymentTypeKey = header.field.replace('payment_type_', '');
                    var paymentTypeName = paymentTypeKey.split('_').map(function(word) {
                        return word.charAt(0).toUpperCase() + word.slice(1);
                    }).join(' ');
                    
                    // Find the matching payment in the payments array
                    if(row.payments && row.payments.length > 0) {
                        for(var pi = 0; pi < row.payments.length; pi++) {
                            if(row.payments[pi].payment_type === paymentTypeName) {
                                value = row.payments[pi].payment_amount;
                                break;
                            }
                        }
                    }
                    cellAttrs = 'data-sale-id="' + row.sale_id + '" data-payment-type="' + paymentTypeName + '" data-original-value="' + value + '"';
                  // if (!cellAttrs) {
                  //   cellAttrs = 'data-payment-type="' + paymentTypeName + '" data-original-value="' + value + '"';
                  // }
                }
                
                tbody += '<td class="' + cellClass + '" ' + cellAttrs + '>' + value + '</td>';
            });
            tbody += '</tr>';
        });
        tbody += '</tbody>';
        return tbody;
    }

    function loadData() {
        $.ajax({
            url: '<?= esc($controller_name) ?>/search',
            type: 'GET',
            data: getQueryParams(),
            dataType: 'json',
            success: function(response) {
                // Update headers from server response
                if(response.headers && response.headers.length > 0) {
                    currentHeaders = response.headers;
                }
                
                // Build and update table
                var table = '<table class="table table-striped table-bordered">';
                table += buildTableHead();
                table += buildTableBody(response.rows || []);
                table += '</table>';
                
                $('#table_content').html(table);
                
                // Bind checkbox events
                bindCheckboxEvents();
                
                // Update payment summary if in summary view
                if(isSummaryView && response.payment_summary) {
                    $('#payment_summary').html(response.payment_summary);
                } else {
                    $('#payment_summary').html('');
                }
            },
            error: function(xhr) {
                console.log('Error loading data:', xhr);
                $('#table_content').html('<p>Error loading data</p>');
            }
        });
    }

    function getSelectedSaleIds() {
        var selected = [];
        $('#table_content').find('input.row-checkbox:checked').each(function() {
            selected.push($(this).val());
        });
        return selected;
    }

    function bindCheckboxEvents() {
        // Select all checkbox
        $('#table_content').on('change', '#select_all', function() {
            var isChecked = $(this).is(':checked');
            $('#table_content').find('input.row-checkbox:checked').prop('checked', isChecked);
            updateBulkActionButtons();
        });

        // Individual row checkbox
        $('#table_content').on('change', 'input.row-checkbox', function() {
            var total = $('#table_content').find('input.row-checkbox').length;
            var checked = $('#table_content').find('input.row-checkbox:checked').length;
            
            // Update select all checkbox
            $('#table_content').find('#select_all').prop('checked', total === checked && total > 0);
            updateBulkActionButtons();
        });
    }

    // Payment type cell inline editing
    $(document).on('click', 'td.payment-type-cell', function() {
        var $cell = $(this);
        var currentValue = $cell.text().trim();
        
        // Prevent editing if already in edit mode
        if($cell.find('input').length > 0) return;
        
        // Store original content and create input
        var $input = $('<input type="number" step="0.01" class="form-control" style="width: 100%; padding: 4px;" />');
        $input.val(currentValue);
        
        $cell.html('').append($input);
        $input.focus();
        
        // Save on blur
        $input.on('blur', function() {
            var newValue = $(this).val();
            if(newValue !== currentValue) {
                $cell.addClass('edited');
                showPaymentSaveButton();
            }
            $cell.html(newValue);
        });
        
        // Save on Enter key
        $input.on('keypress', function(e) {
            if(e.which === 13) {
                $(this).blur();
            }
        });
    });

    function showPaymentSaveButton() {
        // Check if save button already exists
        if($('#save_payment_changes').length === 0) {
            var saveButton = '<button type="button" class="btn btn-success btn-sm" id="save_payment_changes" style="margin: 10px 0;">';
            saveButton += '<span class="glyphicon glyphicon-ok"></span> Save Payment Changes';
            saveButton += '</button>';
            
            // Insert button before the table
            $('#table_content').before(saveButton);
        }
    }

    // Save payment changes
    $(document).on('click', '#save_payment_changes', function() {
        var changes = [];
        
        // Collect all edited cells
        $('#table_content').find('td.payment-type-cell.edited').each(function() {
            var $cell = $(this);
            var saleId = $cell.data('sale-id');
            var paymentType = $cell.data('payment-type');
            var newValue = $cell.text().trim();
            var originalValue = $cell.data('original-value');
            
            if(newValue !== originalValue) {
                changes.push({
                    sale_id: saleId,
                    payment_type: paymentType,
                    payment_amount: newValue
                });
            }
        });
        
        if(changes.length === 0) {
            alert('No changes to save');
            return;
        }
        
        $.ajax({
            url: '<?= esc($controller_name) ?>/BulkUpdatePayments',
            type: 'POST',
            data: {
                changes: changes
            },
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    alert('Payment changes saved successfully');
                    // Remove edited class and save button
                    $('#table_content').find('td.payment-type-cell').removeClass('edited');
                    $('#save_payment_changes').remove();
                    // Reload data
                    loadData();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function(xhr) {
                console.log('Error:', xhr);
                alert('Error saving payment changes');
            }
        });
    });

    function updateBulkActionButtons() {
        var selectedCount = getSelectedSaleIds().length;
        if(selectedCount > 0) {
            $('#bulk_actions').show();
            $('#bulk_count').text(selectedCount);
        } else {
            $('#bulk_actions').hide();
        }
    }

    // Event listeners for filtering
    $('#filters').on('hidden.bs.select', function(e) {
        loadData();
    });

    $('#search_sale_id, #search_vehicle_no, #search_item_name, #search_customer_name, #search_customer_phone').on('keyup', function() {
        loadData();
    });

    $("#daterangepicker").on('apply.daterangepicker', function(ev, picker) {
        loadData();
    });

    // Summary view toggle
    $('#summary_view').on('change', function() {
        isSummaryView = $(this).is(':checked');
        loadData();
    });

    // Bulk action: Cancel selection
    $(document).on('click', '#bulk_cancel', function() {
        $('#table_content').find('input.row-checkbox').prop('checked', false);
        $('#table_content').find('#select_all').prop('checked', false);
        updateBulkActionButtons();
    });

    // Bulk action: Change customer
    $(document).on('click', '#bulk_edit_customer', function() {
        $('#bulk_edit_modal').modal('show');
    });

    // Confirm bulk customer change
    $(document).on('click', '#confirm_bulk_customer', function() {
        var selectedIds = getSelectedSaleIds();
        var customerId = $('#bulk_customer_id').val();
        
        if(!customerId) {
            alert('Please select a customer');
            return;
        }
        
        $.ajax({
            url: '<?= esc($controller_name) ?>/bulk_update',
            type: 'POST',
            data: {
                ids: selectedIds,
                field: 'customer_id',
                value: customerId
            },
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    alert('Customer updated successfully');
                    $('#bulk_edit_modal').modal('hide');
                    loadData();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function(xhr) {
                console.log('Error:', xhr);
                alert('Error updating customer');
            }
        });
    });

    // Bulk action: Mark as paid
    $(document).on('click', '#bulk_mark_paid', function() {
        if(!confirm('Mark selected sales as paid?')) {
            return;
        }
        
        var selectedIds = getSelectedSaleIds();
        
        $.ajax({
            url: '<?= esc($controller_name) ?>/bulk_update',
            type: 'POST',
            data: {
                ids: selectedIds,
                field: 'mark_paid',
                value: 1
            },
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    alert('Marked as paid successfully');
                    loadData();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function(xhr) {
                console.log('Error:', xhr);
                alert('Error marking as paid');
            }
        });
    });

    // Initial load
    loadData();
});
</script>

<?= view('partial/print_receipt', ['print_after_sale'=>false, 'selected_printer' => 'takings_printer']) ?>

<div id="title_bar" class="print_hide btn-toolbar">
    <button onclick="javascript:printdoc()" class='btn btn-info btn-sm pull-right'>
        <span class="glyphicon glyphicon-print">&nbsp</span><?= lang('Common.print') ?>
    </button>
    <?= anchor("sales", '<span class="glyphicon glyphicon-shopping-cart">&nbsp</span>' . lang('Sales.register'), ['class' => 'btn btn-info btn-sm pull-right', 'id' => 'show_sales_button']) ?>
</div>

<div id="toolbar">
    <div class="pull-left form-inline" role="toolbar">
        <?= form_input (['name' => 'search_sale_id', 'class' => 'form-control input-sm', 'id' => 'search_sale_id', 'placeholder' => 'Sale ID', 'style' => 'width: 100px;']) ?>
        <?= form_input (['name' => 'search_vehicle_no', 'class' => 'form-control input-sm', 'id' => 'search_vehicle_no', 'placeholder' => 'Vehicle No', 'style' => 'width: 100px;']) ?>
        <?= form_input (['name' => 'search_item_name', 'class' => 'form-control input-sm', 'id' => 'search_item_name', 'placeholder' => 'Item Name', 'style' => 'width: 120px;']) ?>
        <?= form_input (['name' => 'search_customer_name', 'class' => 'form-control input-sm', 'id' => 'search_customer_name', 'placeholder' => 'Customer Name', 'style' => 'width: 120px;']) ?>
        <?= form_input (['name' => 'search_customer_phone', 'class' => 'form-control input-sm', 'id' => 'search_customer_phone', 'placeholder' => 'Phone Number', 'style' => 'width: 120px;']) ?>
        <?= form_input (['name' => 'daterangepicker', 'class' => 'form-control input-sm', 'id' => 'daterangepicker']) ?>
        <?= form_multiselect('filters[]', $filters, $selected_filters, ['id' => 'filters', 'data-none-selected-text'=>lang('Common.none_selected_text'), 'class' => 'selectpicker show-menu-arrow', 'data-selected-text-format' => 'count > 1', 'data-style' => 'btn-default btn-sm', 'data-width' => 'fit']) ?>
        <label style="margin-left: 15px; margin-bottom: 0;">
            <input type="checkbox" id="summary_view" name="summary_view" checked />
            Summary View
        </label>
    </div>
</div>

<div id="bulk_actions" style="display: none; margin: 15px 0; padding: 10px; background-color: #f5f5f5; border: 1px solid #ddd; border-radius: 4px;">
    <span style="margin-right: 15px;"><strong><span id="bulk_count">0</span> record(s) selected</strong></span>
    <button type="button" class="btn btn-primary btn-sm" id="bulk_edit_customer">
        <span class="glyphicon glyphicon-user"></span> Change Customer
    </button>
    <button type="button" class="btn btn-primary btn-sm" id="bulk_mark_paid">
        <span class="glyphicon glyphicon-ok"></span> Mark as Paid
    </button>
    <button type="button" class="btn btn-danger btn-sm" id="bulk_cancel">
        Cancel Selection
    </button>
</div>

<div id="table_content">
    <!-- Table will be loaded here via AJAX -->
</div>

<div id="payment_summary">
</div>

<!-- Bulk Edit Customer Modal -->
<div id="bulk_edit_modal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">Change Customer</h4>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="bulk_customer_id">Select Customer:</label>
                    <select id="bulk_customer_id" class="form-control">
                        <option value="">-- Select Customer --</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirm_bulk_customer">Update</button>
            </div>
        </div>
    </div>
</div>

<?= view('partial/footer') ?>
