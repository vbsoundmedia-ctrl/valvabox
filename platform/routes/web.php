<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InstallController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReleaseController;
use App\Http\Controllers\TrackController;
use App\Http\Controllers\VideoController;
use App\Http\Controllers\WalletController;
use Illuminate\Support\Facades\Route;

// One-time installer
Route::get('/install', [InstallController::class, 'show'])->name('install');
Route::post('/install', [InstallController::class, 'install'])->name('install.run');

// Public
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/legal/{page}', [HomeController::class, 'page'])->name('page');

// Payment gateways
Route::get('/pay/callback/{gateway}', [PaymentController::class, 'callback'])->name('payments.callback')->whereIn('gateway', ['paystack', 'flutterwave']);
Route::post('/webhooks/{gateway}', [PaymentController::class, 'webhook'])->name('webhooks')->whereIn('gateway', ['paystack', 'flutterwave']);

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:10,1');
    Route::get('/forgot-password', [AuthController::class, 'showForgot'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email')->middleware('throttle:5,1');
    Route::get('/reset-password/{token}', [AuthController::class, 'showReset'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'reset'])->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::resource('releases', ReleaseController::class);
    Route::post('/releases/{release}/stores', [ReleaseController::class, 'stores'])->name('releases.stores');
    Route::post('/releases/{release}/submit', [ReleaseController::class, 'submit'])->name('releases.submit');

    Route::get('/releases/{release}/tracks/create', [TrackController::class, 'create'])->name('tracks.create');
    Route::post('/releases/{release}/tracks', [TrackController::class, 'store'])->name('tracks.store');
    Route::get('/tracks/{track}/edit', [TrackController::class, 'edit'])->name('tracks.edit');
    Route::put('/tracks/{track}', [TrackController::class, 'update'])->name('tracks.update');
    Route::delete('/tracks/{track}', [TrackController::class, 'destroy'])->name('tracks.destroy');
    Route::get('/tracks/{track}/lyrics', [TrackController::class, 'lyrics'])->name('tracks.lyrics');
    Route::put('/tracks/{track}/lyrics', [TrackController::class, 'saveLyrics'])->name('tracks.lyrics.save');

    Route::resource('videos', VideoController::class);
    Route::post('/videos/{video}/submit', [VideoController::class, 'submit'])->name('videos.submit');

    Route::get('/plans', [PaymentController::class, 'plans'])->name('plans');
    Route::post('/plans/{plan}/buy', [PaymentController::class, 'buyPlan'])->name('plans.buy');
    Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
    Route::post('/payments/{payment}/retry', [PaymentController::class, 'retry'])->name('payments.retry');

    Route::get('/wallet', [WalletController::class, 'index'])->name('wallet');
    Route::post('/wallet/bank', [WalletController::class, 'saveBank'])->name('wallet.bank');
    Route::post('/wallet/withdraw', [WalletController::class, 'withdraw'])->name('wallet.withdraw')->middleware('throttle:5,1');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'password'])->name('profile.password');

    Route::get('/files/artwork/{release}/{size?}', [FileController::class, 'artwork'])->name('files.artwork');
    Route::get('/files/audio/{track}', [FileController::class, 'audio'])->name('files.audio');
    Route::get('/files/video/{video}', [FileController::class, 'video'])->name('files.video');
    Route::get('/files/thumbnail/{video}', [FileController::class, 'thumbnail'])->name('files.thumbnail');

    Route::prefix('admin')->name('admin.')->middleware('admin')->group(function () {
        Route::get('/', Admin\DashboardController::class)->name('dashboard');
        Route::get('/releases', [Admin\ReleaseController::class, 'index'])->name('releases.index');
        Route::get('/releases/{release}', [Admin\ReleaseController::class, 'show'])->name('releases.show');
        Route::put('/releases/{release}', [Admin\ReleaseController::class, 'update'])->name('releases.update');
        Route::get('/releases/{release}/package', [Admin\ReleaseController::class, 'package'])->name('releases.package');
        Route::get('/videos', [Admin\VideoController::class, 'index'])->name('videos.index');
        Route::get('/videos/{video}', [Admin\VideoController::class, 'show'])->name('videos.show');
        Route::put('/videos/{video}', [Admin\VideoController::class, 'update'])->name('videos.update');
        Route::get('/users', [Admin\UserController::class, 'index'])->name('users.index');
        Route::get('/users/{user}', [Admin\UserController::class, 'show'])->name('users.show');
        Route::put('/users/{user}', [Admin\UserController::class, 'update'])->name('users.update');
        Route::post('/users/{user}/adjust', [Admin\UserController::class, 'adjust'])->name('users.adjust');
        Route::get('/payments', [Admin\PaymentController::class, 'index'])->name('payments');
        Route::get('/payouts', [Admin\PayoutController::class, 'index'])->name('payouts');
        Route::post('/payouts/{payout}', [Admin\PayoutController::class, 'action'])->name('payouts.action');
        Route::get('/royalties', [Admin\RoyaltyController::class, 'index'])->name('royalties');
        Route::post('/royalties', [Admin\RoyaltyController::class, 'store'])->name('royalties.store');
        Route::get('/royalties/template', [Admin\RoyaltyController::class, 'template'])->name('royalties.template');
        Route::get('/catalog', [Admin\CatalogController::class, 'index'])->name('catalog');
        Route::put('/catalog/plans/{plan}', [Admin\CatalogController::class, 'updatePlan'])->name('plans.update');
        Route::post('/catalog/stores', [Admin\CatalogController::class, 'storeStore'])->name('stores.store');
        Route::post('/catalog/stores/{store}/toggle', [Admin\CatalogController::class, 'toggleStore'])->name('stores.toggle');
        Route::get('/settings', [Admin\SettingController::class, 'edit'])->name('settings');
        Route::put('/settings', [Admin\SettingController::class, 'update'])->name('settings.update');
    });
});
