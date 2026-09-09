# SEO Redirect Plugin: User Guide

This guide explains how to create and manage redirects with the scope01 SEO Redirect plugin in the
Shopware Administration. The focus is on choosing the target and the new language and sales channel
specific SEO URL selection.

## Basics

A redirect consists of:

- **Source URL:** the path the visitor requests (for example `/old-product`).
- **Target:** where to redirect to. The target can be a manually entered URL or, with the Premium
  extension, a product or a category.
- **HTTP code:** `301` (permanent) or `302` (temporary).
- **Sales channel:** optional. Without a selection the redirect applies to all sales channels.

## Creating a redirect

1. Open the **SEO Redirect** module in the Administration and click **Add redirect**.
2. Enter the **Source URL**.
3. Under **Target**, choose one of the following options:
   - **Manual URL:** enter the target URL directly.
   - **Product** or **Category** (Premium): select the target object. The target URL is resolved
     automatically from the current SEO URL of the object and stays dynamically up to date.
4. Optional: restrict the redirect to a channel via **Sales channel**.
5. Save.

## New: selecting the SEO URL and language

When you choose a product or a category as the target, a second field **SEO URL / language** appears.
It lists all existing SEO URLs of the target object, one per **language and sales channel**. An entry
looks like this, for example:

```
Storefront DE  ·  Deutsch  ·  /Wohnzimmer
Storefront EN  ·  English  ·  /en/Living-room
```

Select the entry in the language you want. From then on the redirect points to exactly that language
variant of the target URL. Without a selection the plugin uses the default language as before.

Background: before this extension, a product or category target always used the SEO URL of the default
language. As a result an English page could incorrectly point to the German target page. With the new
selection you decide the target language yourself.

The target URL is still resolved **dynamically**: if the SEO URL of the product or category changes
later, the redirect automatically follows the new SEO URL of the same language.

## How sales channel and language interact

In Shopware, SEO URLs exist per combination of language and sales channel. Keep the following in mind:

- If you select a **channel specific** SEO URL in the second field (for example "Storefront EN ·
  English"), the sales channel of the redirect is set to that channel automatically. The redirect then
  applies only within that channel.
- At runtime the plugin looks for the SEO URL of the selected language that is valid for the requesting
  sales channel (channel specific or channel independent).

### Example: redirect without a sales channel, target language German

Suppose a redirect is **not restricted to any sales channel** (so it applies to all channels) and the
target language is **German**. There is a further sales channel that serves **English only**.

Then the following happens when a visitor requests the source URL in the English channel:

- The plugin looks for the **German** SEO URL of the target that is valid for the English channel.
- If the English channel does not serve German, there is no matching German SEO URL there. The plugin
  finds no valid target and deliberately performs **no** redirect. The visitor lands on the normal 404
  page instead of being redirected to a page that does not exist.
- If there is a channel independent German SEO URL, that one is used.

**Recommendation:** create language specific redirects for the sales channel that actually serves that
language. The easiest way is to select the channel specific SEO URL in the second field. The matching
sales channel is then set automatically.

## Behavior when the target object is deleted

If a linked product or category is deleted, the plugin freezes the last known SEO URL **in the selected
language** as a fixed target URL. This keeps the redirect working even when the object no longer exists.

## Import and export

Redirects can be imported and exported via the standard Shopware import/export feature. On re-import an
existing redirect is updated based on its source URL instead of creating a duplicate.
