=== Valvabox AI Writer ===
Contributors: valvabox
Tags: ai, writing assistant, content generator, grammar, seo
Requires at least: 6.3
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A free AI writing assistant for the block editor. Draft posts, rewrite, fix grammar, change tone, translate, and write titles, excerpts, meta descriptions and tags.

== Description ==

Valvabox AI Writer adds an AI assistant to the WordPress block editor using **free** AI providers. No subscription and no paid API.

**Where to find it**

* **✦ AI Writer sidebar**: click the sparkle icon at the top right of the editor.
* **✦ button on every paragraph, heading, list item and verse**: one-click rewrites in place (Ctrl+Z to undo).
* **Tools → AI Writer**: a standalone writer that works with the classic editor too.

**What it can do**

* Write a full draft from a topic (short / medium / long), outline, or continue writing
* Improve, fix spelling & grammar, shorten, lengthen, simplify
* Change tone (friendly, professional, persuasive, playful, …)
* Translate into any language
* Summarise
* Suggest titles (click to apply), SEO meta descriptions, excerpts and tags (one click to add)
* "Ask AI" anything, with your post as context

**Free providers**

* **Pollinations.ai**: works immediately, no sign-up or key (default)
* **Google Gemini**: free API key from Google AI Studio (recommended for quality)
* **Groq**: free API key, very fast
* **OpenRouter**: free API key, use any model ending in `:free`
* **Ollama / LM Studio / any OpenAI-compatible server**: run models yourself, unlimited and private

With **automatic fallback** on, if one provider is down or out of free quota, the plugin tries the next one you have set up.

**Protecting your free quota**

* Per-user hourly request limit (administrators are exempt)
* Identical requests are cached (configurable)
* Long posts are trimmed before sending

**Privacy**

API keys are stored in your database and are never sent to the browser. The text you ask the AI to work on is sent to the provider you choose. Choose Ollama to keep everything on your own server.

== Installation ==

1. Upload the `valvabox-ai-writer` folder to `/wp-content/plugins/`, or upload the ZIP under Plugins → Add New → Upload Plugin.
2. Activate the plugin.
3. Open a post: the ✦ AI Writer sidebar is ready to use with Pollinations.
4. Optional: go to **Settings → AI Writer**, paste a free Gemini or Groq key, choose it as the provider and click **Test connection**.

== Frequently Asked Questions ==

= Is it really free? =
Yes. Every provider offered has a free option. Free tiers have rate limits; the plugin's fallback, caching and per-user limits help you stay within them.

= Who can use it? =
Anyone who can edit posts (`edit_posts`). Change this with the `vbai_capability` filter.

= Requests time out =
Free providers can be slow at busy times. Raise the timeout with the `vbai_http_timeout` filter (default 60 seconds), or switch to Groq or Gemini.

== Changelog ==

= 1.0.0 =
* First release.
