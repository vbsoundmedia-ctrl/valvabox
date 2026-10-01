<?php

namespace Tests\Feature;

use App\Http\Controllers\InstallController;
use App\Models\Payment;
use App\Models\Payout;
use App\Models\Plan;
use App\Models\Release;
use App\Models\Setting;
use App\Models\Store;
use App\Models\Track;
use App\Models\User;
use App\Services\Wallet;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PlatformTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        InstallController::$installedOverride = true;
        $this->seed(DatabaseSeeder::class);
        Storage::fake('local');
        Setting::put('paystack_secret', 'sk_test_secret');
    }

    protected function tearDown(): void
    {
        InstallController::$installedOverride = null;
        parent::tearDown();
    }

    private function artist(array $attrs = []): User
    {
        return User::create($attrs + ['name' => 'Tobi A', 'artist_name' => 'Tobi', 'email' => 'tobi'.uniqid().'@example.com', 'password' => 'password123']);
    }

    private function readyRelease(User $user, string $type = 'single'): Release
    {
        $r = new Release(['type' => $type, 'title' => 'Lagos Nights', 'primary_artist' => 'Tobi', 'genre' => 'Afrobeats', 'language' => 'English',
            'release_date' => now()->addDays(20), 'copyright_line' => '2026 Tobi', 'phonographic_line' => '2026 Tobi']);
        $r->user_id = $user->id;
        $r->artwork_path = 'releases/x/art.jpg';
        $r->save();
        $r->stores()->sync(Store::pluck('id'));
        $t = new Track(['title' => 'Lagos Nights', 'composers' => 'T. A']);
        $t->release_id = $r->id;
        $t->audio_path = 'releases/x/a.wav';
        $t->save();

        return $r;
    }

    public function test_landing_page_shows_naira_plans(): void
    {
        $this->get('/')->assertOk()->assertSee('₦18,000')->assertSee('Paid in Naira');
    }

    public function test_redirects_to_installer_when_not_installed(): void
    {
        InstallController::$installedOverride = false;
        $this->get('/')->assertRedirect('/install');
        $this->get('/install')->assertOk()->assertSee('Install Valvabox');
    }

    public function test_installer_is_hidden_after_install(): void
    {
        $this->get('/install')->assertNotFound();
    }

    public function test_artist_can_register_and_see_dashboard(): void
    {
        $this->post('/register', ['name' => 'Ada Obi', 'artist_name' => 'Ada', 'email' => 'ada@example.com',
            'password' => 'password123', 'password_confirmation' => 'password123', 'terms' => '1'])->assertRedirect('/dashboard');
        $this->get('/dashboard')->assertOk()->assertSee('Welcome, Ada');
    }

    public function test_create_release_with_artwork_and_track(): void
    {
        $user = $this->artist();
        $this->actingAs($user)->post('/releases', [
            'type' => 'single', 'title' => 'Owambe', 'primary_artist' => 'Tobi', 'genre' => 'Afrobeats', 'language' => 'Yoruba',
            'release_date' => now()->addDays(14)->toDateString(), 'copyright_line' => '2026 Tobi', 'phonographic_line' => '2026 Tobi',
            'explicit' => '0', 'artwork' => UploadedFile::fake()->image('cover.jpg', 3000, 3000),
        ])->assertRedirect();
        $release = Release::firstOrFail();
        $this->assertNotNull($release->artwork_path);
        Storage::disk('local')->assertExists($release->artwork_path);

        $this->actingAs($user)->post("/releases/{$release->id}/tracks", [
            'title' => 'Owambe', 'composers' => 'Tobi A', 'explicit' => '0',
            'audio' => UploadedFile::fake()->create('owambe.wav', 2048, 'audio/wav'),
        ])->assertRedirect("/releases/{$release->id}");
        $this->assertSame(1, $release->tracks()->count());
        $this->actingAs($user)->get("/releases/{$release->id}")->assertOk()->assertSee('Owambe');
    }

    public function test_small_artwork_is_rejected(): void
    {
        $user = $this->artist();
        $this->actingAs($user)->post('/releases', [
            'type' => 'single', 'title' => 'X', 'primary_artist' => 'Tobi', 'genre' => 'Afrobeats', 'language' => 'English',
            'release_date' => now()->addDays(14)->toDateString(), 'copyright_line' => 'a', 'phonographic_line' => 'b',
            'artwork' => UploadedFile::fake()->image('cover.jpg', 1000, 1000),
        ])->assertSessionHasErrors('artwork');
    }

    public function test_other_users_cannot_see_a_release(): void
    {
        $release = $this->readyRelease($this->artist());
        $this->actingAs($this->artist())->get("/releases/{$release->id}")->assertNotFound();
    }

    public function test_free_plan_submission_goes_to_paystack_and_callback_marks_paid(): void
    {
        $user = $this->artist();
        $release = $this->readyRelease($user);
        Http::fake([
            'api.paystack.co/transaction/initialize' => Http::response(['status' => true, 'data' => ['authorization_url' => 'https://checkout.paystack.com/abc']]),
            'api.paystack.co/transaction/verify/*' => Http::response(['status' => true, 'data' => ['status' => 'success', 'amount' => 500000, 'currency' => 'NGN']]),
        ]);

        $this->actingAs($user)->post("/releases/{$release->id}/submit", ['confirm_rights' => '1'])
            ->assertRedirect('https://checkout.paystack.com/abc');
        $payment = Payment::firstOrFail();
        $this->assertSame(500000, (int) $payment->amount_kobo); // ₦5,000 single fee on Starter
        $this->assertSame('pending_payment', $release->fresh()->status);

        $this->actingAs($user)->get('/pay/callback/paystack?reference='.$payment->reference)->assertRedirect("/releases/{$release->id}");
        $this->assertSame('success', $payment->fresh()->status);
        $this->assertSame('in_review', $release->fresh()->status);
        $this->assertNotNull($release->fresh()->paid_at);
    }

    public function test_paid_plan_covers_release_without_payment(): void
    {
        $plan = Plan::where('slug', 'artist')->first();
        $user = $this->artist(['plan_id' => $plan->id, 'plan_expires_at' => now()->addMonths(6)]);
        $release = $this->readyRelease($user, 'album');
        Http::fake();
        $this->actingAs($user)->post("/releases/{$release->id}/submit", ['confirm_rights' => '1'])->assertRedirect("/releases/{$release->id}");
        $this->assertSame('in_review', $release->fresh()->status);
        Http::assertNothingSent();
    }

    public function test_paystack_webhook_requires_valid_signature_and_activates_plan(): void
    {
        $user = $this->artist();
        $plan = Plan::where('slug', 'artist')->first();
        $payment = Payment::create(['user_id' => $user->id, 'reference' => 'vbx_test1', 'gateway' => 'paystack', 'purpose' => 'plan',
            'purpose_id' => $plan->id, 'amount_kobo' => $plan->price_kobo, 'description' => 'Artist plan']);
        Http::fake(['api.paystack.co/transaction/verify/*' => Http::response(['status' => true, 'data' => ['status' => 'success', 'amount' => $plan->price_kobo, 'currency' => 'NGN']])]);
        $body = json_encode(['event' => 'charge.success', 'data' => ['reference' => 'vbx_test1']]);

        $this->call('POST', '/webhooks/paystack', [], [], [], ['HTTP_X_PAYSTACK_SIGNATURE' => 'bad', 'CONTENT_TYPE' => 'application/json'], $body)->assertStatus(401);
        $this->assertSame('pending', $payment->fresh()->status);

        $sig = hash_hmac('sha512', $body, 'sk_test_secret');
        $this->call('POST', '/webhooks/paystack', [], [], [], ['HTTP_X_PAYSTACK_SIGNATURE' => $sig, 'CONTENT_TYPE' => 'application/json'], $body)->assertOk();
        $this->call('POST', '/webhooks/paystack', [], [], [], ['HTTP_X_PAYSTACK_SIGNATURE' => $sig, 'CONTENT_TYPE' => 'application/json'], $body)->assertOk();

        $user->refresh();
        $this->assertSame('success', $payment->fresh()->status);
        $this->assertSame($plan->id, $user->plan_id);
        $this->assertTrue($user->plan_expires_at->between(now()->addYear()->subMinute(), now()->addYear()->addMinute())); // applied once
    }

    public function test_underpayment_is_not_accepted(): void
    {
        $user = $this->artist();
        $release = $this->readyRelease($user);
        $payment = Payment::create(['user_id' => $user->id, 'reference' => 'vbx_low', 'gateway' => 'paystack', 'purpose' => 'release',
            'purpose_id' => $release->id, 'amount_kobo' => 500000, 'description' => 'x']);
        Http::fake(['api.paystack.co/transaction/verify/*' => Http::response(['status' => true, 'data' => ['status' => 'success', 'amount' => 100, 'currency' => 'NGN']])]);
        $this->actingAs($user)->get('/pay/callback/paystack?reference=vbx_low');
        $this->assertSame('failed', $payment->fresh()->status);
        $this->assertNull($release->fresh()->paid_at);
    }

    public function test_royalty_import_credits_wallet_with_plan_share(): void
    {
        $admin = $this->artist(['role' => 'admin']);
        $user = $this->artist(); // Starter: keeps 85%
        $release = $this->readyRelease($user);
        $release->tracks()->first()->forceFill(['isrc' => 'NGA0D2600001'])->save();
        $csv = UploadedFile::fake()->createWithContent('report.csv', "ISRC,Store,Country,Streams,Revenue\nNGA0D2600001,Spotify,NG,1000,10.00\nNGA0D2600001,Audiomack,GH,500,2.00\nXXX000000000,Spotify,NG,5,1.00\n");

        $this->actingAs($admin)->post('/admin/royalties', ['file' => $csv, 'period' => '2026-08', 'fx_rate' => '1500'])->assertSessionHasNoErrors();
        // $12 × 1500 × 85% = ₦15,300
        $this->assertSame(1530000, $user->balanceKobo());
        $this->assertSame(1500, (int) \App\Models\RoyaltyLine::where('user_id', $user->id)->sum('units'));
    }

    public function test_withdrawal_debits_wallet_and_reject_refunds(): void
    {
        $user = $this->artist();
        $user->bankAccount()->create(['bank_name' => 'GTBank', 'bank_code' => '058', 'account_number' => '0123456789', 'account_name' => 'TOBI A']);
        app(Wallet::class)->credit($user, 2000000, 'royalty', 'test');

        $this->actingAs($user)->post('/wallet/withdraw', ['amount' => '10000'])->assertSessionHasNoErrors();
        $payout = Payout::firstOrFail();
        $this->assertSame(2000000 - 1000000 - 5000, $user->balanceKobo());

        $this->actingAs($user)->post('/wallet/withdraw', ['amount' => '20000'])->assertSessionHasErrors('amount'); // insufficient

        $admin = $this->artist(['role' => 'admin']);
        $this->actingAs($admin)->post("/admin/payouts/{$payout->id}", ['action' => 'reject'])->assertSessionHasNoErrors();
        $this->assertSame('rejected', $payout->fresh()->status);
        $this->assertSame(2000000, $user->balanceKobo());
    }

    public function test_paystack_transfer_payout_flow(): void
    {
        $user = $this->artist();
        $user->bankAccount()->create(['bank_name' => 'GTBank', 'bank_code' => '058', 'account_number' => '0123456789', 'account_name' => 'TOBI A']);
        app(Wallet::class)->credit($user, 2000000, 'royalty', 'test');
        $payout = app(Wallet::class)->requestWithdrawal($user, 1000000);
        Http::fake([
            'api.paystack.co/transferrecipient' => Http::response(['status' => true, 'data' => ['recipient_code' => 'RCP_1']]),
            'api.paystack.co/transfer' => Http::response(['status' => true, 'data' => ['status' => 'pending', 'transfer_code' => 'TRF_1']]),
        ]);
        $admin = $this->artist(['role' => 'admin']);
        $this->actingAs($admin)->post("/admin/payouts/{$payout->id}", ['action' => 'paystack'])->assertSessionHasNoErrors();
        $this->assertSame('processing', $payout->fresh()->status);

        $body = json_encode(['event' => 'transfer.failed', 'data' => ['reference' => $payout->reference]]);
        $this->call('POST', '/webhooks/paystack', [], [], [], ['HTTP_X_PAYSTACK_SIGNATURE' => hash_hmac('sha512', $body, 'sk_test_secret')], $body)->assertOk();
        $this->assertSame('failed', $payout->fresh()->status);
        $this->assertSame(2000000, $user->balanceKobo());
    }

    public function test_admin_area_is_protected_and_renders(): void
    {
        $this->actingAs($this->artist())->get('/admin')->assertForbidden();
        $admin = $this->artist(['role' => 'admin']);
        $release = $this->readyRelease($this->artist());
        foreach (['/admin', '/admin/releases?status=all', "/admin/releases/{$release->id}", '/admin/videos', '/admin/users', '/admin/payments',
            '/admin/payouts', '/admin/royalties', '/admin/catalog', '/admin/settings'] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    public function test_admin_approval_requires_codes(): void
    {
        $admin = $this->artist(['role' => 'admin']);
        $release = $this->readyRelease($this->artist());
        $track = $release->tracks()->first();
        $this->actingAs($admin)->put("/admin/releases/{$release->id}", ['status' => 'approved'])->assertSessionHasErrors('upc');
        $this->actingAs($admin)->put("/admin/releases/{$release->id}", ['status' => 'approved', 'upc' => '123456789012', 'isrc' => [$track->id => 'NGA0D2600001']])
            ->assertSessionHasNoErrors();
        $this->assertSame('approved', $release->fresh()->status);
    }

    public function test_artist_pages_render(): void
    {
        $user = $this->artist();
        $release = $this->readyRelease($user);
        $track = $release->tracks()->first();
        foreach (['/dashboard', '/releases', '/releases/create', "/releases/{$release->id}", "/releases/{$release->id}/edit",
            "/releases/{$release->id}/tracks/create", "/tracks/{$track->id}/edit", "/tracks/{$track->id}/lyrics",
            '/videos', '/videos/create', '/plans', '/payments', '/wallet', '/profile', '/legal/terms', '/legal/privacy'] as $url) {
            $this->actingAs($user)->get($url)->assertOk();
        }
    }

    public function test_settings_secret_is_encrypted_at_rest(): void
    {
        $raw = \DB::table('settings')->where('key', 'paystack_secret')->value('value');
        $this->assertNotSame('sk_test_secret', $raw);
        $this->assertSame('sk_test_secret', setting('paystack_secret'));
    }

    public function test_submit_without_payment_setup_shows_message(): void
    {
        Setting::put('paystack_secret', '');
        \DB::table('settings')->where('key', 'paystack_secret')->delete();
        \Illuminate\Support\Facades\Cache::forget('vb_settings');
        $user = $this->artist();
        $release = $this->readyRelease($user);
        $this->actingAs($user)->from("/releases/{$release->id}")->post("/releases/{$release->id}/submit", ['confirm_rights' => '1'])
            ->assertRedirect("/releases/{$release->id}")->assertSessionHasErrors('submit');
        $this->assertSame('draft', $release->fresh()->status);
        $this->assertSame(0, Payment::count());
    }
}
