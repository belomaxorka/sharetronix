# Changelog

Release history for Sharetronix, with the latest changes first.

## Unreleased

### Fixed

- Retained login sessions on non-default ports while preserving URL authorities and existing session names for ordinary domains.
- Registered the existing class-loading behavior through SPL to prevent autoload deprecations from corrupting headers and compressed responses.
- Stopped installer execution immediately after the successful completion redirect, avoiding reads from the destroyed installer session.
- Required an active administrator and session-bound confirmation before installed-site updates; disabled the obsolete installer entry point.
- Defined the private temporary-directory path and corrected cron cleanup for expired files, including filenames equal to zero.
- Corrected request-token expiry to use the existing database column, allowing cron processing to continue past token cleanup.
- Replaced removed curly-brace string offsets in core redirects/password generation and bundled CAPTCHA/audio code, restoring PHP 8.4 parsing.
- Removed a daily-maintenance query against the deleted RSS table, allowing database optimization to complete with the current schema.
- Corrected daily temporary-file cleanup to use the private directory rather than the worker's current directory, preserving placeholders and subdirectories.

### Added

- Added a maintenance roadmap, contributor workflow, testing guide, and repository instructions for coding agents.
- Added an isolated Windows development stack with local email capture, fixture accounts, backup commands, and repeatable HTTP smoke checks.
- Recorded the initial installation, application, restore, and PHP compatibility checks with reproducible defect reports.

### Changed

- Licensed the project under GNU AGPLv3 while retaining original author credits and third-party notices.
- Updated project documentation and converted the release history to Markdown.
- Refreshed the README branding with a blue Sharetronix wordmark and speech-bubble mascot.

### Removed

- Removed the obsolete root `README.txt`.
- Removed the retired developer-site version check from background token cleanup.

## 3.6.2

- Bug fixes.

## 3.6.1

- Bug fixes.
- Plugin architecture improvements.

## 3.6.0

- Post to user profile.
- Post to a group from the dashboard.
- Like plugin integration.
- Edit post plugin integration.
- Bug fixes and improvements.

## 3.5.0

- Bug fixes.
- Group categories.
- Group sorting.
- Image preview and select when posting links.
- Saved search.
- Vine posts.

## 3.4.0

- Security fixes.

## 3.3.0

- Bug fixes.
- Admin Stealth mode.

## 3.2.2

- Bug fixes and improvements.

## 3.2

- Security updates.
- Multilingual FAQ.
- New default captcha.
- Bug fixes.

## 3.1.1.3

- Bug fixes and improvements.

## 3.1.1.2

- Bug fixes and improvements.

## 3.1.1.1

- Bug fixes and improvements.

## 3.1.1

- Bug fixes and improvements.

## 3.1.0

- Added themes.
- New installation procedure.
- Twitter and Facebook signup integration.
- Plugin architecture optimizations.
- Bug fixes and improvements.

## 3.0.1

- Bug fixes and improvements.

## 3.0.0

- Plugin architecture.

## 2.2.1

- Bug fixes.
- Updates.
- Improvements.

[Extended release notes](http://developer.sharetronix.com/whatsnew/extended#2.2.1).

## 2.2.0

- Comment likes.
- Bug fixes, updates, and optimizations.
- Comments remain open after commenting on a post.
- New notification system.
- Show more button.
- jQuery.
- Redesigned home and signup pages.
- Updated comment deletion.
- Fixed the Romanian language pack.

[Extended release notes](http://sharetronix.com/sharetronix/whatsnew/extended#2.2.0).

## 2.1.0

- Bug fixes and updates.
- Database optimizations.
- Added a Likes tab to the dashboard.
- Sphinx Search.
- Updated Community Leaders.
- Updated administrator controls for editing users.
- Updated the mobile version, including signup from the touch interface.
- Added Facebook, Twitter, and Google+ sharing on the post page.
- Added post spam controls.
- Fixed the Portuguese language pack.
- Fixed the Indonesian language pack.

[Extended release notes](http://sharetronix.com/sharetronix/whatsnew/extended#2.1.0).

## 2.0.0

- StxBugFixPack154_1 is included in Sharetronix Personal 2.0.0.
- Bug fixes.
- Cronjob updates.
- Direct Post to Twitter.
- Unshare a post.
- Import posts from Twitter into the Sharetronix community.
- Direct post to Facebook.
- Added Community Leaders statistics.
- Configure post deletion by post type and age.
- Integrated reCaptcha support.
- Detect mobile browsers automatically and redirect to the mobile version.
- Resend invitations to your users.
- Suggest users while typing a post or comment (thanks to `@abhishek`).
- Post Like/Unlike.
- Multiple File Upload.
- IP Restriction.
- Added Ukrainian language pack (thanks to notbad).
- Added Serbian language pack (thanks to Urosh).

[Extended release notes](http://sharetronix.com/sharetronix/whatsnew/extended#2.0.0).

## 1.5.4

Bug fixes and improvements.

- Protect Outside Pages.
- Protect My Profile.
- Email Domains Control.
- Added an option to enable or disable the API.
- Small Bug fixes and Improvements.
- `@me` dashboard tab fixes.
- Protect my posts.
- Protect my direct messages.
- Notification post handling options.
- Allow administrators to select special groups.
- Fixed pagination in group search.
- API Updates.
- E-mail notifications updates.
- Fixed duplicated `http://` prefixes in Facebook links.
- Check for plugins directly in the administration section.

[Extended release notes](http://sharetronix.com/sharetronix/whatsnew/extended#1.5.4).

## 1.5.3

Bug fixes and improvements.

- Small Updates and Fixes.
- FAQ Updates.
- API Documentation Updates.
- Users in Common.
- Added a Skype call button to user business cards.
- Extended profile details with personal email, personal phone, and work phone fields.
- Include the post message in email notifications.
- Added more post share links (MySpace, LinkedIn, StumbleUpon, FriendFeed, Digg).
- Added Reshares tab in dashboard.
- Added an option to search only posts, excluding RSS feeds and tweets.
- API updates.
- Removed the follow-user registration step for communities with more than 50 members.
- Fixed a cronjob bug (thanks to `@alien`).
- Fixed bug in the `@me` tab counter.
- Windows Compatibility updates.
- Added QR code in user's profile.
- Added Azeri language pack (thanks to Ibrahim Jabbari, http://ibrahimjabbari.com).
- Added Croatian language pack (thanks to Dario Abram, http://share.david-rose.co.cc/).
- Added Galego language pack (thanks to latrica, http://latri.ca).
- Added Lithuanian language pack (thanks to Aurimas, http://goon.lt).
- Added Norwegian language pack (thanks to Truls Paulsen).

[Extended release notes](http://sharetronix.com/sharetronix/whatsnew/extended#1.5.3).

## 1.5.2

Bug fixes and improvements.

- Fixed comment deletion in the rebackgroundable theme, updates to admin settings, Basic Authorization login, business cards in Firefox, and other bugs.
- Updated the Suspend Applications page, API, Twitter feed import, language packs (Gr, Es, Esar), network settings, and other components.
- Added detection of widescreen YouTube videos.
- Improved Windows Server compatibility for RSS feeds, dates, and times.
- Added Captcha on Signup.
- Added support for Armenian tags.

[Extended release notes](http://sharetronix.com/sharetronix/whatsnew/extended#1.5.2).

## 1.5.1

API updates, bug fixes, design improvements.

- Upgraded the API and added support for more Twitter API clients.
- Fixed issues with suspended users, frozen sharing menus, email delivery, Twitter feeds, opening comments after posting, and other bugs.
- Design improvements.
- Included Greek language pack (thanks to Karasardelis, http://www.tikiland.gr).
- Included Spanish (Argentina) language pack (thanks to Marcos Besteiro López, http://propiedadprivada.com).

[Extended release notes](http://sharetronix.com/sharetronix/whatsnew/extended#1.5.1).

## 1.5.0

Resharing, API, LDAP, PubSubHubbub, bug fixes, and improvements.

- Added resharing of posts, similar to retweets, with design improvements.
- API for Developers (just like the Twitter API) (still BETA).
- LDAP Integration (requires PHP LDAP extension).
- Added PubSubHubbub real-time feed aggregation (still beta).
- Twitter Feed Import.
- Added Czech language pack (thanks to Petr Bednar, http://www.damak.cz).
- Added Armenian language pack (thanks to Samvel Chakhalyan, http://myhayastan.am).
- Improved French language pack (thanks to Fabien Stoll, http://ecoloactu.com).
- Sort group lists by name, date, member count, or post count.
- Automatic check for Sharetronix software update in Administration.
- Improved the dashboard layout and fixed column widths.
- Fixed some bugs related to email functions and notifications.
- Fixed a bug in the APC cache functionality.
- Fixed a bug related to Facebook Connect button.
- Option for footer backlink removal.
- Performance improvements & other small bug fixes.

Related file: `system/classes/class_newpost.php`.

[Extended release notes](http://sharetronix.com/sharetronix/whatsnew/extended#1.5.0).

## 1.4.2

Bug fixes, improvements, changed license.

- License terms changed.
- Added searching within post comments.
- Performance improvements (thanks to Roman, http://microbloggy.ru).
- Added support for embedding videos from Facebook.com and Rutube.ru (thanks to Roman, http://microbloggy.ru).
- Performance improvements.
- Improved Russian language pack (thanks to Roman, http://microbloggy.ru).
- Improvements to RSS aggregator.
- Fixed some dead links in the mobile version.

[Extended release notes](http://sharetronix.com/sharetronix/whatsnew/extended#1.4.2).

## 1.4.1

Bug fixes, improvements.

- Fixed some bugs in Themes.
- Security improvements related to file uploads.
- Support for Persian characters in tags, groups, network name, etc.
- Improved Romanian language pack (thanks to Zamfirescu Alexandru, http://ebarfa.ro).

[Extended release notes](http://sharetronix.com/sharetronix/whatsnew/extended#1.4.1).

## 1.4.0

Themes, new languages, bug fixes, improvements.

- Added a Theme engine and 4 new themes.
- Limit group invitations to the user's followers.
- Added Vietnamese language pack (thanks to Nguyen The Huy, http://codon.vmmo.net).
- Added Polish language pack (thanks to Mariusz Bednarz, http://kalorie.net).
- Added a Dutch language pack (thanks to Swiggy, http://www.swiggy.eu).
- RSS aggregator doesn't require cURL anymore.
- Fixed a bug in the FAQ section.
- Fixed a bug when posting embedded videos.
- Fixed a bug in the iPhone version.
- Fixes some other small bugs and issues.
- Installation wizard compatibility improvements.
- Updates to German language pack (thanks to Christoph Germscheid, http://ms-freunde.de).

## 1.3.0

iPhone version, bug fixes, and improvements.

- Added an iPhone interface at the existing mobile URL, `site-url.com/m`.
- Added support for right-to-left languages.
- Added Farsi language pack (thanks to Amir Moghadam, http://friendfa.com).
- Added pagination to the JavaScript user selector.
- Fixed a bug in the Search center.
- Added an alternative to the PHP JSON extension.

## 1.2.2

Bug fixes, improvements.

- User accounts can be edited from Administration.
- Invitation center: Importing contacts from Yahoo accounts (check out the README file).
- Fixed a bug in the Facebook integration.
- Fixed a bug in the Twitter integration.
- Fixed a compatibility issue in the Invitation center.
- Fixed a bug in the Administration center.
- Fixed some bugs in the Hebrew characters support.
- Fixed some bugs in the Chinese characters support.

## 1.2.0

Facebook and Twitter integration, improvements, new languages, and bug fixes.

- Added Facebook Connect integration (check out the README file for more info).
- Added Twitter OAuth login integration (check out the README file for more info).
- Option to share each post in Facebook, Twitter, Google Buzz and Delicious.
- Importing contacts from Gmail accounts, vCards, CSV files.
- Site homepage intro text can be edited from Administration.
- User accounts can now be permanently deleted from Administration.
- User can permanently delete their own account.
- User can change their e-mail address.
- Added Followers box in the user profile page.
- Added Frequently Asked Questions Module.
- Terms of Use page can be activated and edited from Administration.
- Added German language pack (thanks to Christoph Germscheid, http://ms-freunde.de).
- Added Portuguese language pack (thanks to Jeferson Jose Reis, http://amizzer.com).
- Added Indonesian language pack (thanks to Abadi Abdillah, http://abadiabdillah.com).
- Added Albanian language pack (thanks to Mendim Idrizi, http://www.shqip.in).
- Added Portuguese language pack (thanks to José Cordeiro, http://permacultura.telegrafo.pt).
- Added Swedish language pack (thanks to Niclas, http://www.miniforum.se).
- Added Romanian language pack (thanks to Laviniu).
- RSS feeds improvements: username/password auth isn't required for public feeds.
- Old PHP MySQL extension is replaced with MySQLi by default.
- Support for both PHP Memcached and Memcache extensions.
- Multilingual system improvements.
- Fixed a bug with the proper delivery of some emails.
- Fixed a bug with some accented characters (ä, ò, ê, ú, etc.).
- Fixed a bug in the Invitation center.
- Fixed some bugs in some of the language files.

## 1.1.0

Multilingual system, bug fixes, performance improvements, compatibility improvements.

- Added Italian language pack (thanks to Fabio Labianca, http://sharetronix.it).
- Added French language pack (thanks to Fabien Stoll, http://ecoloactu.com).
- Added Russian language pack (thanks to Elefant, http://slon.nsys.by).
- Added Japanese language pack (thanks to Taku Nakajima, http://twitter.com/essa).
- Added Macedonian language pack (thanks to Ivan Mihajlovich, http://mikrobloger.com).
- Added Spanish language pack (thanks to Pablo Macaluso, http://twindo.com.ar).
- Added Bulgarian language pack (thanks to http://edno23.com).
- Added Chinese Traditional language pack (thanks to Tim Kuo, http://xirang.us).
- Added Chinese Simplified language packs (Niujue, niaochao.org / Tim Kuo, xirang.us).
- Multilingual system improvements.
- PHP directive "allow_url_fopen" is no longer required if cURL library is available.
- Posts by RSS feeds are auto-deleted a month later if not commented or bookmarked.
- Fixed an HTML injection vulnerability (thanks to MaXe, security[at]intern0t.net).
- Email system improvements.
- Cronjob simulator improvements.
- Database performance improvements (new index `api_id` for table `posts`).
- Timezones menu is more human-friendly.
- Fixed some bugs in the RSS generator.
- Fixed some bugs with #hashtags and `@users` inside posts and comments.
- Fixed a bug when loading language files with mistaken encodings.
- Fixed a bug in the RSS/Atom aggregator.
- Fixed a bug in the Installation wizard.
- Fixed a bug in the cronjobs simulator.
- Fixed a bug in the user settings page.
- Fixed a DHTML bug in Opera.

## 1.0.4

Bug fixes, compatibility improvements.

- Email confirmation requirement is now optional in Administration.
- Fixed a bug in the RSS/Atom parser.
- Added global RSS feed with all network posts.
- Chinese support for network name and group name.
- Fixed some bugs with #hashtags and `@users` inside posts and comments.
- Fixed some bugs with Saved Searches.
- Fixed a bug in Registration.
- Fixed a bug in Settings.
- Fixed issues when running on Windows Server.
- Fixed a bug with cookies when using localhost.
- Fixed cookie handling when using an IP address as the site URL.
- Fixed some bugs in the Installation wizard.
- Fixed some bugs in the templates engine.
- Fixed some mistaken links and texts.
- Fixed browser-specific CSS bugs.
- Fixed a bug in the mobile interface.

## 1.0.0

- Introduced an entirely new engine and design.
