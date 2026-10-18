<img src="static/images/sharetronix-logo.png" alt="Sharetronix" width="500">

A maintained fork of Sharetronix **3.6.2**, a PHP platform for microblogging and social networking.

See the [changelog](CHANGELOG.md) for release history and the [maintenance roadmap](ROADMAP.md) for planned work.

## Source layout

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

The legacy codebase lists Apache, PHP 5.2+, and MySQL 5.0+ as its requirements. Compatibility with current PHP/MySQL releases has not been established.

1. Configure an Apache virtual host for the checkout and enable `mod_rewrite`.
2. Create an empty MySQL database.
3. Open `/install/` on that virtual host and follow the installation wizard.

The installer creates `system/conf_main.php`. This installation-specific configuration, caches, and uploaded files are excluded from Git.

A reproducible local development stack and a verified runtime matrix are planned in the roadmap. See the [testing guide](docs/TESTING.md) for environment records and application scenarios.

## Contributing

Read [CONTRIBUTING.md](CONTRIBUTING.md) for the workflow, Conventional Commits, and verification requirements. Follow existing code conventions, keep changes focused, and preserve author credits and third-party notices. Coding agents must also follow [AGENTS.md](AGENTS.md).

Original contributor credits remain in the source files.

## License

Sharetronix is licensed under the [GNU Affero General Public License, version 3](LICENSE.txt) (`AGPL-3.0-only`). Third-party components retain their respective licenses and copyright notices.
