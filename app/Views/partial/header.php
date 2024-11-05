<?php
/**
 * @var object $user_info
 * @var array $allowed_modules
 * @var CodeIgniter\HTTP\IncomingRequest $request
 * @var array $config
 */

use Config\Services;

$request = Services::request();

// Services::language()->setLocale('de-DE');
?>

<!doctype html>
<html lang="<?= current_language_code() ?>" data-bs-theme="<?= $config['color_mode'] ?>">

<head>
	<meta charset="utf-8">
	<base href="<?= base_url() ?>">
	<title><?= esc($config['company']) . '&nbsp;|&nbsp;' . lang('Common.powered_by') . ' OSPOS ' . esc(config('App')->application_version) ?></title>
	<?= $config['responsive_design'] == 1 ? '<meta name="viewport" content="width=device-width, initial-scale=1">' : '' ?>
	<meta name="robots" content="noindex, nofollow">
	<link rel="shortcut icon" type="image/x-icon" href="images/favicon.ico">
	<?php $theme = (empty($config['theme']) ? 'flatly' : esc($config['theme'])); ?>
	<link rel="stylesheet" type="text/css" href="resources/bootswatch/<?= "$theme" ?>/bootstrap.min.css">
	<link rel="stylesheet" href="resources/bootstrap-icons/bootstrap-icons.min.css">
	<meta name="theme-color" content="#2c3e50">

	<?php if (ENVIRONMENT == 'development' || get_cookie('debug') == 'true' || $request->getGet('debug') == 'true') : ?>
		<!-- inject:debug:css -->
		<link rel="stylesheet" href="resources/css/jquery-ui-49d1c743e3.css">
		<link rel="stylesheet" href="resources/css/bootstrap-dialog-1716ef6e7c.css">
		<link rel="stylesheet" href="resources/css/jasny-bootstrap-40bf85f3ed.css">
		<link rel="stylesheet" href="resources/css/bootstrap-datetimepicker-66374fba71.css">
		<link rel="stylesheet" href="resources/css/bootstrap-select-e0658d7d20.css">
		<link rel="stylesheet" href="resources/css/bootstrap-table-ed9d1a3360.css">
		<link rel="stylesheet" href="resources/css/bootstrap-table-sticky-header-07d65e7533.css">
		<link rel="stylesheet" href="resources/css/daterangepicker-55e1d56082.css">
		<link rel="stylesheet" href="resources/css/chartist-c19aedb81a.css">
		<link rel="stylesheet" href="resources/css/chartist-plugin-tooltip-2e0ec92e60.css">
		<link rel="stylesheet" href="resources/css/bootstrap-tagsinput-5a6d46a06c.css">
		<link rel="stylesheet" href="resources/css/bootstrap5-toggle-1eb9311fd4.css">
		<link rel="stylesheet" href="resources/css/bootstrap-019ef57791.autocomplete.css">
		<link rel="stylesheet" href="resources/css/interface-18463dfc54.css">
		<link rel="stylesheet" href="resources/css/invoice-6a526688bd.css">
		<link rel="stylesheet" href="resources/css/ospos_print-ad4fa36376.css">
		<link rel="stylesheet" href="resources/css/popupbox-df1682d394.css">
		<link rel="stylesheet" href="resources/css/receipt-c2c74c776e.css">
		<link rel="stylesheet" href="resources/css/reports-4b8616a379.css">
		<!-- endinject -->
		<!-- inject:debug:js -->
		<script src="resources/js/jquery-12e87d2f3a.js"></script>
		<script src="resources/js/jquery-4fa896f615.form.js"></script>
		<script src="resources/js/jquery-d3cc566e04.validate.js"></script>
		<script src="resources/js/jquery-ui-c0267985b7.js"></script>
		<script src="resources/js/bootstrap-4d456e4329.bundle.js"></script>
		<script src="resources/js/bootstrap-dialog-27123abb65.js"></script>
		<script src="resources/js/jasny-bootstrap-7c6d7b8adf.js"></script>
		<script src="resources/js/bootstrap-datetimepicker-25e39b7ef8.js"></script>
		<script src="resources/js/bootstrap-select-146babba2b.js"></script>
		<script src="resources/js/bootstrap-table-bdb06552ea.js"></script>
		<script src="resources/js/bootstrap-table-export-6389dc2aa5.js"></script>
		<script src="resources/js/bootstrap-table-mobile-fc655b68ab.js"></script>
		<script src="resources/js/bootstrap-table-sticky-header-cb4d83d172.js"></script>
		<script src="resources/js/moment-d65dc6d2e6.min.js"></script>
		<script src="resources/js/daterangepicker-8a5205cec2.js"></script>
		<script src="resources/js/es6-promise-855125e6f5.js"></script>
		<script src="resources/js/FileSaver-e73b1946e8.js"></script>
		<script src="resources/js/html2canvas-e1d3a8d7cd.js"></script>
		<script src="resources/js/jspdf-c7d136b15b.umd.js"></script>
		<script src="resources/js/jspdf-454f3bac35.plugin.autotable.js"></script>
		<script src="resources/js/tableExport-0df60917ca.min.js"></script>
		<script src="resources/js/chartist-8a7ecb4445.js"></script>
		<script src="resources/js/chartist-plugin-pointlabels-0a1ab6aa4e.js"></script>
		<script src="resources/js/chartist-plugin-tooltip-116cb48831.js"></script>
		<script src="resources/js/chartist-plugin-axistitle-80a1198058.js"></script>
		<script src="resources/js/chartist-plugin-barlabels-4165273742.js"></script>
		<script src="resources/js/bootstrap-notify-60872ef3b2.js"></script>
		<script src="resources/js/js-fa93e8894e.cookie.js"></script>
		<script src="resources/js/bootstrap-tagsinput-855a7c7670.js"></script>
		<script src="resources/js/bootstrap5-toggle-60983ca34d.ecmas.js"></script>
		<script src="resources/js/imgpreview-4836346e15.full.jquery.js"></script>
		<script src="resources/js/manage_tables-0845e7bc0f.js"></script>
		<script src="resources/js/nominatim-d68f7d6a04.autocomplete.js"></script>
		<!-- endinject -->
	<?php else : ?>
		<!--inject:prod:css -->
		<link rel="stylesheet" href="resources/opensourcepos-724e128a41.min.css">
		<!-- endinject -->

		<!-- Tweaks to the UI for a particular theme should drop here  -->
	<?php if ($config['theme'] != 'flatly' && file_exists($_SERVER['DOCUMENT_ROOT'] . '/public/css/' . esc($config['theme']) . '.css')) { ?>
		<link rel="stylesheet" type="text/css" href="<?= 'css/' . esc($config['theme']) . '.css' ?>"/>
	<?php } ?>
		<!-- inject:prod:js -->
		<script src="resources/jquery-2c872dbe60.min.js"></script>
		<script src="resources/opensourcepos-20d84080f9.min.js"></script>
		<!-- endinject -->
	<?php endif; ?>

	<?= view('partial/header_js') ?>
	<?= view('partial/lang_lines') ?>

</head>

<body class="d-flex flex-column">
	<header class="flex-shrink-0 small bg-secondary-subtle py-1 d-print-none">
		<div class="container-lg container-navbar d-flex flex-wrap-reverse justify-content-between align-items-center">
			<div class="flex-grow-1 d-none d-md-block ps-md-3 ps-lg-0">
				<span id="liveclock"><?= date($config['dateformat'] . ' ' . $config['timeformat']) ?></span>
			</div>
			<div class="fw-bold ps-3 ps-md-0">
				<?= esc($config['company']) ?>
			</div>
			<div class="flex-grow-1 text-end pe-3 pe-lg-0">
				<button type="button" class="btn btn-sm btn-outline-primary" onclick="removeAnimationBg()" data-bs-toggle="modal" data-bs-target="#profile-modal" title="<?= lang('Employees.change_password'); ?>">
					<?= $user_info->first_name . '&nbsp;' . $user_info->last_name; ?>
				</button>
				<?= view('home/profile'); ?>
			</div>
		</div>
	</header>

	<nav class="navbar navbar-dark navbar-expand-lg bg-primary py-0 d-print-none">
		<div class="container-lg container-navbar">
			<a class="navbar-brand py-2 pe-1 ps-3 ps-lg-0 fs-4" href="<?= site_url() ?>"><i class="bi-house-fill"></i></a>
			<button class="navbar-toggler my-2 mx-3" type="button" data-bs-toggle="collapse" data-bs-target="#navbar" aria-controls="navbar" aria-expanded="false" aria-label="Toggle navigation">
				<span class="navbar-toggler-icon"></span>
			</button>

			<div class="collapse navbar-collapse" id="navbar">
				<ul class="navbar-nav ms-0 ms-lg-auto">
					<?php foreach($allowed_modules as $module): ?>
					<li class="d-none d-lg-block nav-item ms-1 <?= $module->module_id == $request->getUri()->getSegment(1) ? 'active bg-light bg-opacity-25' : '' ?>" data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?= lang("Module.$module->module_id") ?>">
						<a class="nav-link p-2" href="<?= base_url($module->module_id) ?>">
							<img src="<?= base_url("images/menubar/$module->module_id.svg") ?>" alt="<?= lang('Common.icon') . '&nbsp;' . lang("Module.$module->module_id") ?>">
						</a>
					</li>
					<li class="d-lg-none nav-item py-1 <?= $module->module_id == $request->getUri()->getSegment(1) ? 'active bg-light bg-opacity-25' : '' ?>">
						<a class="nav-link p-0" href="<?= base_url($module->module_id) ?>">
							<img class="ps-3 pe-1 my-1" src="<?= base_url("images/menubar/$module->module_id.svg") ?>" alt="<?= lang('Common.icon') . '&nbsp;' . lang("Module.$module->module_id") ?>">
							<span class="align-middle text-light"><?= lang("Module.$module->module_id") ?></span>
						</a>
					</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</div>
	</nav>

	<main class="container-lg flex-grow-1 py-3">
