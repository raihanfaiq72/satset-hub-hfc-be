<?php

class PaymentVoucherController extends BaseController {
    private $auth;

    public function __construct() {
        $this->auth = new AuthMiddleware();
    }

    public function index() {
        // $this->auth->authenticate();
        try {
            $vouchers = PvVouchers::all();
            return $this->success($vouchers);
        } catch (Exception $e) {
            return $this->serverError('Failed to fetch payment vouchers: ' . $e->getMessage());
        }
    }

    public function batches() {
        // $this->auth->authenticate();
        try {
            $batches = PvBatches::all();
            return $this->success($batches);
        } catch (Exception $e) {
            return $this->serverError('Failed to fetch payment voucher batches: ' . $e->getMessage());
        }
    }

    public function batchCreate(Request $request) {
        // $this->auth->authenticate();
        $data = $this->getRequestData();

        $validation = $this->validateRequired($data,[
            'batch_name',
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
            $batch->template_layout = $data['template_layout'];
            $batch->output_format = $data['output_format'];
            $batch->is_active = $data['is_active'];
            $batch->created_by = $data['created_by'];
            $batch->save();

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

            $this->generateVoucher($batch->id,'available');
        }catch (Exception $e) {
            return $this->serverError('Failed to create payment voucher batch: ' . $e->getMessage());
        }
    }

    // ini private fungsi untuk generate voucher code di model pv_voucher

    private function generateVoucher($batch_id,$status){
        // $this->auth->authenticate();
        $batch = PvBatches::find($batch_id);
        if(!$batch){
            throw new Exception('Batch not found');
        }

        $voucher_code = $batch->voucher_prefix . str_pad($batch->sold_qty + 1, 6, '0', STR_PAD_LEFT);
        $voucher = new PvVouchers();
        $voucher->batch_id = $batch_id;
        $voucher->voucher_code = $voucher_code;
        $voucher->face_value = $batch->face_value;
        $voucher->status = $status;
        $voucher->save();

        $batch->sold_qty += 1;
        $batch->save();

        return $voucher;
    }

    public function batchEdit($id) {
        // $this->auth->authenticate();
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
            'is_active'
        ]);

        if ($validation) return $validation;

        try{
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
            $batch->template_layout = $data['template_layout'];
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
        }catch (Exception $e) {
            return $this->serverError('Failed to update payment voucher batch: ' . $e->getMessage());
        }
    }

    public function batchDelete($id) {
        // $this->auth->authenticate();
        try{
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
        }catch (Exception $e) {
            return $this->serverError('Failed to delete payment voucher batch: ' . $e->getMessage());
        }
    }

    public function batchForceDelete($id) {
        // $this->auth->authenticate();
        try{
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
        }catch (Exception $e) {
            return $this->serverError('Failed to delete payment voucher batch: ' . $e->getMessage());
        }
    }
}