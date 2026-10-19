# Gregwar Captcha

Image generator of the built-in CAPTCHA (`system/classes/class_captcha.php`).

- Source: https://github.com/Gregwar/Captcha
- Version: tag `v1.3.0` (commit `4edbcd09fde4353b94ce550f43460eba73baf2cc`, 2025-06-23), the last release that
  supports PHP 7.2. Version 2 requires PHP 8.2.
- Imported on 2026-10-19. The files below are byte-for-byte identical to the tag; nothing is patched.
- Licence: MIT, see `LICENSE`. The fonts are licensed under the SIL Open Font License 1.1, see
  `src/Gregwar/Captcha/Font/*_license.txt`.
- Left out of the release: `README.md`, `composer.json`, and `src/Gregwar/Captcha/ImageFileHandler.php`, the only
  file that needs `symfony/finder` (Sharetronix never writes CAPTCHA images to disk). The release archive itself
  excludes the demos, tests, and CI files.
- Loaded with `require_once`, without Composer. Needs the GD extension with FreeType support and mbstring.

To update, replace `LICENSE` and the files under `src/` with those of a newer release and update this file. Do not
edit the library; if a patch cannot be avoided, keep it minimal, commit it separately, and describe it here.
