<?php

use Modules\Core\Http\Controllers\BaseApiController;
use Modules\Core\Http\Requests\BaseFormRequest;
use Modules\Payment\Contracts\PaymentGatewayInterface;

function stageNineClassFromPath(string $path): string
{
    preg_match('#/Modules/([^/]+)/app/(.+)\.php$#', $path, $matches);

    return 'Modules\\'.$matches[1].'\\'.str_replace('/', '\\', $matches[2]);
}

it('keeps controllers requests and gateways on their shared contracts', function (): void {
    foreach (glob(base_path('Modules/*/app/Http/Controllers/*Controller.php')) as $path) {
        expect(stageNineClassFromPath($path))->toExtend(BaseApiController::class);
    }

    foreach (glob(base_path('Modules/*/app/Http/Requests/*Request.php')) as $path) {
        expect(stageNineClassFromPath($path))->toExtend(BaseFormRequest::class);
    }

    foreach (glob(base_path('Modules/Payment/app/Gateways/*Gateway.php')) as $path) {
        expect(stageNineClassFromPath($path))->toImplement(PaymentGatewayInterface::class);
    }
});

it('contains no debugging calls in application code', function (): void {
    $paths = [base_path('app'), ...glob(base_path('Modules/*/app'))];

    foreach ($paths as $path) {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                expect(file_get_contents($file->getPathname()))
                    ->not->toMatch('/\b(?:dd|dump)\s*\(/');
            }
        }
    }
});

it('keeps raw status strings out of models', function (): void {
    foreach (glob(base_path('Modules/*/app/Models/*.php')) as $path) {
        expect(file_get_contents($path))
            ->not->toMatch("/'(?:pending|confirmed|cancelled|successful|failed)'/");
    }
});
