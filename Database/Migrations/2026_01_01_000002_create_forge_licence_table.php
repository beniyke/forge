<?php

declare(strict_types=1);

/**
 * Anchor Framework
 *
 * Migration to create the forge_licence table.
 *
 * @author BenIyke <beniyke34@gmail.com> | Twitter: @BigBeniyke
 */

use Database\Migration\BaseMigration;
use Database\Schema\Schema;

class CreateForgeLicenceTable extends BaseMigration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('forge_licence', function ($table) {
            $table->id();
            $table->string('refid', 16)->unique();
            $table->string('key', 64)->unique();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('client_id')->nullable();
            $table->integer('duration_days')->nullable();
            $table->string('status', 20)->default('pending');
            $table->json('metadata')->nullable();
            $table->dateTime('activated_at')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->dateTimestamps();
            $table->softDeletes();

            $table->foreign('product_id')
                ->references('id')
                ->on('wave_product')
                ->onDelete('cascade');

            $table->foreign('client_id')
                ->references('id')
                ->on('client')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('forge_licence');
    }
}
