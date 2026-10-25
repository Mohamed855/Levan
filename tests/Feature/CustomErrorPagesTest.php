<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class CustomErrorPagesTest extends TestCase
{
    /**
     * Test 404 page renders custom view and status 404.
     */
    public function test_404_error_renders_custom_view(): void
    {
        $response = $this->get('/non-existent-route-for-testing-404');

        $response->assertStatus(404);
        $response->assertSee('الصفحة غير موجودة');
        $response->assertSee('404');
    }

    /**
     * Test 403 page renders custom view and status 403.
     */
    public function test_403_error_renders_custom_view(): void
    {
        Route::domain('localhost')->middleware('web')->get('/admin/test-error-403', function () {
            abort(403);
        });

        $response = $this->get('http://localhost/admin/test-error-403');

        $response->assertStatus(403);
        $response->assertSee('غير مصرح لك بالدخول');
        $response->assertSee('403');
    }

    /**
     * Test 500 error renders custom view and status 500.
     */
    public function test_500_error_renders_custom_view(): void
    {
        Route::domain('localhost')->middleware('web')->get('/admin/test-error-500', function () {
            abort(500);
        });

        $response = $this->get('http://localhost/admin/test-error-500');

        $response->assertStatus(500);
        $response->assertSee('حدث خطأ داخلي في الخادم');
        $response->assertSee('500');
    }

    /**
     * Test unhandled exception renders custom 500 view.
     */
    public function test_unhandled_exception_renders_custom_500_view(): void
    {
        Route::domain('localhost')->middleware('web')->get('/admin/test-unhandled-exception', function () {
            throw new \RuntimeException('Database connection broke unexpectedly');
        });

        $response = $this->get('http://localhost/admin/test-unhandled-exception');

        $response->assertStatus(500);
        $response->assertSee('حدث خطأ داخلي في الخادم');
        $response->assertSee('500');
    }

    /**
     * Test JSON/AJAX requests still receive JSON errors.
     */
    public function test_json_requests_receive_json_errors(): void
    {
        $response = $this->getJson('/non-existent-route-for-testing-404');
        $response->assertStatus(404);
        $response->assertJsonStructure(['message']);
    }
}
