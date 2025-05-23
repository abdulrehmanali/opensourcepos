<?php
/**
 * @var object $user_info
 * @var array $allowed_modules
 * @var CodeIgniter\HTTP\IncomingRequest $request
 * @var array $config
 */

use Config\Services;

$request = Services::request();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="<?= $request->getLocale() ?>">
<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <base href="<?= base_url() ?>" />
    <title><?= esc($config['company']) . ' | ' . lang('Common.powered_by') . ' OSPOS ' . esc(config('App')->application_version) ?></title>
    <link rel="shortcut icon" type="image/x-icon" href="images/favicon.ico">
    <link rel="stylesheet" type="text/css" href="<?= 'resources/bootswatch/' . (empty($config['theme']) ? 'flatly' : esc($config['theme'])) . '/bootstrap.min.css' ?>"/>
    
	<?php if (ENVIRONMENT == 'development' || get_cookie('debug') == 'true' || $request->getGet('debug') == 'true') : ?>
		<!-- inject:debug:css -->
		<link rel="stylesheet" href="resources/css/jquery-ui-49d1c743e3.css">
		<link rel="stylesheet" href="resources/css/bootstrap-dialog-1716ef6e7c.css">
		<link rel="stylesheet" href="resources/css/jasny-bootstrap-40bf85f3ed.css">
		<link rel="stylesheet" href="resources/css/bootstrap-datetimepicker-66374fba71.css">
		<link rel="stylesheet" href="resources/css/bootstrap-select-66d5473b84.css">
		<link rel="stylesheet" href="resources/css/bootstrap-table-beee084f97.css">
		<link rel="stylesheet" href="resources/css/bootstrap-table-sticky-header-07d65e7533.css">
		<link rel="stylesheet" href="resources/css/daterangepicker-85523b7dfe.css">
		<link rel="stylesheet" href="resources/css/chartist-c19aedb81a.css">
		<link rel="stylesheet" href="resources/css/chartist-plugin-tooltip-2e0ec92e60.css">
		<link rel="stylesheet" href="resources/css/bootstrap-tagsinput-5a6d46a06c.css">
		<link rel="stylesheet" href="resources/css/bootstrap-toggle-e12db6c1f3.css">
		<link rel="stylesheet" href="resources/css/bootstrap-570f92bedd.autocomplete.css">
		<link rel="stylesheet" href="resources/css/invoice-cc2bb70bbd.css">
		<link rel="stylesheet" href="resources/css/ospos_print-6724bcd06d.css">
		<link rel="stylesheet" href="resources/css/ospos-367a1bc0ae.css">
		<link rel="stylesheet" href="resources/css/popupbox-57d6208379.css">
		<link rel="stylesheet" href="resources/css/receipt-994a9a6ec5.css">
		<link rel="stylesheet" href="resources/css/register-f38b8b7778.css">
		<link rel="stylesheet" href="resources/css/reports-70cb473319.css">
		<!-- endinject -->
		<!-- inject:debug:js -->
		<script src="resources/js/jquery-12e87d2f3a.js"></script>
		<script src="resources/js/jquery-4fa896f615.form.js"></script>
		<script src="resources/js/jquery-d3cc566e04.validate.js"></script>
		<script src="resources/js/jquery-ui-c0267985b7.js"></script>
		<script src="resources/js/bootstrap-894d79839f.js"></script>
		<script src="resources/js/bootstrap-dialog-27123abb65.js"></script>
		<script src="resources/js/jasny-bootstrap-7c6d7b8adf.js"></script>
		<script src="resources/js/bootstrap-datetimepicker-25e39b7ef8.js"></script>
		<script src="resources/js/bootstrap-select-b01896a67b.js"></script>
		<script src="resources/js/bootstrap-table-4c3352caf1.js"></script>
		<script src="resources/js/bootstrap-table-export-f57325d9d4.js"></script>
		<script src="resources/js/bootstrap-table-mobile-6c4f14ac24.js"></script>
		<script src="resources/js/bootstrap-table-sticky-header-46af2df131.js"></script>
		<script src="resources/js/moment-d65dc6d2e6.min.js"></script>
		<script src="resources/js/daterangepicker-048c56a690.js"></script>
		<script src="resources/js/es6-promise-855125e6f5.js"></script>
		<script src="resources/js/FileSaver-e73b1946e8.js"></script>
		<script src="resources/js/html2canvas-e1d3a8d7cd.js"></script>
		<script src="resources/js/jspdf-6eb90bf5a3.umd.js"></script>
		<script src="resources/js/jspdf-4f52bd767f.plugin.autotable.js"></script>
		<script src="resources/js/tableExport-3d506dfa61.min.js"></script>
		<script src="resources/js/chartist-8a7ecb4445.js"></script>
		<script src="resources/js/chartist-plugin-pointlabels-0a1ab6aa4e.js"></script>
		<script src="resources/js/chartist-plugin-tooltip-116cb48831.js"></script>
		<script src="resources/js/chartist-plugin-axistitle-80a1198058.js"></script>
		<script src="resources/js/chartist-plugin-barlabels-4165273742.js"></script>
		<script src="resources/js/bootstrap-notify-376bc6eb87.js"></script>
		<script src="resources/js/js-fa93e8894e.cookie.js"></script>
		<script src="resources/js/bootstrap-tagsinput-855a7c7670.js"></script>
		<script src="resources/js/bootstrap-toggle-1c7a19a049.js"></script>
		<script src="resources/js/clipboard-908af414ab.js"></script>
		<script src="resources/js/imgpreview-1db063409f.full.jquery.js"></script>
		<script src="resources/js/manage_tables-9be5a76d8e.js"></script>
		<script src="resources/js/nominatim-4e238f4a89.autocomplete.js"></script>
		<!-- endinject -->
	<?php else : ?>
		<!--inject:prod:css -->
		<link rel="stylesheet" href="resources/opensourcepos-1a078a830f.min.css">
		<!-- endinject -->

		<!-- Tweaks to the UI for a particular theme should drop here  -->
	<?php if ($config['theme'] != 'flatly' && file_exists($_SERVER['DOCUMENT_ROOT'] . '/public/css/' . esc($config['theme']) . '.css')) { ?>
		<link rel="stylesheet" type="text/css" href="<?= 'css/' . esc($config['theme']) . '.css' ?>"/>
	<?php } ?>
		<!-- inject:prod:js -->
		<script src="resources/jquery-2c872dbe60.min.js"></script>
		<script src="resources/opensourcepos-66d2329f3d.min.js"></script>
		<!-- endinject -->
	<?php endif; ?>

    <?= view('partial/header_js') ?>
    <?= view('partial/lang_lines') ?>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <style type="text/css">
        html {
            overflow: auto;
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <div class="topbar">
            <div class="container">
                <div class="navbar-left">
                    <div id="liveclock"><?= date($config['dateformat'] . ' ' . $config['timeformat']) ?></div>
                </div>

                <div class="navbar-right" style="margin:0">
                    <?= anchor("home/changePassword/$user_info->person_id", "$user_info->first_name $user_info->last_name", ['class' => 'modal-dlg', 'data-btn-submit' => lang('Common.submit'), 'title' => lang('Employees.change_password')]) ?>
                    <span>&nbsp;|&nbsp;</span>
                    <?= anchor('home/logout', lang('Login.logout')) ?>
                </div>

                <div class="navbar-center" style="text-align:center">
                    <strong><?= esc($config['company']) ?></strong>
                </div>
            </div>
        </div>

        <div class="navbar navbar-default" role="navigation">
            <div class="container">
                <div class="navbar-header">
                    <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target=".navbar-collapse">
                        <span class="sr-only">Toggle navigation</span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                    </button>

					<a class="navbar-brand hidden-sm" href="<?= site_url() ?>"><?= lang('Common.software_short') ?></a>
				</div>

                <div class="navbar-collapse collapse">
                    <ul class="nav navbar-nav navbar-right">
                        <?php foreach($allowed_modules as $module): ?>
                            <li class="<?= $module->module_id == $request->getUri()->getSegment(1) ? 'active' : '' ?>">
                                <a href="<?= base_url($module->module_id) ?>" title="<?= lang("Module.$module->module_id") ?>" class="menu-icon">
                                    <img src="<?= base_url("images/menubar/$module->module_id.svg") ?>" style="border: none;" alt="Module Icon"/><br/>
                                    <?= lang('Module.' . $module->module_id) ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
			</div>
		</div>
		<div class="container">
			<div class="row">
