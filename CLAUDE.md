# CLAUDE.md

Project context for Claude Code. Read this file fully before any task.

## 1. Product summary

A freemium WordPress plugin for managing external contributors (doctors on a health site, service providers on a directory, guest writers on a blog). It covers the full contributor lifecycle in one plugin:

1. The admin creates a contributor role from the plugin.
2. The admin gives that role access to one or more custom post types.
3. Visitors apply to join the role through an application form; the admin approves or rejects with reasons and notes.
4. Contributors submit content; every submission and every edit goes to review.
5. Every admin action on a submission (approve, reject, delete, move to draft) notifies the contributor, and contributor actions notify the admin.
6. The admin can hold contributors accountable (warnings, temporary publishing suspension).

### Positioning

Do not market it as "one plugin instead of three". Each separate piece has stronger competitors (PublishPress Capabilities / Revisions / Planner, WP User Frontend, User Submitted Posts, Ultimate Member, ProfilePress). The differentiator is the combination around one job: **vetting, receiving, reviewing and holding accountable external contributors**. The join application, reviewer feedback and sanctions system together are the unique part. Roles and capabilities alone are not.

### Name

Working name is not final. "Publisher Master" was rejected as too generic and not searchable. Candidates: Vetted, Contributor Desk, Byline Gate. Before release, verify availability of the WordPress.org slug, the domain, and trademarks.

Until the name is decided, use these placeholders consistently so a rename is a single search and replace:

- Plugin slug / text domain: `contributors-desk`
- PHP prefix / namespace root: `Contributor_Desk` / `cdesk_`
- Option and meta prefix: `_cdesk_`

## 2. Key product decisions (already made, do not revisit without asking)

1. **Editing published content sends it back to review and takes it offline.** There is NO "keep the published version live while a pending copy is reviewed" mechanism (Revisionary style). This was a deliberate scope decision to avoid the hardest technical part (cloning and merging post content, meta, ACF fields, page builder data).
2. Because of decision 1, the contributor must be told clearly before submitting an edit that the post will disappear from the site until approved.
3. Mitigations to build for decision 1:
   - Persistent notice in the editor when editing a published post, plus a confirmation dialog on save explaining the post will go offline until approved.
   - Optional temporary "content under update" page instead of a 404 for posts that were published and are now pending re-review, served with HTTP `503` and a `Retry-After` header, so search engines treat it as temporary.
   - Admin setting for a review deadline, shown to the contributor in the notice, with a reminder email to the admin when a submission exceeds it.
   - Admin setting to exempt specific roles or users from re-review on edits (trusted contributors).
4. Keeping the published version live during review may become a Pro feature later, only if users ask for it.
5. Source strings in code are English and fully translatable. Arabic translation is done later through `.po/.mo` files.

## 3. Scope

### Free (must be genuinely useful on its own; WordPress.org forbids crippled or license-locked free versions)

- One contributor role linked to one custom post type.
- Contributors see and manage only their own posts.
- Submission to review (`pending`), admin approval to publish.
- Re-review on edit of published posts, with the mitigations in section 2.
- Notifications with fixed texts for all events in section 5.
- Join application with basic fields; admin approves or rejects with a reason.

### Pro (separate add-on plugin, not locked code inside the free plugin)

- Multiple roles and multiple post types.
- Customizable message templates with placeholders.
- Reviewer notes on submissions.
- Warnings and temporary publishing suspension.
- Custom fields builder for the join application.
- Frontend contributor dashboard (many contributors will not want wp-admin).
- Contributor reports.
- Integrations: webhooks, Slack.
- Later, on demand: keep published version live during edit review.

### MVP order

1. Join application.
2. Submission and review workflow.
3. Notifications.

Release the MVP free. Add sanctions and edit-review extras as the first Pro features after seeing real usage.

### Validation before heavy building

Search the support forums of PublishPress, User Submitted Posts and similar plugins for recurring unsolved requests such as "application to become author", "suspend contributor", "reviewer feedback". Recurring unanswered requests are the demand signal.

## 4. Reference implementation

A working single-file prototype exists, written for a client project (Nova Healthcare Practitioner: role `healthcare_practitioner`, post type `scientific_source`, taxonomy `item-type`). If it is placed in the repo, keep it under `reference/` and never ship it.

**Ownership:** that code was written for a client. Confirm the agreement allows reuse in a commercial product, or rewrite the foundation independently. Treat the prototype as a record of solved problems, not as code to copy.

### Lessons learned from the prototype (apply these)

- **Custom capabilities for the post type:** filter `register_post_type_args` to set `capability_type => [ singular, plural ]` and `map_meta_cap => true`, and unset any existing `capabilities` array.
- **Taxonomy terms managed by admins only:** filter `register_taxonomy_args` so `manage_terms`, `edit_terms`, `delete_terms` require `manage_categories` and `assign_terms` requires the post type `edit_{plural}` cap. Also block term creation in `pre_insert_term` for users without `manage_categories`, because non-hierarchical taxonomy boxes and REST can otherwise create terms.
- **Admin and editor capabilities must be granted dynamically** through the `user_has_cap` filter for any user with `edit_others_posts`. Relying only on caps stored in the database caused admins to lose the post type menu (role editor plugins, persistent object cache, and timing all break it). Still sync stored caps, but never depend on them for managers.
- **Cap sync timing:** run the sync on `init`, not `admin_init`. The admin menu is built before `admin_init`, so a sync there leaves the first load without caps. Gate the sync behind a stored version option and reset that option on activation.
- **Contributor caps:** `read`, `upload_files`, `edit_{plural}`, `edit_published_{plural}`, `delete_{plural}`. Never `publish_{plural}`, `edit_others_{plural}`, `edit_others_posts`, `edit_posts`. Without the publish cap, WordPress turns "Publish" into "Submit for Review" and saves as `pending`.
- **Force re-review on edit:** filter `wp_insert_post_data`; when the current user is a contributor, the post exists, and the status is `publish`, `future` or `private`, set it to `pending`. Skip during autosave. Hook it globally, not only in admin, because the block editor saves through REST where `is_admin()` is false.
- **Own posts only in lists:** `pre_get_posts` sets `author` on the post type list screen and `upload.php`; `ajax_query_attachments_args` does the same for the media modal; `wp_count_posts` is replaced with per-author counts; remove the `mine` view.
- **Contributor admin experience:** redirect login and `index.php` to the post type list; remove Dashboard, Media, Comments and Tools menus and some admin bar nodes.
- **WooCommerce:** it blocks wp-admin for users without `edit_posts`. Return false from `woocommerce_prevent_admin_access` and `woocommerce_disable_admin_bar` for contributors.
- **Track first approval** with a post meta flag set when a post reaches `publish` (and when it leaves `publish`, to cover posts published before the plugin). Use it to distinguish "published" vs "edit approved" and "rejected" vs "deleted after publishing".
- **Role label translation:** PHP constants cannot hold `__()` calls (fatal error). Store the role label in English in the database and translate it at display time through `gettext_with_context` with context `User role` and domain `default`, because WordPress displays role names via `translate_user_role()`.
- **Emails in the recipient's language:** wrap each email in `switch_to_user_locale()` / `restore_previous_locale()` (requires WordPress 6.2). For the admin email address, look up the matching user; fall back to `switch_to_locale( get_locale() )`.
- **Text domain loading:** `load_plugin_textdomain()` on `init` (WordPress 6.7+ warns when translations load earlier). Header needs `Domain Path: /languages`.
- **Text domain must be a literal string** in every `__()` call, not a constant, so `wp i18n make-pot` can extract strings.
- **Emails depend on `wp_mail`.** Document that an SMTP plugin is likely required for delivery.

## 5. Notification rules

Recipient locale applies to every email. Admin actions only notify the contributor when the acting user is not the post author (a contributor deleting or drafting their own post triggers nothing).

| Event | Recipient | Message meaning |
|---|---|---|
| New submission goes to `pending` | Admin | New item pending review, with review link |
| Edit of approved item goes to `pending` | Admin | Changes to a published item pending review |
| `pending` to `publish`, never approved before | Contributor | Your item has been published |
| `pending` to `publish`, approved before | Contributor | Your changes have been approved (not "published") |
| Deleted (trash or direct permanent delete) before any approval | Contributor | Your item was rejected, you may prepare it and submit again |
| Deleted after a previous approval | Contributor | The item you previously published under title X has been deleted |
| Moved to draft from `pending`, `publish`, `future` or `private` | Contributor | Your item was moved to draft, contact the admin at the support email to find out why |
| Review deadline exceeded | Admin | Reminder |

Implementation notes:

- Use `transition_post_status` for status changes. Ignore same-status transitions.
- For deletion, notify on transition to `trash`, and on `before_delete_post` only when the post is not already in trash, so emptying the trash never sends a second email.
- Restoring from trash sends nothing (WordPress 5.6+ restores to draft).
- The support email is a plugin setting, not hardcoded.
- Pro: every message becomes a template with placeholders.

## 6. Code conventions

- Code, comments in headers, identifiers and source strings: English. Conversation with the user: Arabic.
- Singleton architecture for main classes.
- Conditional asset loading: enqueue CSS and JS only on the screens that need them.
- HPOS compatibility declared whenever anything touches WooCommerce.
- Output full files, not partial snippets.
- No added code comments, except required plugin headers.
- Human-like, readable code patterns; avoid generated-looking boilerplate.
- Translator comments (`/* translators: */`) are the one exception to consider before any WordPress.org submission, since Plugin Check flags their absence on strings with placeholders. Ask the user before adding them.
- Do not use em dashes anywhere, including readme, UI strings and docs.
- Minimum requirements: WordPress 6.2, PHP 7.4.
- Security: nonces and capability checks on every action, sanitize input, escape output, `$wpdb->prepare()` for every query.

## 7. Suggested structure

```
contributors-desk/
  contributors-desk.php
  uninstall.php
  readme.txt
  languages/
  includes/
    class-plugin.php
    class-roles.php
    class-capabilities.php
    class-post-types.php
    class-review-workflow.php
    class-notifications.php
    class-applications.php
    class-admin-experience.php
    class-offline-notice.php
    class-settings.php
  assets/
    css/
    js/
  templates/
    emails/
    under-review.php
reference/
```

## 8. Tooling

- Syntax check: `php -l`.
- Plugin Check plugin before every release.
- Local environment: `wp-env` or LocalWP.
- POT file: `wp i18n make-pot . languages/contributors-desk.pot`.
- Arabic translation files: `contributors-desk-ar.po` / `contributors-desk-ar.mo`.

## 9. Open questions

- Final product name and slug.
- Reuse rights for the client prototype.
- Whether the MVP includes a frontend dashboard or stays in wp-admin.
- Pricing and sales channel for Pro.
