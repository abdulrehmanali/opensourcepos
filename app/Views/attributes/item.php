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
<div class="form-group form-group-sm">
  <?= form_label(lang('Attributes.definition_name'), 'definition_name_label', ['class' => 'control-label col-xs-3']) ?>
  <div class='col-xs-8'>
    <?= form_dropdown([
      'name' => 'definition_name',
      'options' => $definition_names,
      'selected' => -1,
      'class' => 'form-control',
      'id' => 'definition_name'
    ]) ?>
  </div>
</div>
<?php
foreach ($definition_values as $definition_id => $definition_value) {
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
              'readonly' => 'true'
            ]);
            break;
          case DROPDOWN:
            $selected_value = $definition_value['selected_value'];
            echo form_dropdown([
              'name' => "attribute_links[$definition_id]",
              'options' => $definition_value['values'],
              'selected' => $selected_value,
              'class' => 'form-control select-2-new-item-attributes',
              'data-definition-id' => $definition_id
            ]);
            break;
          case TEXT:
            $value = (empty($attribute_value) || empty($attribute_value->attribute_value)) ? $definition_value['selected_value'] : $attribute_value->attribute_value;
            echo form_input([
              'name' => "attribute_links[$definition_id]",
              'value' => $value,
              'class' => 'form-control valid_chars',
              'data-definition-id' => $definition_id
            ]);
            break;
          case DECIMAL:
            $value = (empty($attribute_value) || empty($attribute_value->attribute_decimal)) ? $definition_value['selected_value'] : $attribute_value->attribute_decimal;
            echo form_input([
              'name' => "attribute_links[$definition_id]",
              'value' => to_decimals((float)$value),
              'class' => 'form-control valid_chars',
              'data-definition-id' => $definition_id
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
              'data-definition-id' => $definition_id
            ]);
            echo form_checkbox([
              'name' => "attribute_links[$definition_id]",
              'id' => "attribute_links[$definition_id]",
              'value' => 1,
              'checked' => $value == 1,
              'class' => 'checkbox-inline',
              'data-definition-id' => $definition_id
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
      const $select = $(this); // 👈 capture the current element
      $select.select2({
        tags: true,
        // dropdownParent: $(".modal.bootstrap-dialog.modal-dlg").first(),
        createTag: function(params) {
          console.log('createTag')
          var term = $.trim(params.term);

          if (term === '') {
            return null;
          }

          console.log(term)

          return {
            id: term,
            text: term,
            newTag: true // add additional parameters
          }
        },
        insertTag: function(data, tag) {
          console.log('insertTag')
          console.log(data)
          // Insert the tag at the end of the results
          data.push(tag);
        }
      }).on('select2:select', function (e) {
        const data = e.params.data;
        // Check if it's a new tag (Select2 marks it like this)
        if (data.id === data.text && data.newTag) {
          $.post('attributes/saveAttributeValue/', {
            definition_id: $select.data('definition-id'),
            attribute_value: data.text
          });
        }
      });
    });
  })();
</script>
