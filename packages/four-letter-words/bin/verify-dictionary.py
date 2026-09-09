#!/usr/bin/env python3
"""Check the committed dictionary against a supplied BSD web2 source file."""
import hashlib
import re
import sys
from pathlib import Path

source = Path(sys.argv[1]).read_text().splitlines()
words = sorted({word.upper() for word in source if re.fullmatch(r'[a-z]{4}', word)})
expected = ('\n'.join(words) + '\n').encode()
actual = (Path(__file__).resolve().parents[1] / 'resources/words.txt').read_bytes()
print(f'{len(words)} words; SHA-256 {hashlib.sha256(expected).hexdigest()}')
if actual != expected:
    raise SystemExit('Dictionary differs from the supplied source.')
print('Exact byte match.')
