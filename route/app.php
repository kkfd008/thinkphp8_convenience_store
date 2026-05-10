<?php
use think\facade\Route;

Route::get('/', 'login/index');
Route::get('/login', 'login/index');
Route::post('/login/doLogin', 'login/doLogin');
Route::get('/login/doLogout', 'login/doLogout');

Route::get('/index/index', 'index/index');

Route::get('/auth/ruleList', 'auth/ruleList');
Route::post('/auth/ruleAdd', 'auth/ruleAdd');
Route::post('/auth/ruleEdit', 'auth/ruleEdit');
Route::post('/auth/ruleDelete', 'auth/ruleDelete');

Route::get('/auth/roleList', 'auth/roleList');
Route::post('/auth/roleAdd', 'auth/roleAdd');
Route::post('/auth/roleEdit', 'auth/roleEdit');
Route::post('/auth/roleDelete', 'auth/roleDelete');

Route::get('/auth/adminList', 'auth/adminList');
Route::post('/auth/adminAdd', 'auth/adminAdd');
Route::post('/auth/adminEdit', 'auth/adminEdit');
Route::post('/auth/adminDelete', 'auth/adminDelete');

Route::get('/supplier/index', 'supplier/index');
Route::post('/supplier/add', 'supplier/add');
Route::post('/supplier/edit', 'supplier/edit');
Route::post('/supplier/delete', 'supplier/delete');
Route::post('/supplier/toggleStatus', 'supplier/toggleStatus');
Route::post('/supplier/import', 'supplier/import');
Route::get('/supplier/downloadTemplate', 'supplier/downloadTemplate');
Route::get('/supplier/export', 'supplier/export');

Route::get('/goods/index', 'goods/index');
Route::post('/goods/add', 'goods/add');
Route::post('/goods/edit', 'goods/edit');
Route::post('/goods/delete', 'goods/delete');
Route::post('/goods/genBarcode', 'goods/genBarcode');
Route::post('/goods/checkBarcode', 'goods/checkBarcode');
Route::get('/goods/cateList', 'goods/cateList');
Route::post('/goods/cateAdd', 'goods/cateAdd');
Route::post('/goods/cateEdit', 'goods/cateEdit');
Route::post('/goods/cateToggle', 'goods/cateToggle');
Route::post('/goods/batchCate', 'goods/batchCate');
Route::post('/goods/import', 'goods/import');
Route::get('/goods/downloadTemplate', 'goods/downloadTemplate');
Route::get('/goods/export', 'goods/export');

Route::get('/purchase/index', 'purchase/index');
Route::get('/purchase/add', 'purchase/add');
Route::post('/purchase/doAdd', 'purchase/doAdd');
Route::get('/purchase/detail', 'purchase/detail');
Route::get('/purchase/searchGoods', 'purchase/searchGoods');
Route::post('/purchase/import', 'purchase/import');
Route::post('/purchase/importSheets', 'purchase/importSheets');
Route::get('/purchase/downloadTemplate', 'purchase/downloadTemplate');
Route::get('/purchase/export', 'purchase/export');

Route::get('/stock/index', 'stock/index');
Route::get('/stock/detail', 'stock/detail');
Route::post('/stock/updateThreshold', 'stock/updateThreshold');
Route::get('/stock/warning', 'stock/warning');
Route::get('/stock/warningExport', 'stock/warningExport');
Route::get('/stock/export', 'stock/export');

Route::get('/cashier/index', 'cashier/index');
Route::get('/cashier/searchGoods', 'cashier/searchGoods');
Route::get('/cashier/searchMember', 'cashier/searchMember');
Route::post('/cashier/doCheckout', 'cashier/doCheckout');

Route::get('/order/index', 'order/index');
Route::get('/order/detail', 'order/detail');
Route::get('/order/export', 'order/export');

Route::get('/member/cateList', 'member/cateList');
Route::post('/member/cateAdd', 'member/cateAdd');
Route::post('/member/cateEdit', 'member/cateEdit');
Route::post('/member/cateDelete', 'member/cateDelete');
Route::get('/member/index', 'member/index');
Route::post('/member/add', 'member/add');
Route::post('/member/edit', 'member/edit');
Route::post('/member/delete', 'member/delete');
Route::get('/member/recharge', 'member/recharge');
Route::post('/member/doRecharge', 'member/doRecharge');
Route::get('/member/rechargeLog', 'member/rechargeLog');
Route::get('/member/export', 'member/export');
