<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\Store;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            ['slug' => 'starter', 'name' => 'Starter', 'tagline' => 'Pay per release · keep 85%', 'price_kobo' => 0,
                'single_fee_kobo' => 500000, 'album_fee_kobo' => 1200000, 'unlimited_releases' => false, 'videos_per_year' => 0,
                'royalty_share' => 85, 'sort' => 1, 'is_featured' => false,
                'features' => "₦5,000 per single\n₦12,000 per EP / album\nAll major stores\nLyrics distribution\nMonthly earnings reports"],
            ['slug' => 'artist', 'name' => 'Artist', 'tagline' => 'Unlimited releases · keep 100%', 'price_kobo' => 1800000,
                'single_fee_kobo' => 0, 'album_fee_kobo' => 0, 'unlimited_releases' => true, 'videos_per_year' => 2,
                'royalty_share' => 100, 'sort' => 2, 'is_featured' => true,
                'features' => "Unlimited music releases\n2 music videos / year\nLyrics + synced lyrics\nFree ISRC & UPC codes\nWithdraw from ₦5,000"],
            ['slug' => 'label', 'name' => 'Label', 'tagline' => 'For labels & managers · keep 100%', 'price_kobo' => 7500000,
                'single_fee_kobo' => 0, 'album_fee_kobo' => 0, 'unlimited_releases' => true, 'videos_per_year' => 10,
                'royalty_share' => 100, 'sort' => 3, 'is_featured' => false,
                'features' => "Unlimited releases for many artists\n10 music videos / year\nCustom label name\nLyrics + synced lyrics\nPriority WhatsApp support"],
        ];
        foreach ($plans as $p) {
            Plan::firstOrCreate(['slug' => $p['slug']], $p);
        }

        $stores = [
            ['Spotify', 'spotify', false], ['Apple Music / iTunes', 'apple', true], ['Audiomack', 'audiomack', false],
            ['Boomplay', 'boomplay', true], ['YouTube Music', 'youtube_music', false], ['YouTube (Content ID & Video)', 'youtube', true],
            ['TikTok / CapCut', 'tiktok', false], ['Instagram & Facebook', 'meta', false], ['Deezer', 'deezer', false],
            ['Amazon Music', 'amazon', false], ['TIDAL', 'tidal', true], ['Shazam', 'shazam', false],
            ['Anghami', 'anghami', false], ['Pandora', 'pandora', false], ['Musixmatch (lyrics)', 'musixmatch', false],
        ];
        foreach ($stores as $i => [$name, $code, $video]) {
            Store::firstOrCreate(['code' => $code], ['name' => $name, 'supports_video' => $video, 'sort' => $i + 1]);
        }
    }
}
