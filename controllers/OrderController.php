<?php

class OrderController extends BaseController {
    private $auth;

    public function __construct() {
        $this->auth = new AuthMiddleware();
    }

    public function orderCreate() {
        $this->auth->authenticate();
        $data = $this->getRequestData();

        $validation = $this->validateRequired($data, [
            'idCustomer',
            'idLayanan',
            'tglPekerjaan',
            'idSubLayanan',
            'idLokasi'
        ]);

        if ($validation) return $validation;

        try {

            $office = Offices::where('name', "PT SATSET SAPU JAGAT")->first();
            if (!$office) {
                return $this->notFound('Office not found');
            }

            $inquiry = new Inquiry();
            $inquiry->idCustomer = $data['idCustomer'];
            $inquiry->idLayanan = $data['idLayanan'];
            $inquiry->idSubLayanan = $data['idSubLayanan'];
            $inquiry->idLokasi = $data['idLokasi'];
            $inquiry->status = 62;
            $inquiry->tglInput = date('Y-m-d H:i:s');
            $inquiry->tglStatus = date('Y-m-d H:i:s');
            $inquiry->office_id = $office->id;
            $inquiry->tax_type = $office->tax_type;
            $inquiry->voucher_code = $data['voucher_code'] ?? 0;
            $inquiry->voucher_discount_amount = $data['voucher_discount_amount'] ?? 0;
            $inquiry->assign = 11;
            $inquiry->kodeInquiry = $this->generateInquiryCode();

            $inquiry->save();

            $order = new Order();
            $order->idCustomer = $data['idCustomer'];
            $order->idInquiry = $inquiry->id;
            $order->idLayanan = $data['idLayanan'];
            $order->idSubLayanan = $data['idSubLayanan'];
            $order->idLokasi = $data['idLokasi'];
            $order->tglPekerjaan = $data['tglPekerjaan'];
            $order->status = 62;
            $order->tglOrder = date('Y-m-d H:i:s');
            $order->tglStatus = date('Y-m-d H:i:s');
            $order->payment_method = "Non-Tunai (Transfer)";
            $order->save();

            return $this->created($order, 'Order created successfully');
        } catch (Exception $e) {
            return $this->serverError('Failed to create order: ' . $e->getMessage());
        }
    }

    private function generateInquiryCode() {
        $date = date('ymd');
        $prefix = "CUST" . $date;
        $lastInquiry = Inquiry::where('kodeInquiry', 'LIKE', $prefix . '-%')
            ->orderBy('kodeInquiry', 'DESC')
            ->first();

        if ($lastInquiry) {
            $lastCode = $lastInquiry->kodeInquiry;
            $count = (int)substr($lastCode, -3) + 1;
        } else {
            $count = 1;
        }

        return $prefix . "-" . str_pad($count, 3, '0', STR_PAD_LEFT);
    }

}