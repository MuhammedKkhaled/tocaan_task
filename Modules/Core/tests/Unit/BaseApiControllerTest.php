<?php

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Core\Http\Controllers\BaseApiController;

it('builds success and error envelopes', function (): void {
    $controller = new BaseApiController();

    expect($controller->successResponse('Done.', ['data' => ['id' => 1]])->getData(true))->toBe([
        'message' => 'Done.',
        'data' => ['id' => 1],
    ])->and($controller->errorResponse('Failed.', ['field' => ['Invalid.']], 422)->getData(true))->toBe([
        'message' => 'Failed.',
        'error' => ['field' => ['Invalid.']],
    ]);
});

it('adds length-aware pagination metadata to resource responses', function (): void {
    $paginator = new LengthAwarePaginator(
        [['id' => 1], ['id' => 2]],
        total: 3,
        perPage: 2,
        currentPage: 1,
        options: ['path' => '/api/examples'],
    );

    $json = (new BaseApiController())
        ->fromResource(JsonResource::collection($paginator))
        ->toResponse()
        ->getData(true);

    expect($json['data'])->toHaveCount(2)
        ->and($json['meta'])->toMatchArray([
            'total_Items' => 3,
            'items_Per_Page' => 2,
            'Items_in_page' => 2,
            'current_Page' => 1,
            'last_Page' => 2,
        ]);
});

it('adds cursor pagination metadata to resource responses', function (): void {
    $paginator = new CursorPaginator(
        [['id' => 2], ['id' => 1]],
        perPage: 1,
        options: ['path' => '/api/examples', 'parameters' => ['id']],
    );

    $json = (new BaseApiController())
        ->fromResource(JsonResource::collection($paginator))
        ->toResponse()
        ->getData(true);

    expect($json['data'])->toHaveCount(1)
        ->and($json['meta']['items_Per_Page'])->toBe(1)
        ->and($json['meta']['Items_in_page'])->toBe(1)
        ->and($json['meta']['next_cursor'])->not->toBeNull()
        ->and($json['meta']['previous_cursor'])->toBeNull();
});
