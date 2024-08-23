<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('opening_hours_time_ranges', static function (Blueprint $table): void {
            $table->id();

            $table->enum("time_rangeable_type",['day', 'exception']);
            $table->unsignedBigInteger("time_rangeable_id");
            $table->index(["time_rangeable_type", "time_rangeable_id"]);

            $table->time('start');
            $table->time('end');
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }
};
