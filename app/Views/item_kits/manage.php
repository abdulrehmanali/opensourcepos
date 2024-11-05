<?php
/**
 * @var string $controller_name
 * @var string $table_headers
 * @var array $config
 */
?>
<?= view('partial/header') ?>

<?php
$title_info['config_title'] = 'Item Kits';
echo view('configs/config_header', $title_info);
?>

<script type="application/javascript">
$(document).ready(function()
{
	<?= view('partial/bootstrap_tables_locale') ?>

	table_support.init({
		resource: '<?= esc($controller_name) ?>',
		headers: <?= $table_headers ?>,
		pageSize: <?= $config['lines_per_page'] ?>,
		uniqueId: 'item_kit_id'
	});

	$('#generate_barcodes').click(function()
	{
		window.open(
			'index.php/item_kits/generateBarcodes/'+table_support.selected_ids().join(':'),
			'_blank' // <- This is what makes it open in a new window.
		);
	});
});

</script>

<div class="d-flex gap-2 justify-content-end">
	<button type="button" class="btn btn-primary icon-link" data-btn-submit="<?= lang('Common.submit') ?>" data-href="<?= esc("$controller_name/view") ?>" title="<?= lang(ucfirst($controller_name). '.new') ?>">
		<i class="bi-tags"></i><?= lang(ucfirst($controller_name) .".new") ?>
	</button>
</div>

<div id="toolbar">
	<div class="d-flex gap-2">
		<button type="button" class="btn btn-secondary icon-link">
			<i class="bi-trash"></i><span class="d-none d-sm-block"><?= lang('Common.delete') ?></span>
		</button>
		<button type="button" class="btn btn-secondary icon-link" data-href="<?= esc('$controller_name/generateBarcodes') ?>" title="<?= lang('Items.generate_barcodes') ?>">
			<i class="bi-upc-scan"></i><span class="d-none d-sm-block"><?= lang('Items.generate_barcodes') ?></span>
		</button>
	</div>
</div>

<div id="table_holder">
	<table id="table"></table>
</div>

<?= view('partial/footer') ?>
