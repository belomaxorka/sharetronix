# Sharetronix 3.6.2: license review

Reviewed on **2026-10-18** from the supplied distribution. This records the packaged terms and source provenance; it does not replace the license text or determine the enforceability of individual clauses.

## Finding

The bundled license does **not** grant general redistribution rights for the software or maintained derivatives. No separate redistribution grant has been established. Publication of this repository does not change those terms or grant an additional license.

The contents of the archive's `upload/` directory are imported at the repository root. Application file contents and the original license texts are preserved. Git placeholder files retain empty directories, and generated runtime files and OS metadata are excluded from version control.

The title uses “Open Source”, but the distribution restrictions conflict with the [Open Source Definition](https://opensource.org/osd), particularly its free redistribution and derived works criteria. Source availability alone does not establish those freedoms.

## Packaged terms

`LICENSE.txt` is dated **November 11, 2013** and contains two alternatives:

- **A: Open Source Personal License** (the title in the package misspells “license” as “LCENSE”).
- **B: Open Source Commercial License**.

The installer copies, now at `install/license.txt` and `install/old/license.txt`, contain the same substantive text as `LICENSE.txt`; the root file has additional blank lines.

| Topic | What the packaged text says |
| --- | --- |
| Personal use | A §2 permits personal use, copying, and modification subject to the agreement; business purposes require prior written approval. |
| Public source mirror or fork | A §2 and B §2 withhold distribution rights for the software and derivative works, and require a separate agreement before distribution. |
| Commercial use | B §2 permits specified business uses; B §3 states a USD 199 license fee. This is a historical contractual term, not a verified current offer. The commercial license still withholds distribution rights. |
| Hosting | A §2 requires hosting purchased through Sharetronix or an authorized hosting company. |
| Rights in modifications | A §3 and B §4 contain assignment provisions for derivative works, with fallback license grants. Their legal effect would require separate assessment. |
| Name and logo | A §2 and B §2 grant no rights in Sharetronix trademarks, trade names, or logos. |

The original `README.txt` also instructs users to retain the “Powered by Sharetronix” backlink unless a removal option is purchased. No application, attribution, or license files have been modified.

## Provenance

| Item | Observed value |
| --- | --- |
| Archive | `sharetronix-3.6.2.rar` |
| Archive size | 3,069,411 bytes |
| Archive SHA-256 | `3932812548042dce6e0ee5dd1ae76b8f89f8b8d050457d5b4c6403a8d08946cc` |
| Root license SHA-256 | `7b92894c523d74bdf0913535b03e00aa19de080bdb2e7d554ebe6a9f0386a190` |
| Installer license SHA-256 | `00ead815449fcfc89a41dfdf40da608610288053b09890c4b445ed742e9ce8ad` |
| Version declaration in archive | `upload/install/config/config.php`, line 4: `3.6.2` |
| Version declaration in repository | `install/config/config.php`, line 4: `3.6.2` |
| Extracted files | 726 total; 722 in `upload/` |

These fingerprints identify the supplied archive; they do not authenticate it as an unmodified official release or establish that 3.6.2 was the final upstream release. The archive also includes `whatsnew.txt` and a Russian change note named `Редми.txt`; the latter describes changes without establishing their origin.

## Logo located

The distribution contains a green-and-gray Sharetronix wordmark, now at `static/images/mobile/logo-login.png`, and a smaller variant at `static/images/mobile/logo@2.png`. The desktop installer references `static/images/logo.png`. These are bundled assets, not newly created branding; the packaged license grants no trademark rights.

## Historical evidence and its limits

- The [SourceForge listing](https://sourceforge.net/projects/sharetronix/) labels the project as Affero GNU Public License. That listing does not resolve the contradictory terms in this specific archive or establish an AGPL grant for its contents.
- The [historical GitHub repository](https://github.com/vassilmladjov/Sharetronix-OSS) has a [license dated November 14, 2012](https://github.com/vassilmladjov/Sharetronix-OSS/blob/15694c047fb8f5d0bdc64f10b6f80d4be1b4a34e/LICENSE.txt) with personal-use and redistribution restrictions. It is not evidence of a permissive grant for 3.6.2.
- The local 3.6.0 archive's installer license was also checked and contains the November 11, 2013 personal/commercial terms. It is not a verified unrestricted alternative.
- [Opentronix](https://github.com/Opentronix/Opentronix) documents a fork following Sharetronix's license change. Its changelog identifies Sharetronix 1.4.2 as the base, and its license specifies AGPLv3 with attribution requirements. Those terms do not establish rights to the later 3.6.2 code imported here.
- Permissive licenses attached to individual bundled libraries do not establish a license for the complete application.

The original license and third-party notices remain authoritative for their respective materials. This review does not claim that the software has been relicensed under AGPL, GPL, MIT, or any other license.
