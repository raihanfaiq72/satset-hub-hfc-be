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
Route::post('/auth/self-auth', 'AuthController@selfAuth');

// banner
Route::get('/banners', 'BannerController@index');

// promosi modal
Route::get('/promosi-modals', 'PromosiModalController@index');

// layanan
Route::get('/layanan', 'LayananController@index');
Route::get('/layanan/{id}', 'LayananController@show');

// promo voucher
Route::get('/promo-vouchers', 'PromoVoucherController@index');
Route::get('/promo-vouchers/{id}', 'PromoVoucherController@show');
Route::post('/promo-vouchers/redeem', 'PromoVoucherController@redeem');
Route::post('/promo-vouchers/create', 'PromoVoucherController@create');

// payment voucher
Route::get('/payment-vouchers', 'PaymentVoucherController@index');
Route::get('/payment-vouchers/batches', 'PaymentVoucherController@batches');
Route::post('/payment-vouchers/batches/create', 'PaymentVoucherController@batchCreate');
Route::put('/payment-vouchers/batches/{id}/edit', 'PaymentVoucherController@batchEdit');
Route::delete('/payment-vouchers/batches/{id}/delete', 'PaymentVoucherController@batchDelete');
Route::delete('/payment-vouchers/batches/{id}/force-delete', 'PaymentVoucherController@batchForceDelete');
Route::post('/payment-vouchers/user-buy', 'PaymentVoucherController@userBuy');