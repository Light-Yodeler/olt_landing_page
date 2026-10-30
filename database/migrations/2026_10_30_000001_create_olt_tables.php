<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            // Brand & Contact
            $table->text('logo_path')->nullable();
            $table->text('favicon_path')->nullable();
            $table->json('company_name');
            $table->json('tagline')->nullable();
            $table->string('whatsapp_number', 20);
            $table->json('whatsapp_default_message')->nullable();
            $table->text('address')->nullable();
            $table->text('instagram_url')->nullable();
            $table->text('facebook_url')->nullable();
            $table->text('tiktok_url')->nullable();
            $table->text('tripadvisor_url')->nullable();
            $table->text('google_maps_url')->nullable();
            $table->json('footer_copyright')->nullable();

            // Hero & Greeting Card
            $table->json('hero_badge')->nullable();
            $table->json('hero_headline')->nullable();
            $table->json('hero_description')->nullable();
            $table->json('hero_highlights')->nullable();

            // About Section
            $table->json('about_badge')->nullable();
            $table->json('about_title')->nullable();
            $table->json('about_body')->nullable();

            // Consultation CTA Banner
            $table->json('cta_title')->nullable();
            $table->json('cta_description')->nullable();
            $table->json('cta_button_text')->nullable();
            $table->json('cta_whatsapp_message')->nullable();

            // Global SEO
            $table->json('meta_title')->nullable();
            $table->json('meta_description')->nullable();
            $table->text('og_image_path')->nullable();

            $table->timestamps();
        });

        Schema::create('hero_slides', function (Blueprint $table) {
            $table->id();
            $table->text('image_path');
            $table->json('caption')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->json('category_code');
            $table->string('icon', 50);
            $table->json('title');
            $table->json('description');
            $table->json('features')->nullable();
            $table->json('button_text')->nullable();
            $table->json('whatsapp_message')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        Schema::create('destinations', function (Blueprint $table) {
            $table->id();
            $table->text('image_path');
            $table->json('name');
            $table->json('category_badge')->nullable();
            $table->json('description');
            $table->json('location')->nullable();
            $table->json('button_text')->nullable();
            $table->json('whatsapp_message')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        Schema::create('standards', function (Blueprint $table) {
            $table->id();
            $table->json('title');
            $table->json('description');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->json('question');
            $table->json('answer');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faqs');
        Schema::dropIfExists('standards');
        Schema::dropIfExists('destinations');
        Schema::dropIfExists('services');
        Schema::dropIfExists('hero_slides');
        Schema::dropIfExists('site_settings');
    }
};
