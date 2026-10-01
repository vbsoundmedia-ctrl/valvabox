<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
        });

        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('tagline')->nullable();
            $table->unsignedBigInteger('price_kobo')->default(0);       // yearly price
            $table->unsignedBigInteger('single_fee_kobo')->default(0);  // per-release fee when plan doesn't cover it
            $table->unsignedBigInteger('album_fee_kobo')->default(0);
            $table->boolean('unlimited_releases')->default(false);
            $table->unsignedInteger('videos_per_year')->default(0);
            $table->unsignedTinyInteger('royalty_share')->default(100); // % the artist keeps
            $table->text('features')->nullable();                       // one per line
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->boolean('supports_video')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('releases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 10)->default('single');      // single | ep | album
            $table->string('title');
            $table->string('primary_artist');
            $table->string('featured_artists')->nullable();
            $table->string('label_name')->nullable();
            $table->string('genre', 60);
            $table->string('language', 40)->default('English');
            $table->date('release_date')->nullable();
            $table->boolean('explicit')->default(false);
            $table->string('copyright_line')->nullable();       // © line
            $table->string('phonographic_line')->nullable();    // ℗ line
            $table->string('upc', 20)->nullable();
            $table->string('artwork_path')->nullable();
            $table->string('artwork_thumb_path')->nullable();
            $table->string('status', 20)->default('draft');     // draft|pending_payment|in_review|approved|delivered|live|rejected|takedown
            $table->text('rejection_reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'submitted_at']);
        });

        Schema::create('release_store', function (Blueprint $table) {
            $table->foreignId('release_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('url')->nullable();
            $table->primary(['release_id', 'store_id']);
        });

        Schema::create('tracks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('release_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position')->default(1);
            $table->string('title');
            $table->string('version')->nullable();
            $table->string('featured_artists')->nullable();
            $table->string('isrc', 15)->nullable()->index();
            $table->string('composers')->nullable();
            $table->string('lyricists')->nullable();
            $table->string('producers')->nullable();
            $table->boolean('explicit')->default(false);
            $table->string('language', 40)->nullable();
            $table->string('audio_path')->nullable();
            $table->string('audio_name')->nullable();
            $table->unsignedBigInteger('audio_size')->default(0);
            $table->longText('lyrics')->nullable();
            $table->longText('lyrics_lrc')->nullable();
            $table->timestamps();
        });

        Schema::create('videos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('track_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('artist');
            $table->string('director')->nullable();
            $table->string('isrc', 15)->nullable();
            $table->boolean('explicit')->default(false);
            $table->date('release_date')->nullable();
            $table->string('video_path')->nullable();
            $table->string('video_name')->nullable();
            $table->string('video_url')->nullable();            // Google Drive / Dropbox / WeTransfer link for big files
            $table->string('thumbnail_path')->nullable();
            $table->string('status', 20)->default('draft');
            $table->text('rejection_reason')->nullable();
            $table->string('youtube_url')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('reference', 60)->unique();
            $table->string('gateway', 20);
            $table->string('purpose', 20);                      // plan | release | video
            $table->unsignedBigInteger('purpose_id');
            $table->string('description');
            $table->unsignedBigInteger('amount_kobo');
            $table->string('status', 20)->default('pending');   // pending | success | failed
            $table->text('gateway_response')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::create('ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20);                         // royalty | withdrawal | reversal | adjustment
            $table->bigInteger('amount_kobo');                  // + credit, - debit
            $table->string('description');
            $table->string('ref_type', 40)->nullable();
            $table->unsignedBigInteger('ref_id')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('bank_code', 20)->nullable();
            $table->string('bank_name');
            $table->string('account_number', 20);
            $table->string('account_name');
            $table->string('recipient_code')->nullable();
            $table->timestamps();
        });

        Schema::create('payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('reference', 60)->unique();
            $table->unsignedBigInteger('amount_kobo');
            $table->unsignedBigInteger('fee_kobo')->default(0);
            $table->string('bank_name');
            $table->string('bank_code', 20)->nullable();
            $table->string('account_number', 20);
            $table->string('account_name');
            $table->string('status', 20)->default('pending');   // pending | processing | paid | failed | rejected
            $table->string('transfer_code')->nullable();
            $table->text('admin_note')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('royalty_imports', function (Blueprint $table) {
            $table->id();
            $table->string('period', 7);                        // YYYY-MM
            $table->string('source')->nullable();
            $table->string('file_name');
            $table->decimal('fx_rate', 12, 4);
            $table->unsignedInteger('rows')->default(0);
            $table->unsignedInteger('unmatched_rows')->default(0);
            $table->decimal('total_usd', 16, 6)->default(0);
            $table->unsignedBigInteger('credited_kobo')->default(0);
            $table->foreignId('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('royalty_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('royalty_import_id')->constrained()->cascadeOnDelete();
            $table->foreignId('track_id')->nullable();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('isrc', 15)->nullable();
            $table->string('store', 60)->nullable();
            $table->string('country', 10)->nullable();
            $table->unsignedBigInteger('units')->default(0);
            $table->decimal('amount_usd', 16, 6)->default(0);
            $table->unsignedBigInteger('amount_kobo')->default(0);
            $table->timestamps();
        });

        Schema::create('webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('gateway', 20);
            $table->string('event', 60)->nullable();
            $table->string('reference', 100)->nullable();
            $table->boolean('signature_ok')->default(false);
            $table->longText('payload');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['webhook_events', 'royalty_lines', 'royalty_imports', 'payouts', 'bank_accounts', 'ledger_entries',
            'payments', 'videos', 'tracks', 'release_store', 'releases', 'stores', 'plans', 'settings'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
