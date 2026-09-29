<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * Render a view inside the dedicated print layout.
     * Usage: return $this->renderPrint('admin.bills.print', $data);
     */
    protected function renderPrint(string $view, array $data = []): View
    {
        return view($view, $data)->with('_layout', 'admin.layouts.print');
    }
}
