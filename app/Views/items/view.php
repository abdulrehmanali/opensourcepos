<?php

/**
 * @var object $item_info
 * @var array $categories
 * @var bool $include_hsn
 * @var array $stock_locations
 * @var bool $logo_exists
 * @var string $image_path
 * @var string $pdf_path
 * @var array $item_media_images
 * @var array $item_media_pdfs
 * @var array $definition_values
 * @var array $definition_names
 * @var array $item_tax_info
 * @var int|null $previous_item_id
 * @var int|null $next_item_id
 */
?>
<?= view('partial/header') ?>
<style>
  .item-detail-section {
    background-color: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 15px;
    margin-bottom: 20px;
  }

  .detail-label {
    font-weight: bold;
    color: #333;
    margin-bottom: 5px;
  }

  .detail-value {
    color: #666;
    margin-bottom: 15px;
    padding-left: 10px;
    border-left: 3px solid #0275d8;
  }

  .image-preview {
    max-width: 200px;
    max-height: 200px;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 5px;
    cursor: pointer;
    transition: opacity 0.2s ease;
  }

  .image-preview:hover {
    opacity: 0.8;
  }

  .back-button {
    margin-bottom: 20px;
  }

  .section-title {
    font-size: 18px;
    font-weight: bold;
    color: #333;
    border-bottom: 2px solid #0275d8;
    padding-bottom: 10px;
    margin-top: 20px;
    margin-bottom: 15px;
  }

  .grid-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 15px;
  }

  @media (max-width: 768px) {
    .grid-row {
      grid-template-columns: 1fr;
    }
  }
</style>

<div class="container">
  <!-- Item Navigation Buttons -->
  <nav class="navbar navbar-default" style="margin-bottom: 20px;">
    <ul class="nav navbar-nav" style="width: 100%; display: flex; justify-content: center; gap: 10px; padding: 10px;">
      <li>
        <button type="button" class='btn btn-default btn-sm' id='items_view_back_button' title="Previous Item">
          <span class="glyphicon glyphicon-chevron-left">&nbsp</span>Back
        </button>
      </li>
      <li>
        <button type="button" class='btn btn-primary btn-sm' id='items_view_new_button' title="New Item">
          <span class="glyphicon glyphicon-plus">&nbsp</span>New
        </button>
      </li>
      <li>
        <button type="button" class='btn btn-default btn-sm' id='items_view_next_button' title="Next Item">
          <span class="glyphicon glyphicon-chevron-right">&nbsp</span>Next
        </button>
      </li>
    </ul>
  </nav>

  <h1><?= esc($item_info->name) ?></h1>

  <div class="row">
    <div class="col-md-8">
      <!-- Basic Information -->
      <div class="item-detail-section">
        <div class="section-title">Basic Information</div>

        <div class="grid-row">
          <div>
            <div class="detail-label">Item ID</div>
            <div class="detail-value"><?= esc($item_info->item_id) ?></div>
          </div>
          <div>
            <div class="detail-label">Item Number (Barcode)</div>
            <div class="detail-value"><?= !empty($item_info->item_number) ? esc($item_info->item_number) : '<em>Not set</em>' ?></div>
          </div>
        </div>

        <div class="grid-row">
          <div>
            <div class="detail-label">Item Name</div>
            <div class="detail-value"><?= esc($item_info->name) ?></div>
          </div>
          <div>
            <div class="detail-label">Item Type</div>
            <div class="detail-value">
              <?php
              switch ($item_info->item_type) {
                case ITEM:
                  echo 'Standard Item';
                  break;
                case ITEM_KIT:
                  echo 'Item Kit';
                  break;
                case ITEM_AMOUNT_ENTRY:
                  echo 'Amount Entry';
                  break;
                case ITEM_TEMP:
                  echo 'Temporary Item';
                  break;
                default:
                  echo 'Unknown';
              }
              ?>
            </div>
          </div>
        </div>

        <div class="grid-row">
          <div>
            <div class="detail-label">Category</div>
            <div class="detail-value">
              <?php if (!empty($categories)): ?>
                <?= esc(implode(', ', $categories)) ?>
              <?php else: ?>
                <em>No category assigned</em>
              <?php endif; ?>
            </div>
          </div>
          <div>
            <div class="detail-label">Stock Type</div>
            <div class="detail-value">
              <?= $item_info->stock_type == HAS_STOCK ? 'Has Stock' : 'No Stock' ?>
            </div>
          </div>
        </div>

        <div>
          <div class="detail-label">Description</div>
          <div class="detail-value">
            <?= !empty($item_info->description) ? esc($item_info->description) : '<em>No description</em>' ?>
          </div>
        </div>
      </div>

      <!-- Pricing Information -->
      <div class="item-detail-section">
        <div class="section-title">Pricing Information</div>

        <div class="grid-row">
          <div>
            <div class="detail-label">Cost Price</div>
            <div class="detail-value"><?= to_currency($item_info->cost_price) ?></div>
          </div>
          <div>
            <div class="detail-label">Unit Price</div>
            <div class="detail-value"><?= to_currency($item_info->unit_price) ?></div>
          </div>
        </div>
      </div>

      <!-- Stock Information -->
      <div class="item-detail-section">
        <div class="section-title">Stock Information</div>

        <div class="grid-row">
          <div>
            <div class="detail-label">Receiving Quantity</div>
            <div class="detail-value"><?= to_quantity_decimals($item_info->receiving_quantity) ?></div>
          </div>
          <div>
            <div class="detail-label">Reorder Level</div>
            <div class="detail-value"><?= to_quantity_decimals($item_info->reorder_level) ?></div>
          </div>
        </div>

        <div>
          <div class="detail-label">Stock Locations & Quantities</div>
          <div class="detail-value">
            <?php if (!empty($stock_locations)): ?>
              <table class="table table-striped table-sm">
                <thead>
                  <tr>
                    <th>Location</th>
                    <th>Quantity</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($stock_locations as $location_id => $location): ?>
                    <tr>
                      <td><?= esc($location['location_name']) ?></td>
                      <td><?= to_quantity_decimals($location['quantity']) ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            <?php else: ?>
              <em>No stock locations configured</em>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Tax Information -->
      <?php if (!empty($item_tax_info)): ?>
        <div class="item-detail-section">
          <div class="section-title">Tax Information</div>

          <table class="table table-striped table-sm">
            <thead>
              <tr>
                <th>Tax Name</th>
                <th>Tax Percentage</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($item_tax_info as $tax): ?>
                <tr>
                  <td><?= esc($tax['name']) ?></td>
                  <td><?= to_tax_decimals($tax['percent']) ?>%</td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>

      <!-- Supplier Information -->
      <?php if (!empty($item_info->supplier_id)): ?>
        <div class="item-detail-section">
          <div class="section-title">Supplier Information</div>

          <div>
            <div class="detail-label">Supplier ID</div>
            <div class="detail-value"><?= esc($item_info->supplier_id) ?></div>
          </div>
        </div>
      <?php endif; ?>

      <!-- Additional Settings -->
      <div class="item-detail-section">
        <div class="section-title">Additional Settings</div>

        <div class="grid-row">
          <div>
            <div class="detail-label">Allow Alt Description</div>
            <div class="detail-value">
              <?= $item_info->allow_alt_description == 1 ? '<span class="label label-success">Yes</span>' : '<span class="label label-danger">No</span>' ?>
            </div>
          </div>
          <div>
            <div class="detail-label">Is Serialized</div>
            <div class="detail-value">
              <?= $item_info->is_serialized == 1 ? '<span class="label label-success">Yes</span>' : '<span class="label label-danger">No</span>' ?>
            </div>
          </div>
        </div>

        <div class="grid-row">
          <div>
            <div class="detail-label">Quantity Per Pack</div>
            <div class="detail-value"><?= to_quantity_decimals($item_info->qty_per_pack) ?></div>
          </div>
          <div>
            <div class="detail-label">Pack Name</div>
            <div class="detail-value"><?= esc($item_info->pack_name) ?></div>
          </div>
        </div>

        <?php if ($include_hsn): ?>
          <div>
            <div class="detail-label">HSN Code</div>
            <div class="detail-value">
              <?= !empty($item_info->hsn_code) ? esc($item_info->hsn_code) : '<em>Not set</em>' ?>
            </div>
          </div>
        <?php endif; ?>

        <div class="grid-row">
          <div>
            <div class="detail-label">Status</div>
            <div class="detail-value">
              <?= $item_info->deleted == 1 ? '<span class="label label-danger">Deleted</span>' : '<span class="label label-success">Active</span>' ?>
            </div>
          </div>
        </div>
      </div>

      <!-- Attributes -->
      <?php if (!empty($definition_names)): ?>
        <div class="item-detail-section">
          <div class="section-title">Attributes</div>

          <table class="table table-striped table-sm">
            <thead>
              <tr>
                <th>Attribute</th>
                <th>Value</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($definition_names as $definition_id => $definition_name): ?>
                <tr>
                  <td><?= esc($definition_name) ?></td>
                  <td>
                    <?php
                    if (isset($definition_values[$definition_id])) {
                      $definition = $definition_values[$definition_id];
                      if (isset($definition['attribute_value'])) {
                        if (is_object($definition['attribute_value']) && isset($definition['attribute_value']->attribute_value)) {
                          echo esc($definition['attribute_value']->attribute_value);
                        } elseif (is_array($definition['attribute_value'])) {
                          echo esc(implode(', ', $definition['attribute_value']));
                        } else {
                          echo esc($definition['attribute_value'] ?? '');
                        }
                      } else {
                        echo '<em>Not set</em>';
                      }
                    } else {
                      echo '<em>Not set</em>';
                    }
                    ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

    <!-- Right Column: Images and PDFs -->
    <div class="col-md-4">
      <!-- Item Image -->
      <div class="item-detail-section">
        <div class="section-title">Item Image</div>

        <?php if ($logo_exists && !empty($image_path)): ?>
          <img src="<?= $image_path ?>" alt="<?= esc($item_info->name) ?>" class="image-preview" data-toggle="modal" data-target="#imageModal">
          <p class="text-muted text-center" style="margin-top: 10px;">
            <small>Click to enlarge</small>
          </p>
        <?php else: ?>
          <div class="alert alert-info">
            <em>No image available</em>
          </div>
        <?php endif; ?>
      </div>

      <!-- Item PDF -->
      <div class="item-detail-section">
        <div class="section-title">Item PDF</div>

        <?php if (!empty($pdf_path)): ?>
          <a href="<?= $pdf_path ?>" class="btn btn-default btn-block" target="_blank">
            <span class="glyphicon glyphicon-file"></span> View PDF
          </a>
        <?php else: ?>
          <div class="alert alert-info">
            <em>No PDF available</em>
          </div>
        <?php endif; ?>
      </div>

      <!-- Additional Images Gallery -->
      <?php if (!empty($item_media_images)): ?>
        <div class="item-detail-section">
          <div class="section-title">Additional Images</div>
          <div style="display:flex; flex-wrap:wrap; gap:8px;">
            <?php foreach ($item_media_images as $media): ?>
              <div style="text-align:center;">
                <a href="<?= base_url('uploads/item_pics/' . esc($media['filename'])) ?>" target="_blank">
                  <img src="<?= base_url('uploads/item_pics/' . esc($media['filename'])) ?>"
                       alt="<?= esc($media['original_name']) ?>"
                       class="image-preview"
                       style="max-width:100px; max-height:100px;">
                </a>
                <div style="font-size:11px; color:#888; max-width:100px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="<?= esc($media['original_name']) ?>">
                  <?= esc($media['original_name']) ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <!-- Additional PDFs -->
      <?php if (!empty($item_media_pdfs)): ?>
        <div class="item-detail-section">
          <div class="section-title">Additional PDFs</div>
          <?php foreach ($item_media_pdfs as $media): ?>
            <div style="margin-bottom:5px;">
              <a href="<?= base_url('uploads/item_pdf/' . esc($media['filename'])) ?>" class="btn btn-default btn-sm" target="_blank">
                <span class="glyphicon glyphicon-file"></span> <?= esc($media['original_name'] ?: $media['filename']) ?>
              </a>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif;?>

      <!-- Action Buttons -->
      <div class="item-detail-section">
        <div class="section-title">Actions</div>

        <a href="<?= site_url('items/edit/' . $item_info->item_id) ?>" class="btn btn-primary btn-block">
          <span class="glyphicon glyphicon-pencil"></span> Edit Item
        </a>
        <a href="<?= site_url('items/duplicate/' . $item_info->item_id) ?>" class="btn btn-success btn-block" style="margin-top: 10px;">
          <span class="glyphicon glyphicon-copy"></span> Duplicate Item
        </a>
        <a href="<?= site_url('items/inventory/' . $item_info->item_id) ?>" class="btn btn-info btn-block" style="margin-top: 10px;">
          <span class="glyphicon glyphicon-pushpin"></span> Update Inventory
        </a>
      </div>
    </div>
  </div>
</div>

<!-- Image Preview Modal -->
<div id="imageModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="imageModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
        <h4 class="modal-title" id="imageModalLabel"><?= esc($item_info->name) ?></h4>
      </div>
      <div class="modal-body text-center">
        <img src="<?= $image_path ?>" alt="<?= esc($item_info->name) ?>" style="max-width: 100%; max-height: 80vh;">
      </div>
    </div>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    const BASE_URL = '<?= base_url() ?>';
    const controller_name = 'items';
    const previousItemId = <?= json_encode($previous_item_id) ?>;
    const nextItemId = <?= json_encode($next_item_id) ?>;

    // Initialize button states based on passed variables
    function initializeButtonStates() {
      // Disable back button if no previous item
      if (previousItemId === null) {
        document.getElementById('items_view_back_button').disabled = true;
        document.getElementById('items_view_back_button').style.opacity = '0.5';
      }

      // Disable next button if no next item
      if (nextItemId === null) {
        document.getElementById('items_view_next_button').disabled = true;
        document.getElementById('items_view_next_button').style.opacity = '0.5';
      }
    }

    // Initialize button states on page load
    initializeButtonStates();

    // Back button - navigate to previous item
    document.getElementById('items_view_back_button').addEventListener('click', function(e) {
      e.preventDefault();
      if (this.disabled || previousItemId === null) return;

      window.location.href = `${BASE_URL}/${controller_name}/view/${previousItemId}`;
    });

    // New button - create new item
    document.getElementById('items_view_new_button').addEventListener('click', function(e) {
      e.preventDefault();

      window.location.href = `${BASE_URL}/${controller_name}/edit`;
    });

    // Next button - navigate to next item
    document.getElementById('items_view_next_button').addEventListener('click', function(e) {
      e.preventDefault();
      if (this.disabled || nextItemId === null) return;

      window.location.href = `${BASE_URL}/${controller_name}/view/${nextItemId}`;
    });
  });
</script>

<?= view('partial/footer') ?>
