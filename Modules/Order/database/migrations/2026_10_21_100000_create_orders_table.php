<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Core\Enums\Currency;
use Modules\Order\Enums\OrderStatus;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('number')->unique();
            $table->string('status')->default(OrderStatus::Pending->value)->index();
            $table->decimal('total_amount', 12, 2);
            $table->string('currency', 3)->default(Currency::USD->value);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
