<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->setDefaultController('Login');

$routes->get('/', 'Login::index');
$routes->get('login', 'Login::index');
$routes->post('login', 'Login::index');

$routes->add('no_access/index/(:segment)', 'No_access::index/$1');
$routes->add('no_access/index/(:segment)/(:segment)', 'No_access::index/$1/$2');

$routes->add('reports/summary_(:any)/(:any)/(:any)', 'Reports::Summary_$1/$2/$3/$4');
$routes->add('reports/summary_expenses_categories', 'Reports::date_input_only');
$routes->add('reports/summary_payments', 'Reports::date_input_only');
$routes->add('reports/summary_discounts', 'Reports::summary_discounts_input');
$routes->add('reports/summary_(:any)', 'Reports::date_input');

$routes->add('reports/graphical_(:any)/(:any)/(:any)', 'Reports::Graphical_$1/$2/$3/$4');
$routes->add('reports/graphical_summary_expenses_categories', 'Reports::date_input_only');
$routes->add('reports/graphical_summary_discounts', 'Reports::summary_discounts_input');
$routes->add('reports/graphical_(:any)', 'Reports::date_input');

$routes->add('reports/inventory_(:any)/(:any)', 'Reports::Inventory_$1/$2');
$routes->add('reports/inventory_low', 'Reports::inventory_low');
$routes->add('reports/inventory_summary', 'Reports::inventory_summary_input');
$routes->add('reports/inventory_summary/(:any)/(:any)/(:any)', 'Reports::inventory_summary/$1/$2/$3');

$routes->add('reports/detailed_(:any)/(:any)/(:any)/(:any)', 'Reports::Detailed_$1/$2/$3/$4');
$routes->add('reports/detailed_sales', 'Reports::date_input_sales');
$routes->add('reports/detailed_receivings', 'Reports::date_input_recv');

$routes->add('reports/specific_(:any)/(:any)/(:any)/(:any)', 'Reports::Specific_$1/$2/$3/$4');
$routes->add('reports/specific_customers', 'Reports::specific_customer_input');
$routes->add('reports/specific_employees', 'Reports::specific_employee_input');
$routes->add('reports/specific_discounts', 'Reports::specific_discount_input');
$routes->add('reports/specific_suppliers', 'Reports::specific_supplier_input');

// Customer routes
$routes->get('customers', 'Customers::getIndex');
$routes->get('customers/view', 'Customers::getView');
$routes->get('customers/view/(:num)', 'Customers::getView/$1');
$routes->get('customers/(:num)', 'Customers::getView/$1');
$routes->post('customers/save/(:num)', 'Customers::postSave/$1');
$routes->get('customers/save', 'Customers::postSave');
$routes->post('customers/save', 'Customers::postSave');
$routes->get('customers/row/(:num)', 'Customers::getRow/$1');
$routes->get('customers/search', 'Customers::getSearch');
$routes->post('customers/search', 'Customers::getSearch');
$routes->post('customers/check_email', 'Customers::postCheckEmail');
$routes->post('customers/check_account_number', 'Customers::postCheckAccountNumber');
$routes->post('customers/delete', 'Customers::postDelete');
$routes->get('customers/csv', 'Customers::getCsv');
$routes->get('customers/csv_import', 'Customers::getCsvImport');
$routes->post('customers/import_csv', 'Customers::postImportCsvFile');

// Add these routes for the new customer methods
$routes->get('customers/byPhoneNumber', 'Customers::byPhoneNumber');
$routes->get('customers/byPhoneNumberOrCreateCustomer', 'Customers::byPhoneNumberOrCreateCustomer');
$routes->get('customers/customerById', 'Customers::customerById');
$routes->get('customers/suggest', 'Customers::getSuggest');

// Vehicle routes
$routes->get('vehicles/suggest', 'Vehicles::suggest');
$routes->get('vehicles/getByVehicleNo', 'Vehicles::getByVehicleNo');
$routes->post('vehicles/save', 'Vehicles::save');
$routes->get('vehicles/getOrCreateByVehicleNo', 'Vehicles::getOrCreateByVehicleNo');

// $routes->get('sales/customerSalesHistory', 'Sales::getCustomerSalesHistory');
