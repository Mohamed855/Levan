<?php

declare(strict_types=1);
namespace App\Http\Requests\Admin\Coupons;
use Illuminate\Foundation\Http\FormRequest;
final class StoreCouponRequest extends FormRequest { use CouponRequestRules; public function authorize(): bool { return $this->user('admin')?->hasPermissionTo('coupons.create') ?? false; } public function rules(): array { return $this->couponRules(); } }
