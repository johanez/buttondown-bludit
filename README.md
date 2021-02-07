# Buttondown Newsletter Plugin for [BluditCMS](www.blufit.com)

[Buttondown.email](https://buttondown.email/) is a newsletter service that I prefer over others like e.g. MailChimp.
I'm not affiliated with Buttondown in any other way then happily using their service.
This plugin uses the Buttondown API to automatically send a newsletter for new pages, e.g for subscribers to your blog. The mail contains the full page content.

## Instalation

Download and extract into `bl-blugins`.

## Usage

- Create a [Buttondown.email](https://buttondown.email/) account and configue it for your website.
- Copy the API key into the plugin settings.

### How it works

- Emails will be sent for any that full fills the following criteria:
  - not static
  - not scheduled
  - published
  - creation date after the start date in the plug settings
  - `no-index` option OFF (in page SEO settings, this can be used for excluding pages from the newsletter, e.g. for testing)
  - not scheduled
  - no newsletter for this page has been sent before. **For exceptions see _warnings_ below!.**
- An email will be sent whenever a page is saved for the first time matching these criteria. This is normally the initial creation, or saving a draft page as published.
- The mail body contains the cover image (optionally), the page title and the content (including html).

### Warnings

- **Changing the slug of a page (friendly URL in page SEO settings) will cause a resend!**
- If the plugin is _paused_ (on this plugin's settings page), mails will not be send. **However, when active again, mails will be sent for pages that were created while paused, when these pages are modified and saved matching the above criteria.**
- If the plugin is deactivated and reactivated from the Bludit admin plugin page, the sent list will be cleared. This means mails will be sent again for any page modified matching the criteria!

### Known Issues

- A newsletter _should_ be sent when a a scheduled page is due and appears on the site. However, the internal trigger for this [doesn't seem to work](https://github.com/bludit/bludit/issues/1307). You can (messily) patch this by adding `Theme::plugins('afterPageModify', array($pageKey));` in the `scheduler()` before `$saveDatabase = true;` in the file `pages.class.php`.

## License

MIT, see LICENSE file.
