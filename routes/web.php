<?php

Route::get('/', function() {
    return [
        'status' => 'success',
        'message' => 'Halo dunia',
        'version' => '1.0.0',
    ];
});

Route::post('/auth/register', 'AuthController@register');
Route::post('/auth/login', 'AuthController@login');
Route::post('/auth/otp-forgot-password', 'AuthController@otpForgotPassword');
Route::post('/auth/verify-otp', 'AuthController@verifyOtp');
Route::post('/auth/reset-password', 'AuthController@resetPassword');
Route::post('/auth/self-auth', 'AuthController@selfAuth');

// banner
Route::get('/banners', 'BannerController@index');

// promosi modal
Route::get('/promosi-modals', 'PromosiModalController@index');

// layanan
Route::get('/layanan', 'LayananController@index');
Route::get('/layanan/{kode}', 'LayananController@show');

// promo voucher
Route::get('/promo-vouchers', 'PromoVoucherController@index');

// payment voucher
Route::get('/payment-vouchers', 'PaymentVoucherController@index');
Route::get('/payment-vouchers/batches', 'PaymentVoucherController@batches');
Route::post('/payment-vouchers/batches/create', 'PaymentVoucherController@batchCreate');
Route::put('/payment-vouchers/batches/{id}/edit', 'PaymentVoucherController@batchEdit');
Route::delete('/payment-vouchers/batches/{id}/delete', 'PaymentVoucherController@batchDelete');
Route::delete('/payment-vouchers/batches/{id}/force-delete', 'PaymentVoucherController@batchForceDelete');
Route::post('/payment-vouchers/user-buy', 'PaymentVoucherController@userBuy');
Route::post('/payment-vouchers/transfer', 'PaymentVoucherController@transfer');
Route::post('/payment-vouchers/user-use', 'PaymentVoucherController@userUse');
Route::get('/payment-vouchers/user', 'PaymentVoucherController@userVouchers');
Route::get('/payment-vouchers/user/history', 'PaymentVoucherController@userVoucherHistory');
Route::get('/payment-vouchers/generate-image-voucher/{id}', 'PaymentVoucherController@generateImageVoucher');

// order
Route::post('/order', 'OrderController@orderCreate');
Route::get('/order/history', 'OrderController@orderHistory');
Route::post('/order/check-available-ranger', 'OrderController@checkAvailableRanger');
Route::get('/tracking/{id}', 'TrackingController@index');

// customer
Route::get('/customer', 'CustomerController@index');
Route::put('/customer/update', 'CustomerController@update');
Route::post('/customer/request-update-otp', 'CustomerController@requestUpdateOtp');
Route::post('/customer/verify-update-otp', 'CustomerController@verifyUpdateOtp');

// lokasi
Route::get('/lokasi', 'LokasiController@index');
Route::get('/lokasi/{id}', 'LokasiController@detail');
Route::post('/lokasi/create', 'LokasiController@create');
Route::put('/lokasi/{id}/edit', 'LokasiController@edit');
Route::delete('/lokasi/{id}/delete', 'LokasiController@delete');

// location
Route::get('/provinces', 'LokasiController@getProvinces');
Route::get('/regencies/{id}', 'LokasiController@getRegencies');
Route::get('/districts/{id}', 'LokasiController@getDistricts');
Route::get('/villages/{id}', 'LokasiController@getVillages');