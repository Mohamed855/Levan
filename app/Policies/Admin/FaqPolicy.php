<?php
namespace App\Policies\Admin;
use App\Models\Admin\Faq; use App\Models\Admin\AdminMangment\Users\AdminUser;
final class FaqPolicy { public function viewAny(AdminUser $u):bool{return $u->can('faqs.view');} public function view(AdminUser $u,Faq $f):bool{return $u->can('faqs.view');} public function create(AdminUser $u):bool{return $u->can('faqs.create');} public function update(AdminUser $u,Faq $f):bool{return $u->can('faqs.edit');} public function delete(AdminUser $u,Faq $f):bool{return $u->can('faqs.delete');} }
