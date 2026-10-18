# Sharetronix 3.6.2

<img src="static/images/mobile/logo-login.png" alt="Sharetronix" width="320">

A legacy PHP platform for microblogging and social networking, preserved for continued maintenance and contributions by [@belomaxorka](https://github.com/belomaxorka).

This is an unofficial repository. Original author credits and notices from Sharetronix, LLC and the upstream contributors are preserved.

## Source layout

The application comes from the `upload/` directory of the supplied `sharetronix-3.6.2.rar` archive. Its contents are placed directly in this repository's root:

- `index.php`, `.htaccess`, `web.config` — application entry point and server configuration.
- `system/` — application code, libraries, and languages.
- `themes/`, `static/` — templates, styles, scripts, and images.
- `install/` — installation and upgrade wizard.
- `apps/`, `storage/` — extensions and application storage.

## Getting started

```sh
git clone https://github.com/belomaxorka/sharetronix.git
cd sharetronix
```

The upstream README lists Apache, PHP 5.2+, and MySQL 5.0+ as historical requirements. These are the original requirements; compatibility with current runtimes has not been established.

1. Configure an Apache virtual host for the checkout and enable `mod_rewrite`.
2. Create an empty MySQL database.
3. Open `/install/` on that virtual host and follow the installation wizard.

The installer creates `system/conf_main.php`. This installation-specific configuration, caches, and uploaded files are excluded from Git. See [the original README](README.txt) for the upstream installation notes; its references to `upload/` refer to this repository's root.

## Contributing

Create a branch for a focused change and open a pull request. Include a description of the change, the PHP/MySQL versions used, and how it was checked. Preserve upstream attribution and third-party notices, and keep credentials and installation data out of commits.

The initial import preserves the application files as supplied. Modernization and bug fixes can be reviewed separately from the import.

## License and provenance

The original [Sharetronix personal/commercial license](LICENSE.txt) is retained without relicensing the application. It contains restrictions on redistribution and derivative works. See [the license review](LICENSE-REVIEW.md) for the terms, archive fingerprint, and source provenance.

[Upstream changelog](whatsnew.txt) · [Historical source repository](https://github.com/vassilmladjov/Sharetronix-OSS)
