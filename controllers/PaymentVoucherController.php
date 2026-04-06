<?php

class PaymentVoucherController extends BaseController {
    private $auth;

    public function __construct() {
        $this->auth = new AuthMiddleware();
    }

    public function index() {
        $this->auth->authenticate();
        try {
            $vouchers = PvVouchers::all();
            return $this->success($vouchers);
        } catch (Exception $e) {
            return $this->serverError('Failed to fetch payment vouchers: ' . $e->getMessage());
        }
    }

    public function batches() {
        $this->auth->authenticate();
        try {
            $batches = PvBatches::all();
            return $this->success($batches);
        } catch (Exception $e) {
            return $this->serverError('Failed to fetch payment voucher batches: ' . $e->getMessage());
        }
    }

    public function batchCreate() {
        $this->auth->authenticate();
        $data = $this->getRequestData();

        $validation = $this->validateRequired($data,[
            'batch_name',
            'voucher_prefix',
            'id_layanan',
            'face_value',
            'selling_price',
            'total_qty',
            'sold_qty',
            'valid_from',
            'valid_until',
            'voucher_name',
            'voucher_description',
            'voucher_icon',
            'voucher_image',
            'template_image',
            'code_pos_x',
            'code_pos_y',
            'code_font_size',
            'code_color',
            'code_rotation',
            'template_layout',
            'output_format',
            'is_active',
            'created_by'
        ]);

        if ($validation) return $validation;

        try{
            DB::beginTransaction();
            $templateLayout = $this->parseJsonField($data['template_layout']);
            if ($templateLayout === false) {
                return $this->validationError('template_layout must be a valid JSON object or array');
            }

            $batch = new PvBatches();
            $batch->batch_name = $data['batch_name'];
            $batch->voucher_prefix = $data['voucher_prefix'];
            $batch->id_layanan = $data['id_layanan'];
            $batch->face_value = $data['face_value'];
            $batch->selling_price = $data['selling_price'];
            $batch->total_qty = $data['total_qty'];
            $batch->sold_qty = $data['sold_qty'];
            $batch->valid_from = $data['valid_from'];
            $batch->valid_until = $data['valid_until'];
            $batch->voucher_name = $data['voucher_name'];
            $batch->voucher_description = $data['voucher_description'];
            $batch->voucher_icon = $data['voucher_icon'];
            $batch->voucher_image = $data['voucher_image'];
            $batch->template_image = $data['template_image'];
            $batch->code_pos_x = $data['code_pos_x'];
            $batch->code_pos_y = $data['code_pos_y'];
            $batch->code_font_size = $data['code_font_size'];
            $batch->code_color = $data['code_color'];
            $batch->code_rotation = $data['code_rotation'];
            $batch->template_layout = $templateLayout;
            $batch->output_format = $data['output_format'];
            $batch->is_active = $data['is_active'];
            $batch->created_by = $data['created_by'];
            $batch->save();

            $this->generateVoucher($batch->id,'available');

            return $this->created([
                'id' => $batch->id,
                'batch_name' => $batch->batch_name,
                'voucher_prefix' => $batch->voucher_prefix,
                'id_layanan' => $batch->id_layanan,
                'face_value' => $batch->face_value,
                'selling_price' => $batch->selling_price,
                'total_qty' => $batch->total_qty,
                'sold_qty' => $batch->sold_qty,
                'valid_from' => $batch->valid_from,
                'valid_until' => $batch->valid_until,
                'voucher_name' => $batch->voucher_name,
                'voucher_description' => $batch->voucher_description,
                'voucher_icon' => $batch->voucher_icon,
                'voucher_image' => $batch->voucher_image,
                'template_image' => $batch->template_image,
                'code_pos_x' => $batch->code_pos_x,
                'code_pos_y' => $batch->code_pos_y,
                'code_font_size' => $batch->code_font_size,
                'code_color' => $batch->code_color,
                'code_rotation' => $batch->code_rotation,
                'template_layout' => $batch->template_layout,
                'output_format' => $batch->output_format,
                'is_active' => $batch->is_active,
                'created_by' => $batch->created_by
            ],'Payment voucher batch created successfully');
            DB::commit();
        }catch (Exception $e) {
            DB::rollBack();
            return $this->serverError('Failed to create payment voucher batch: ' . $e->getMessage());
        }
    }

    // ini private fungsi untuk generate voucher code di model pv_voucher

    private function generateVoucher($batch_id, $status = 'available') {
        $this->auth->authenticate();
        $batch = PvBatches::find($batch_id);
        if (!$batch) {
            throw new Exception('Batch not found');
        }

        $remaining = $batch->total_qty - $batch->generated_qty;
        if ($remaining <= 0) {
            throw new Exception('All vouchers already generated for this batch');
        }

        $vouchersCreated = [];

        DB::beginTransaction();
        try {
            for ($i = 0; $i < $remaining; $i++) {
                $nextNumber = $batch->generated_qty + 1;

                $voucherCode = $batch->voucher_prefix . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);

                $voucher = new PvVouchers();
                $voucher->batch_id = $batch->id;
                $voucher->voucher_code = $voucherCode;
                $voucher->face_value = $batch->face_value;
                $voucher->status = $status;
                $voucher->save();

                $vouchersCreated[] = $voucher;

                $batch->generated_qty += 1;
            }

            $batch->save();
            DB::commit();

            return $vouchersCreated;

        } catch (\Exception $e) {
            DB::rollBack();
            throw new Exception('Failed to generate vouchers: ' . $e->getMessage());
        }
    }

    public function batchEdit($id) {
        $this->auth->authenticate();
        $data = $this->getRequestData();

        $validation = $this->validateRequired($data,[
            'batch_name',
            'voucher_prefix',
            'id_layanan',
            'face_value',
            'selling_price',
            'total_qty',
            'sold_qty',
            'valid_from',
            'valid_until',
            'voucher_name',
            'voucher_description',
            'voucher_icon',
            'voucher_image',
            'template_image',
            'code_pos_x',
            'code_pos_y',
            'code_font_size',
            'code_color',
            'code_rotation',
            'template_layout',
            'output_format',
            'is_active',
            'generated_qty'
        ]);

        if ($validation) return $validation;

        try{
            DB::beginTransaction();
            $batch = PvBatches::find($id);
            if(!$batch){
                return $this->notFound('Batch not found');
            }

            $batch->batch_name = $data['batch_name'];
            $batch->voucher_prefix = $data['voucher_prefix'];
            $batch->id_layanan = $data['id_layanan'];
            $batch->face_value = $data['face_value'];
            $batch->selling_price = $data['selling_price'];
            $batch->total_qty = $data['total_qty'];
            $batch->sold_qty = $data['sold_qty'];
            $batch->valid_from = $data['valid_from'];
            $batch->valid_until = $data['valid_until'];
            $batch->voucher_name = $data['voucher_name'];
            $batch->voucher_description = $data['voucher_description'];
            $batch->voucher_icon = $data['voucher_icon'];
            $batch->voucher_image = $data['voucher_image'];
            $batch->template_image = $data['template_image'];
            $batch->code_pos_x = $data['code_pos_x'];
            $batch->code_pos_y = $data['code_pos_y'];
            $batch->code_font_size = $data['code_font_size'];
            $batch->code_color = $data['code_color'];
            $batch->code_rotation = $data['code_rotation'];

            $templateLayout = $this->parseJsonField($data['template_layout']);
            if ($templateLayout === false) {
                return $this->validationError('template_layout must be a valid JSON object or array');
            }

            $batch->template_layout = $templateLayout;
            $batch->output_format = $data['output_format'];
            $batch->is_active = $data['is_active'];
            
            if($batch->save()){
                return $this->success([
                    'id' => $batch->id,
                    'batch_name' => $batch->batch_name,
                    'voucher_prefix' => $batch->voucher_prefix,
                    'id_layanan' => $batch->id_layanan,
                    'face_value' => $batch->face_value,
                    'selling_price' => $batch->selling_price,
                    'total_qty' => $batch->total_qty,
                    'sold_qty' => $batch->sold_qty,
                    'valid_from' => $batch->valid_from,
                    'valid_until' => $batch->valid_until,
                    'voucher_name' => $batch->voucher_name,
                    'voucher_description' => $batch->voucher_description,
                    'voucher_icon' => $batch->voucher_icon,
                    'voucher_image' => $batch->voucher_image,
                    'template_image' => $batch->template_image,
                    'code_pos_x' => $batch->code_pos_x,
                    'code_pos_y' => $batch->code_pos_y,
                    'code_font_size' => $batch->code_font_size,
                    'code_color' => $batch->code_color,
                    'code_rotation' => $batch->code_rotation,
                    'template_layout' => $batch->template_layout,
                    'output_format' => $batch->output_format,
                    'is_active' => $batch->is_active,
                ],'Payment voucher batch updated successfully');
            }else{
                return $this->serverError('Failed to update payment voucher batch');
            }
            DB::commit();
        }catch (Exception $e) {
            DB::rollBack();
            return $this->serverError('Failed to update payment voucher batch: ' . $e->getMessage());
        }
    }

    public function batchDelete($id) {
        $this->auth->authenticate();
        try{
            DB::beginTransaction();
            $batch = PvBatches::find($id);
            $voucher = PvVouchers::where('batch_id', $id)->first();
            if($voucher){
                return $this->serverError('Cannot delete batch with existing vouchers');
            }

            if(!$batch){
                return $this->notFound('Batch not found');
            }

            if($batch->delete()){
                return $this->success(null,'Payment voucher batch deleted successfully');
            }else{
                return $this->serverError('Failed to delete payment voucher batch');
            }
            DB::commit();
        }catch (Exception $e) {
            DB::rollBack();
            return $this->serverError('Failed to delete payment voucher batch: ' . $e->getMessage());
        }
    }

    public function batchForceDelete($id) {
        $this->auth->authenticate();
        try{
            DB::beginTransaction();
            $batch = PvBatches::find($id);
            if(!$batch){
                return $this->notFound('Batch not found');
            }

            PvVouchers::where('batch_id', $id)->delete();

            if($batch->delete()){
                return $this->success(null,'Payment voucher batch and its vouchers deleted successfully');
            }else{
                return $this->serverError('Failed to delete payment voucher batch');
            }
            DB::commit();
        }catch (Exception $e) {
            DB::rollBack();
            return $this->serverError('Failed to delete payment voucher batch: ' . $e->getMessage());
        }
    }

    public function userBuy() {
        $this->auth->authenticate();
        DB::beginTransaction();
        $data = $this->getRequestData();

        $validation = $this->validateRequired($data, [
            'user_id',
            'voucher_id'
        ]);
        if ($validation) return $validation;

        $voucherIds = $data['voucher_id'];
        if(!is_array($voucherIds)){
            $voucherIds = [$voucherIds];
        }

        $vouchers = PvVouchers::whereIn('id', $voucherIds)->where('status', 'available')->get();

        if(count($vouchers) !== count($voucherIds)){
            return $this->serverError('Some vouchers are not available for purchase');
        }

        $purchased = [];
        $batchSoldCounts = []; 

        foreach($vouchers as $voucher){
            $voucher->current_owner_id = $data['user_id'];
            $voucher->original_owner_id = $voucher->original_owner_id ?? $data['user_id'];
            $voucher->status = 'sold'; 
            $voucher->sold_at = date('Y-m-d H:i:s');
            $voucher->save();

            $batchSoldCounts[$voucher->batch_id] = ($batchSoldCounts[$voucher->batch_id] ?? 0) + 1;

            $purchased[] = [
                'id' => $voucher->id,
                'batch_id' => $voucher->batch_id,
                'voucher_code' => $voucher->voucher_code,
                'face_value' => $voucher->face_value,
                'status' => $voucher->status, 
                'current_owner_id' => $voucher->current_owner_id,
                'original_owner_id' => $voucher->original_owner_id,
                'sold_at' => $voucher->sold_at,
                'used_at' => $voucher->used_at,
                'rendered_image' => $voucher->rendered_image
            ];
        }

        foreach($batchSoldCounts as $batchId => $soldQty){
            $this->updatePaymentVoucherBatches($batchId, $soldQty);
        }

        foreach($vouchers as $voucher){
            $this->paymentVoucherTransfer($data['user_id'], $voucher->id);
        }

        return $this->success($purchased, 'Voucher(s) purchased successfully');
        DB::commit();
    }


    private function updatePaymentVoucherBatches($voucher_batch_id, $sold_qty){
        $this->auth->authenticate();
        $batch = PvBatches::find($voucher_batch_id);
        if(!$batch) return;

        $batch->sold_qty = $batch->sold_qty ?? 0; 
        $available = $batch->total_qty - $batch->sold_qty;

        $toAdd = min($sold_qty, $available);
        $batch->sold_qty += $toAdd;
        $batch->save();
    }

    private function paymentVoucherTransfer($user_id,$voucher_id){
        $pv_transfer = new PvTransfers();
        $pv_transfer->voucher_id = $voucher_id;
        $pv_transfer->to_user_id = $user_id;
        $pv_transfer->transfer_type = "purchase";
        $pv_transfer->save();
    }

    public function transfer() {
        $this->auth->authenticate();
        DB::beginTransaction();
        $data = $this->getRequestData();

        $validation = $this->validateRequired($data, [
            'from_user_id',
            'to_user_id',
            'voucher_id',
            'transfer_type'
        ]);
        if ($validation) return $validation;

        $voucherIds = $data['voucher_id'];
        if(!is_array($voucherIds)){
            $voucherIds = [$voucherIds];
        }

        $vouchers = PvVouchers::whereIn('id', $voucherIds)->where('current_owner_id', $data['from_user_id'])->where('status', 'sold')->get();

        if(count($vouchers) !== count($voucherIds)){
            return $this->serverError('Some vouchers are not available for transfer');
        }

        foreach($vouchers as $voucher){
            $voucher->current_owner_id = $data['to_user_id'];
            $voucher->save();

            $pv_transfer = new PvTransfers();
            $pv_transfer->voucher_id = $voucher->id;
            $pv_transfer->to_user_id = $data['to_user_id'];
            $pv_transfer->transfer_type = $data['transfer_type'];
            $pv_transfer->save();
        }

        return $this->success(null, 'Voucher(s) transferred successfully');
        DB::commit();
    }

    public function userUse() {
        $this->auth->authenticate();
        DB::beginTransaction();
        $data = $this->getRequestData();

        $validation = $this->validateRequired($data, [
            'user_id',
            'voucher_id',
            'layanan_id'
        ]);
        if ($validation) return $validation;

        $voucherIds = $data['voucher_id'];
        if(!is_array($voucherIds)){
            $voucherIds = [$voucherIds];
        }

        $check_voucher = PvBatches::whereIn('id', function($query) use ($voucherIds){
            $query->select('batch_id')->from('pv_vouchers')->whereIn('id', $voucherIds);
        })->where('id_layanan', $data['layanan_id'])->first();

        if(!$check_voucher){
            return $this->serverError('Some vouchers are not valid for this Service');
        }

        $vouchers = PvVouchers::whereIn('id', $voucherIds)->where('current_owner_id', $data['user_id'])->where('status', 'sold')->get();

        if(count($vouchers) !== count($voucherIds)){
            return $this->serverError('Some vouchers are not available for use');
        }

        foreach($vouchers as $voucher){
            $voucher->status = 'used';
            $voucher->used_at = date('Y-m-d H:i:s');
            $voucher->save();
        }

        $this->redemptions($voucherIds[0], $data['user_id'], $data['layanan_id'], $check_voucher->face_value);

        return $this->success(null, 'Voucher(s) used successfully');
        DB::commit();
    }

    private function redemptions($voucher_id,$user_id,$id_layanan,$value){
    $this->auth->authenticate();    
        $redemption = new PvRedemptions();
        $redemption->voucher_id = $voucher_id;
        $redemption->user_id = $user_id;
        $redemption->id_layanan = $id_layanan;
        $redemption->redeemed_value = $value;
        $redemption->save();
    }

    public function userVouchers() {
        $this->auth->authenticate();
        $user_id = $_GET['user_id'] ?? null;
        if (!$user_id) {
            return $this->validationError('user_id is required');
        }

        try {
            $vouchers = PvVouchers::where('current_owner_id', $user_id)->get();
            return $this->success($vouchers);
        } catch (Exception $e) {
            return $this->serverError('Failed to fetch user vouchers: ' . $e->getMessage());
        }
    }

    public function userVoucherHistory() {
        $this->auth->authenticate();
        $user_id = $_GET['user_id'] ?? null;
        if (!$user_id) {
            return $this->validationError('user_id is required');
        }

        try {
            $vouchers = PvVouchers::where('current_owner_id', $user_id)->get();
            $history = [];

            foreach ($vouchers as $voucher) {
                $transfers = PvTransfers::where('voucher_id', $voucher->id)->get();
                $redemptions = PvRedemptions::where('voucher_id', $voucher->id)->get();

                $history[] = [
                    'voucher' => $voucher,
                    'transfers' => $transfers,
                    'redemptions' => $redemptions
                ];
            }

            return $this->success($history);
        } catch (Exception $e) {
            return $this->serverError('Failed to fetch user voucher history: ' . $e->getMessage());
        }
    }   
}