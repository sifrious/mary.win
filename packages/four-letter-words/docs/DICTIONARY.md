# Dictionary provenance

The canonical file is `packages/four-letter-words/resources/words.txt`. It contains 4,360 entries and has SHA-256 `0cb445519760e5dac63355e409b76a4979706c583d288d32e510c7c76bde1830`.

On September 9, 2026, the entire file was reproduced byte for byte from the macOS BSD `web2` dictionary. The transformation selects entries matching lowercase ASCII `[a-z]{4}`, uppercases them, removes duplicates, sorts them, and writes one entry per line with a final newline. The lowercase filter excludes capitalized source entries. This proves a reproducible source match; no recovered commit explains the original author's extraction command.

Run `python3 packages/four-letter-words/bin/verify-dictionary.py <path-to-web2>` to check a supplied source. The application does not depend on a device's system dictionary.

The BSD distribution describes the source as Webster's Second International and reports that the 1934 copyright lapsed according to its supplier. See the [FreeBSD source notice](https://github.com/freebsd/freebsd-src/blob/main/share/dict/README). That statement is the source's rights claim, not an MIT license from mary.win. Keep the source notice with any redistributed dictionary. The current metadata conservatively reports `offline_distribution_ready: false` while the mobile release and bundled notice are being prepared. This flag is a release-readiness marker.

No entries changed during this investigation. Replacement dictionaries would change the game and require a separate data decision and version.
