<?php

/**
 * @var array $definition_names
 * @var array $definition_values
 * @var int $item_id
 * @var array $config
 */
?>
<style>
  .select2-container--default .select2-selection--single {
    height: 35px;
    border-radius: 3px;
    border: 1px solid #dce4ec;
  }

  .select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 35px;
  }

  .select2-container--default .select2-selection--single .select2-selection__arrow {
    top: 5px;
  }
</style>
<?php
// Determine which definition_names are not yet present in $definition_values.
$present_norm = [];
foreach ($definition_values as $dv) {
  if (!empty($dv['definition_name'])) {
    $present_norm[strtolower(preg_replace('/\s+/', '', $dv['definition_name']))] = true;
  }
}

$remaining_options = [];
foreach ($definition_names as $k => $label) {
  $norm = strtolower(preg_replace('/\s+/', '', (string)$label));
  if (!isset($present_norm[$norm])) {
    $remaining_options[$k] = $label;
  }
}

// Only show the "add attribute" dropdown if there are attributes left to add.
if (count($remaining_options) > 0):
?>
  <div class="form-group form-group-sm">
    <?= form_label(lang('Attributes.definition_name'), 'definition_name_label', ['class' => 'control-label col-xs-3']) ?>
    <div class='col-xs-8'>
      <?= form_dropdown([
        'name' => 'definition_name',
        'options' => $remaining_options,
        'selected' => -1,
        'class' => 'form-control',
        'id' => 'definition_name'
      ]) ?>
    </div>
  </div>
<?php endif; ?>
<?php
// Reorder attributes to preferred sequence where possible. Attributes
// not listed below will be appended in their original order.
$desired_order = [
  'Brand',
  'Engine Oil',
  'API Grade',
  'Made',
  'Part Number',
  'OEM Part Number',
  'Reference Part Number',
  'Viscosity / SAE',
  'Whole Sale Price',
  'Retail Price',
  'Quantity Stock',
  'Receiving Quantity Stock',
  'Reorder Level',
  'Single Unit'
];

// Normalizer: lowercase + remove spaces for loose matching
$normalize = function($s) {
  return strtolower(preg_replace('/\s+/', '', (string)$s));
};

$remaining = $definition_values;
$ordered = [];

foreach ($desired_order as $want) {
  $want_n = $normalize($want);
  foreach ($remaining as $k => $v) {
    if (empty($v['definition_name'])) continue;
    $have_n = $normalize($v['definition_name']);

    // Exact or substring match (helps with small typos like 'Viscocity' vs 'Viscosity')
    if ($have_n === $want_n || strpos($have_n, $want_n) !== false || strpos($want_n, $have_n) !== false) {
      $ordered[$k] = $v;
      unset($remaining[$k]);
    }
  }
}

// Append any leftover attributes preserving original order
foreach ($remaining as $k => $v) {
  $ordered[$k] = $v;
}

foreach ($ordered as $definition_id => $definition_value) {
  if (!$definition_value['definition_id']) {
    continue;
  }
?>
  <div class="form-group form-group-sm">
    <?= form_label($definition_value['definition_name'], $definition_value['definition_name'], ['class' => 'control-label col-xs-3']) ?>
    <div class='col-xs-8'>
      <div class="input-group">
        <?php
        echo form_hidden("attribute_ids[$definition_id]", strval($definition_value['attribute_id']));
        $attribute_value = $definition_value['attribute_value'];
        switch ($definition_value['definition_type']) {
          case DATE:
            $value = (empty($attribute_value) || empty($attribute_value->attribute_date)) ? NOW : strtotime($attribute_value->attribute_date);
            echo form_input([
              'name' => "attribute_links[$definition_id]",
              'value' => to_date($value),
              'class' => 'form-control input-sm datetime',
              'data-definition-id' => $definition_id,
              'data-definition-label' => $definition_value['definition_name'],
              'readonly' => 'true'
            ]);
            break;
          case DROPDOWN:
            $selected_value = $definition_value['selected_value'];
            $values = ['Select '.$definition_value['definition_name'], ...$definition_value['values']];
            echo form_dropdown([
              'name' => "attribute_links[$definition_id]",
              'options' => $values,
              'selected' => $selected_value,
              'class' => 'form-control select-2-new-item-attributes',
              'data-definition-id' => $definition_id,
              'data-definition-label' => $definition_value['definition_name']
            ]);
            break;
          case TEXT:
            $value = (empty($attribute_value) || empty($attribute_value->attribute_value)) ? $definition_value['selected_value'] : $attribute_value->attribute_value;
            echo form_input([
              'name' => "attribute_links[$definition_id]",
              'value' => $value,
              'class' => 'form-control valid_chars',
              'data-definition-id' => $definition_id,
              'data-definition-label' => $definition_value['definition_name']
            ]);
            break;
          case DECIMAL:
            $value = (empty($attribute_value) || empty($attribute_value->attribute_decimal)) ? $definition_value['selected_value'] : $attribute_value->attribute_decimal;
            echo form_input([
              'name' => "attribute_links[$definition_id]",
              'value' => to_decimals((float)$value),
              'class' => 'form-control valid_chars',
              'data-definition-id' => $definition_id,
              'data-definition-label' => $definition_value['definition_name']
            ]);
            break;
          case CHECKBOX:
            $value = (empty($attribute_value) || empty($attribute_value->attribute_value)) ? $definition_value['selected_value'] : $attribute_value->attribute_value;

            //Sends 0 if the box is unchecked instead of not sending anything.
            echo form_input([
              'type' => 'hidden',
              'name' => "attribute_links[$definition_id]",
              'id' => "attribute_links[$definition_id]",
              'value' => 0,
              'data-definition-id' => $definition_id,
              'data-definition-label' => $definition_value['definition_name']
            ]);
            echo form_checkbox([
              'name' => "attribute_links[$definition_id]",
              'id' => "attribute_links[$definition_id]",
              'value' => 1,
              'checked' => $value == 1,
              'class' => 'checkbox-inline',
              'data-definition-id' => $definition_id,
              'data-definition-label' => $definition_value['definition_name']
            ]);
            break;
        }
        ?>
        <span class="input-group-addon input-sm btn btn-default remove_attribute_btn"><span class="glyphicon glyphicon-trash"></span></span>
      </div>
    </div>
  </div>

<?php
}
?>

<script type="application/javascript">
  (function() {
    <?= view('partial/datepicker_locale', ['config' => '{ minView: 2, format: "' . dateformat_bootstrap($config['dateformat'] . '"}')]) ?>

    var enable_delete = function() {
      $('.remove_attribute_btn').click(function() {
        $(this).parents('.form-group').remove();
      });
    };

    enable_delete();

    $("input[name*='attribute_links']").change(function() {
      var definition_id = $(this).data('definition-id');
      $("input[name='attribute_ids[" + definition_id + "]']").val('');
    }).autocomplete({
      source: function(request, response) {
        $.get('<?= 'attributes/suggestAttribute/' ?>' + this.element.data('definition-id') + '?term=' + request.term, function(data) {
          return response(data);
        }, 'json');
      },
      appendTo: '.modal-content',
      select: function(event, ui) {
        event.preventDefault();
        $(this).val(ui.item.label);
      },
      delay: 10
    });

    var definition_values = function() {
      var result = {};
      $("[name*='attribute_links'").each(function() {
        var definition_id = $(this).data('definition-id');
        result[definition_id] = $(this).val();
      });
      return result;
    };

    var refresh = function() {
      var definition_id = $("#definition_name option:selected").val();
      var attribute_values = definition_values();
      attribute_values[definition_id] = '';
      $('#attributes').load('<?= "items/attributes/$item_id" ?>', {
        'definition_ids': JSON.stringify(attribute_values)
      }, enable_delete);
    };

    $('#definition_name').change(function() {
      refresh();
    });

    $('.select-2-new-item-attributes').each(function() {
      const $select = $(this);

      $select.select2({
        tags: true,
        createTag: function(params) {
          var term = $.trim(params.term);
          if (term === '') return null;

          return {
            id: term, // temporary id
            text: term.replace(/\b\w/g, (l) => l.toUpperCase()),
            newTag: true
          };
        },
        insertTag: function(data, tag) {
          data.push(tag);
        }
      }).on('select2:select', function(e) {
        const data = e.params.data;
        const dataText = data.text.replace(/\b\w/g, (l) => l.toUpperCase())
        if (data.id === dataText && data.newTag) {
          $.post('attributes/saveAttributeValue/', {
            definition_id: $select.data('definition-id'),
            attribute_value: dataText
          }, function(response) {
            response = JSON.parse(response)
            // Assume response contains the real ID returned from the server
            const newId = response.id || response; // support both plain id or { id: x }

            // Replace the temporary tag with the one having the real ID
            const newOption = new Option(dataText, newId, true, true);
            $select.append(newOption).trigger('change');
            

            // Remove the temporary option
            // console.log(data.text)
            // console.log($select.find('option[value="' + data.id + '"]'))
            // $select.find('option[value="' + data.id + '"]').remove();
            $select.find('option[value="' + dataText + '"]').remove();
          });
        }
      }).on('change', function(e) {
        const $changedSelect = $(this);
        const selectedOption = $changedSelect.find("option:selected");
        if (selectedOption.length && selectedOption.attr('data-select2-tag') === "true") {
          const newTag = selectedOption.text();
          $.post('attributes/saveAttributeValue/', {
            definition_id: $changedSelect.data('definition-id'),
            attribute_value: newTag
          }, function(response) {
            response = JSON.parse(response)
            const newId = response.id || response;
            const newOption = new Option(newTag, newId, true, true);
            $changedSelect.append(newOption).trigger('change');
            $changedSelect.find('option[value="' + newTag + '"]').remove();
          });
        }
      });
    });
  })();
</script>
