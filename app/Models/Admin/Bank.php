<?php
declare(strict_types=1);
namespace App\Models\Admin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
final class Bank extends Model { use SoftDeletes; protected $fillable=['name','account_name','iban','account_number','logo_path','is_active']; protected function casts():array{return ['is_active'=>'boolean'];} }
