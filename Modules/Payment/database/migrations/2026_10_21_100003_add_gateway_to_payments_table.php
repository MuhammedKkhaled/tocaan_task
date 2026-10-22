<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Payment\Enums\PaymentGateway;
use Modules\Payment\Enums\PaymentMethod;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->string('gateway')->nullable()->after('reference');
        });

        DB::table('payments')
            ->where('method', PaymentMethod::Paypal->value)
            ->update(['gateway' => PaymentGateway::MyFatoorah->value]);

        DB::table('payments')
            ->whereNull('gateway')
            ->update(['gateway' => PaymentGateway::Kashier->value]);

        Schema::table('payments', function (Blueprint $table): void {
            $table->string('gateway')->nullable(false)->change();
            $table->index('gateway');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropIndex(['gateway']);
            $table->dropColumn('gateway');
        });
    }
};
