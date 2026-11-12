<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('sku', 30)->unique();
            $table->string('name')->index();
            // A category cannot be deleted while products still use it.
            $table->foreignId('category_id')->constrained('categories')->restrictOnDelete();
            $table->string('clothing_type', 50)->nullable();   // Casual Wear, Formal Wear, Sportswear...
            $table->string('size', 20)->index();               // XS, S, M, L, XL, XXL, Free Size
            $table->string('condition', 20)->index();          // Excellent, Good, Fair
            $table->text('description')->nullable();
            $table->decimal('unit_price', 10, 2);
            $table->unsignedInteger('initial_stock')->default(0);
            $table->unsignedInteger('available_stock')->default(0)->index();
            $table->unsignedInteger('minimum_stock')->default(5);
            $table->string('image_path')->nullable();
            $table->softDeletes();                              // keeps sales history intact
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
