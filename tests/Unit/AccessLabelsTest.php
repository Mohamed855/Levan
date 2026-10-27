<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Requests\Admin\AdminMangment\Role\AdminStoreRoleRequest;
use App\Http\Requests\Admin\AdminMangment\Role\AdminUpdateRoleRequest;
use App\Http\Requests\Tenants\UserManagement\Role\StoreRoleRequest;
use App\Http\Requests\Tenants\UserManagement\Role\UpdateRoleRequest;
use App\Support\AccessLabels;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ViewErrorBag;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AccessLabelsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        app()->setLocale('ar');
        view()->share('errors', new ViewErrorBag);
    }

    public function test_role_labels_and_options_preserve_identifiers_and_model_names(): void
    {
        $admin = (new Role)->forceFill(['id' => 11, 'name' => 'Admin', 'guard_name' => 'tenant']);
        $user = (new Role)->forceFill(['id' => 12, 'name' => 'User', 'guard_name' => 'tenant']);
        self::assertSame('مدير النظام الرئيسي', AccessLabels::role('SuperAdmin'));
        self::assertSame([11 => 'مدير النظام', 12 => 'مستخدم'], AccessLabels::roleOptions([$admin, $user]));
        self::assertSame(['Admin' => 'مدير النظام', 'User' => 'مستخدم'], AccessLabels::roleOptions([$admin, $user], 'name'));
        self::assertSame('Admin', $admin->name);
        self::assertSame('tenant', $admin->guard_name);
        self::assertSame('tenant', $admin->getAttributes()['guard_name']);
        self::assertSame('إدارة الشركة', AccessLabels::guard('tenant'));
    }

    public function test_arabic_dashboard_labels_do_not_depend_on_the_default_application_locale(): void
    {
        app()->setLocale('en');
        self::assertSame('مدير النظام الرئيسي', AccessLabels::role('SuperAdmin'));
        self::assertSame('الموافقة على طلب تجربة', AccessLabels::permission('trial_requests.approve'));
        self::assertSame('إدارة المزادات', AccessLabels::section('auctions_management'));
        self::assertSame('SuperAdmin', AccessLabels::roleKey('مدير النظام الرئيسي'));
    }

    public function test_all_seeded_permissions_sections_and_roles_have_arabic_labels(): void
    {
        foreach ([
            database_path('seeders/admin/PermissionsRolesSeeder.php'),
            database_path('seeders/tenant/PermissionsRolesSeeder.php'),
            app_path('Services/Shared/Tenant/Seeders/TenantPermissionSeeder.php'),
        ] as $path) {
            $source = file_get_contents($path);
            preg_match_all("/'([a-z_]+(?:\\.[a-z_]+)+)'/", $source, $permissions);
            foreach (array_unique($permissions[1]) as $name) {
                self::assertMatchesRegularExpression('/[\p{Arabic}]/u', AccessLabels::permission($name), $name);
            }
            preg_match_all("/'([a-z_]+)'\\s*=>\\s*\\[/", $source, $sections);
            foreach (array_unique($sections[1]) as $name) {
                self::assertMatchesRegularExpression('/[\p{Arabic}]/u', AccessLabels::section($name), $name);
            }
            preg_match_all("/'name'\\s*=>\\s*'([A-Za-z_]+)'/", $source, $roles);
            foreach (array_unique($roles[1]) as $name) {
                self::assertMatchesRegularExpression('/[\p{Arabic}]/u', AccessLabels::role($name), $name);
            }
        }
    }

    public function test_shared_select_renders_arabic_labels_and_submits_original_role_ids(): void
    {
        $role = (new Role)->forceFill(['id' => 71, 'name' => 'Admin', 'guard_name' => 'tenant']);
        $html = Blade::render('<x-form.controls.select name="role_id" :options="$roles" :value="71" />', ['roles' => collect([$role])]);
        self::assertStringContainsString('مدير النظام', $html);
        self::assertStringContainsString('value="71" selected', $html);
        self::assertStringNotContainsString('>Admin<', $html);
        self::assertSame('Admin', $role->name);
    }

    public function test_permission_checkboxes_render_arabic_but_keep_english_submission_values(): void
    {
        $permission = (new Permission)->forceFill(['id' => 81, 'name' => 'trial_requests.approve', 'guard_name' => 'admin']);
        $html = view('admin.roles.partials.form', ['role' => null, 'permissions' => collect(['public_requests_management' => collect([$permission])])])->render();
        self::assertStringContainsString('إدارة طلبات التواصل والتجربة', $html);
        self::assertStringContainsString('الموافقة على طلب تجربة', $html);
        self::assertStringContainsString('value="trial_requests.approve"', $html);
        self::assertStringNotContainsString('>trial_requests.approve<', $html);
        self::assertSame('trial_requests.approve', $permission->name);
    }

    public function test_localized_role_form_values_normalize_before_validation_in_both_scopes(): void
    {
        foreach ([
            AdminStoreRoleRequest::class,
            AdminUpdateRoleRequest::class,
            StoreRoleRequest::class,
            UpdateRoleRequest::class,
        ] as $class) {
            $request = $class::create('/', 'POST', ['name' => 'مدير النظام']);
            (new \ReflectionMethod($request, 'prepareForValidation'))->invoke($request);
            self::assertSame('Admin', $request->input('name'));
            self::assertSame(1, preg_match('/^[A-Za-z][A-Za-z0-9_-]*$/', $request->input('name')));
            $regex = collect($request->rules()['name'])->first(fn ($rule) => is_string($rule) && str_starts_with($rule, 'regex:'));
            self::assertSame(0, preg_match(substr($regex, 6), 'دور غير مترجم'));
        }
        self::assertSame('CustomRole', AccessLabels::roleKey(' CustomRole '));
        self::assertSame('CustomRole', AccessLabels::role('CustomRole'));
        self::assertSame('Admin', AccessLabels::roleKey('مدير النظام', 'Admin'));
    }
}
