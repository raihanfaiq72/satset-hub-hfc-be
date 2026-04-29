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
Route::post('/auth/update-profile', 'AuthController@updateProfile');
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
Route::get('/promo-vouchers/campaigns', 'PromoVoucherController@campaigns');
Route::get('/promo-vouchers/campaigns/{id}', 'PromoVoucherController@campaignDetail');
Route::post('/promo-vouchers/campaigns/create', 'PromoVoucherController@campaignCreate');
Route::put('/promo-vouchers/campaigns/{id}/edit', 'PromoVoucherController@campaignEdit');
Route::delete('/promo-vouchers/campaigns/{id}/delete', 'PromoVoucherController@campaignDelete');
Route::post('/promo-vouchers/claim', 'PromoVoucherController@claim');
Route::post('/promo-vouchers/transfer', 'PromoVoucherController@transfer');
Route::post('/promo-vouchers/use', 'PromoVoucherController@use');
Route::get('/promo-vouchers/user', 'PromoVoucherController@userVouchers');
Route::get('/promo-vouchers/user/history', 'PromoVoucherController@userVoucherHistory');
Route::get('/promo-vouchers/generate-image-voucher/{id}', 'PromoVoucherController@generateImageVoucher');
Route::post('/promo-vouchers/generate-receive-qr', 'PromoVoucherController@generateReceiveQR');
Route::post('/promo-vouchers/generate-redeem-qr', 'PromoVoucherController@generateRedeemQR');

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
Route::post('/payment-vouchers/generate-receive-qr', 'PaymentVoucherController@generateReceiveQR');
Route::post('/payment-vouchers/generate-redeem-qr', 'PaymentVoucherController@generateRedeemQR');

Route::post('/qr-code/generate-receive', 'QRCodeController@generateReceiveQR');
Route::post('/qr-code/scan-and-send-otp', 'QRCodeController@scanAndSendOTP');
Route::post('/qr-code/scan-and-transfer', 'QRCodeController@scanAndTransfer');
Route::post('/qr-code/check-otp-status', 'QRCodeController@checkOTPStatus');
Route::post('/qr-code/generate-redeem', 'QRCodeController@generateRedeemQR');
Route::post('/qr-code/scan-and-redeem', 'QRCodeController@scanAndRedeem');

// order
Route::post('/order', 'OrderController@orderCreate');
Route::get('/order/history', 'OrderController@orderHistory');
Route::post('/order/check-available-ranger', 'OrderController@checkAvailableRanger');
Route::get('/tracking/{id}', 'TrackingController@index');

// customer
Route::get('/get-customer-all', 'CustomerController@getAll');
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
 
// proxy
Route::get('/proxy/search', 'ProxyController@search');
Route::get('/proxy/reverse', 'ProxyController@reverse');