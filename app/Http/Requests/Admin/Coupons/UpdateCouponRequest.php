<?php

declare(strict_types=1);
namespace App\Http\Requests\Admin\Coupons;
use Illuminate\Foundation\Http\FormRequest;
final class UpdateCouponRequest extends FormRequest { use CouponRequestRules; public function authorize(): bool { return $this->user('admin')?->hasPermissionTo('coupons.update') ?? false; } public function rules(): array { return $this->couponRules($this->route('coupon')); } }
