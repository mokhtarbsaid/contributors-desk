=== Contributors Desk ===
Contributors: mokhtarbsaid
Tags: contributors, guest posts, editorial workflow, author application, moderation
Requires at least: 6.2
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Vet, receive, review and hold accountable the external contributors of your site.

== Description ==

Contributors Desk handles the full lifecycle of external contributors, such as doctors writing for a health site, providers listed in a directory, or guest writers on a blog.

* Visitors apply to join through an application form.
* You approve or reject each application with a reason, and the applicant is notified by email.
* Approved applicants get a dedicated contributor role.

Emails are sent through `wp_mail()`. For reliable delivery, use an SMTP plugin.

== Installation ==

1. Upload the `contributors-desk` folder to `/wp-content/plugins/`.
2. Activate the plugin from the Plugins screen.
3. Go to Contributors Desk > Settings.
4. Add the `[cdesk_application_form]` shortcode to the page where visitors should apply.

== Changelog ==

= 0.1.0 =
* Initial development version.
