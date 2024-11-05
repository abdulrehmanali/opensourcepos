<?= view('partial/header') ?>

<?php
$info_tab = 'id="info-tab" data-bs-toggle="tab" href="#info" role="tab" aria-controls="info" title="' . lang('Config.info_configuration') . '"';
$info_title = '<i class="bi-shop pe-2"></i>' . lang('Config.info');

$general_tab = 'id="general-tab" data-bs-toggle="tab" href="#general" role="tab" aria-controls="general" title="' . lang('Config.general_configuration') . '"';
$general_title = '<i class="bi-sliders pe-2"></i>' . lang('Config.general');

$appearance_tab = 'id="appearance-tab" data-bs-toggle="tab" href="#appearance" role="tab" aria-controls="appearance" title="Appearance Configuration"'; // TODO-BS5 toevoegen aan translate
$appearance_title = '<i class="bi-eye pe-2"></i>Appearance'; // TODO-BS5 toevoegen aan translate

$locale_tab = 'id="locale-tab" data-bs-toggle="tab" href="#locale" role="tab" aria-controls="locale" title="' . lang('Config.locale_configuration') . '"';
$locale_title = '<i class="bi-translate pe-2"></i>' . lang('Config.locale');

$tax_tab = 'id="tax-tab" data-bs-toggle="tab" href="#tax" role="tab" aria-controls="tax" title="' . lang('Config.tax_configuration') . '"';
$tax_title = '<i class="bi-piggy-bank pe-2"></i>' . lang('Config.tax');

$barcode_tab = 'id="barcode-tab" data-bs-toggle="tab" href="#barcode" role="tab" aria-controls="barcode" title="' . lang('Config.barcode_configuration') . '"';
$barcode_title = '<i class="bi-upc-scan pe-2"></i>' . lang('Config.barcode');

$stock_tab = 'id="stock-tab" data-bs-toggle="tab" href="#stock" role="tab" aria-controls="stock" title="' . lang('Config.location_configuration') . '"';
$stock_title = '<i class="bi-truck pe-2"></i>' . lang('Config.location');

$receipt_tab = 'id="receipt-tab" data-bs-toggle="tab" href="#receipt" role="tab" aria-controls="receipt" title="' . lang('Config.receipt_configuration') . '"';
$receipt_title = '<i class="bi-receipt pe-2"></i>' . lang('Config.receipt');

$invoice_tab = 'id="invoice-tab" data-bs-toggle="tab" href="#invoice" role="tab" aria-controls="invoice" title="' . lang('Config.invoice_configuration') . '"';
$invoice_title = '<i class="bi-file-text pe-2"></i>' . lang('Config.invoice');

$reward_tab = 'id="reward-tab" data-bs-toggle="tab" href="#reward" role="tab" aria-controls="reward" title="' . lang('Config.reward_configuration') . '"';
$reward_title = '<i class="bi-trophy pe-2"></i>' . lang('Config.reward');

$table_tab = 'id="table-tab" data-bs-toggle="tab" href="#table" role="tab" aria-controls="table" title="' . lang('Config.table_configuration') . '"';
$table_title = '<i class="bi-cup-straw pe-2"></i>' . lang('Config.table');

$email_tab = 'id="email-tab" data-bs-toggle="tab" href="#email" role="tab" aria-controls="email" title="' . lang('Config.email_configuration') . '"';
$email_title = '<i class="bi-envelope pe-2"></i>' . lang('Config.email');

$message_tab = 'id="message-tab" data-bs-toggle="tab" href="#message" role="tab" aria-controls="message" title="' . lang('Config.message_configuration') . '"';
$message_title = '<i class="bi-chat pe-2"></i>' . lang('Config.message');

$integrations_tab = 'id="integrations-tab" data-bs-toggle="tab" href="#integrations" role="tab" aria-controls="integrations" title="' . lang('Config.integrations_configuration') . '"';
$integrations_title = '<i class="bi-gear-wide-connected pe-2"></i>' . lang('Config.integrations');

$system_tab = 'id="system-tab" data-bs-toggle="tab" href="#system" role="tab" aria-controls="system" title="' . lang('Config.system_info') . '"';
$system_title = '<i class="bi-info-circle pe-2"></i>' . lang('Config.system_info');

$license_tab = 'id="license-tab" data-bs-toggle="tab" href="#license" role="tab" aria-controls="license" title="' . lang('Config.license_configuration') . '"';
$license_title = '<i class="bi-journal-check pe-2"></i>' . lang('Config.license');
?>

<!-- Scripts only used in Configuration screen -->
<script type="text/javascript" src="resources/clipboard/clipboard.min.js"></script>

<div class="row">
	<div class="col-lg order-lg-2">
		<div class="list-group d-none d-lg-block sticky-top" id="configs-list-tab" role="tablist">
			<button class="list-group-item list-group-item-action active" <?= $info_tab; ?>><?= $info_title; ?></button>
			<button class="list-group-item list-group-item-action" <?= $general_tab; ?>><?= $general_title; ?></button>
			<button class="list-group-item list-group-item-action" <?= $appearance_tab; ?>><?= $appearance_title; ?></button>
			<button class="list-group-item list-group-item-action" <?= $locale_tab; ?>><?= $locale_title; ?></button>
			<button class="list-group-item list-group-item-action" <?= $tax_tab; ?>><?= $tax_title; ?></button>
			<button class="list-group-item list-group-item-action" <?= $barcode_tab; ?>><?= $barcode_title; ?></button>
			<button class="list-group-item list-group-item-action" <?= $stock_tab; ?>><?= $stock_title; ?></button>
			<button class="list-group-item list-group-item-action" <?= $receipt_tab; ?>><?= $receipt_title; ?></button>
			<button class="list-group-item list-group-item-action" <?= $invoice_tab; ?>><?= $invoice_title; ?></button>
			<button class="list-group-item list-group-item-action" <?= $reward_tab; ?>><?= $reward_title; ?></button>
			<button class="list-group-item list-group-item-action" <?= $table_tab; ?>><?= $table_title; ?></button>
			<button class="list-group-item list-group-item-action" <?= $email_tab; ?>><?= $email_title; ?></button>
			<button class="list-group-item list-group-item-action" <?= $message_tab; ?>><?= $message_title; ?></button>
			<button class="list-group-item list-group-item-action" <?= $integrations_tab; ?>><?= $integrations_title; ?></button>
			<button class="list-group-item list-group-item-action" <?= $system_tab; ?>><?= $system_title; ?></button>
			<button class="list-group-item list-group-item-action" <?= $license_tab; ?>><?= $license_title; ?></button>
		</div>
		<div class="nav dropdown d-lg-none mb-3">
			<button class="btn btn-primary w-100 dropdown-toggle" id="configs-dropdown" data-bs-toggle="dropdown" aria-expanded="false">Select Configuration...</button> <!-- TODO-BS5 translate -->
			<ul class="dropdown-menu w-100" aria-labelledby="configs-dropdown">
				<li><a class="dropdown-item py-2 active" <?= $info_tab; ?>><?= $info_title; ?></a></li>
				<li><a class="dropdown-item py-2" <?= $general_tab; ?>><?= $general_title; ?></a></li>
				<li><a class="dropdown-item py-2" <?= $appearance_tab; ?>><?= $appearance_title; ?></a></li>
				<li><a class="dropdown-item py-2" <?= $locale_tab; ?>><?= $locale_title; ?></a></li>
				<li><a class="dropdown-item py-2" <?= $tax_tab; ?>><?= $tax_title; ?></a></li>
				<li><a class="dropdown-item py-2" <?= $barcode_tab; ?>><?= $barcode_title; ?></a></li>
				<li><a class="dropdown-item py-2" <?= $stock_tab; ?>><?= $stock_title; ?></a></li>
				<li><a class="dropdown-item py-2" <?= $receipt_tab; ?>><?= $receipt_title; ?></a></li>
				<li><a class="dropdown-item py-2" <?= $invoice_tab; ?>><?= $invoice_title; ?></a></li>
				<li><a class="dropdown-item py-2" <?= $reward_tab; ?>><?= $reward_title; ?></a></li>
				<li><a class="dropdown-item py-2" <?= $table_tab; ?>><?= $table_title; ?></a></li>
				<li><a class="dropdown-item py-2" <?= $email_tab; ?>><?= $email_title; ?></a></li>
				<li><a class="dropdown-item py-2" <?= $message_tab; ?>><?= $message_title; ?></a></li>
				<li><a class="dropdown-item py-2" <?= $integrations_tab; ?>><?= $integrations_title; ?></a></li>
				<li><a class="dropdown-item py-2" <?= $system_tab; ?>><?= $system_title; ?></a></li>
				<li><a class="dropdown-item py-2" <?= $license_tab; ?>><?= $license_title; ?></a></li>
			</ul>
		</div>
	</div>

	<div class="col-lg-9 order-lg-1">
		<div class="tab-content" id="configs-content">
			<div class="tab-pane active" id="info" role="tabpanel" aria-labelledby="info-tab"><?= view('configs/info_config') ?></div>
			<div class="tab-pane" id="general" role="tabpanel" aria-labelledby="general-tab"><?= view('configs/general_config'); ?></div>
			<div class="tab-pane" id="appearance" role="tabpanel" aria-labelledby="appearance-tab"><?= view('configs/appearance_config'); ?></div>
			<div class="tab-pane" id="locale" role="tabpanel" aria-labelledby="locale-tab"><?= view('configs/locale_config'); ?></div>
			<div class="tab-pane" id="tax" role="tabpanel" aria-labelledby="tax-tab"><?= view('configs/tax_config'); ?></div>
			<div class="tab-pane" id="barcode" role="tabpanel" aria-labelledby="barcode-tab"><?= view('configs/barcode_config'); ?></div>
			<div class="tab-pane" id="stock" role="tabpanel" aria-labelledby="stock-tab"><?= view('configs/stock_config'); ?></div>
			<div class="tab-pane" id="receipt" role="tabpanel" aria-labelledby="receipt-tab"><?= view('configs/receipt_config'); ?></div>
			<div class="tab-pane" id="invoice" role="tabpanel" aria-labelledby="invoice-tab"><?= view('configs/invoice_config'); ?></div>
			<div class="tab-pane" id="reward" role="tabpanel" aria-labelledby="reward-tab"><?= view('configs/reward_config'); ?></div>
			<div class="tab-pane" id="table" role="tabpanel" aria-labelledby="table-tab"><?= view('configs/table_config'); ?></div>
			<div class="tab-pane" id="email" role="tabpanel" aria-labelledby="email-tab"><?= view('configs/email_config'); ?></div>
			<div class="tab-pane" id="message" role="tabpanel" aria-labelledby="message-tab"><?= view('configs/message_config'); ?></div>
			<div class="tab-pane" id="integrations" role="tabpanel" aria-labelledby="integrations-tab"><?= view('configs/integrations_config'); ?></div>
			<div class="tab-pane" id="system" role="tabpanel" aria-labelledby="system-tab"><?= view('configs/system_info'); ?></div>
			<div class="tab-pane" id="license" role="tabpanel" aria-labelledby="license-tab"><?= view('configs/license_config'); ?></div>
		</div>
	</div>
</div>

<script type="text/javascript" src="js/bs-validation.js"></script>

<?= view('partial/footer') ?>
