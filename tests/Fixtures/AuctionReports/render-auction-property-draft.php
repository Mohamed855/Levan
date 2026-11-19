<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ViewErrorBag;

require dirname(__DIR__, 3).'/vendor/autoload.php';
$app = require dirname(__DIR__, 3).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
view()->share('errors', new ViewErrorBag);
DB::listen(static fn () => throw new RuntimeException('The property draft fixture must not query a database.'));
echo '<!doctype html><meta charset="utf-8"><style>.d-none{display:none!important}.modal:not(.show){display:none}.modal.show{display:block}.modal-backdrop{display:none}</style>';
echo '<form id="wizard-validation-form" data-assignment-properties-url="/assignments/:id/properties"><select id="assignment_id"><option value=""></option><option value="1">إسناد</option></select>';
if (str_starts_with($argv[1] ?? '', 'brokers')) {
    if (($argv[1] ?? '') === 'brokers-existing') {
        request()->setLaravelSession(session()->driver());
        session()->flashInput(['marketing' => ['brokers' => [
            ['key' => 'saved_broker_0', 'name' => 'Saved broker 1'],
            ['key' => 'saved_broker_1', 'name' => 'Saved broker 2'],
        ]]]);
    }
    echo view('tenant.auctions.partials.steps.step4-marketing', ['auction' => null, 'cityOptions' => []])->render();
} else {
    echo view('tenant.auctions.partials.steps.step3-properties', ['auction' => null])->render();
}
echo '</form>';
