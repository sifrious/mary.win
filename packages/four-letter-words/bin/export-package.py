#!/usr/bin/env python3
"""Export canonical game code and data as a deterministic development archive."""
import hashlib
import json
import sys
import zipfile
from pathlib import Path

root = Path(__file__).resolve().parents[1]
output = Path(sys.argv[1])
output.parent.mkdir(parents=True, exist_ok=True)
files = [root / 'composer.json', root / 'README.md'] + sorted((root / 'src').rglob('*.php')) + sorted((root / 'resources').glob('*')) + sorted((root / 'docs').glob('*.md'))
with zipfile.ZipFile(output, 'w', compression=zipfile.ZIP_DEFLATED) as archive:
    for path in files:
        data = path.read_bytes()
        if path.name == 'composer.json':
            manifest = json.loads(data)
            manifest['version'] = '0.1.1'
            data = (json.dumps(manifest, indent=4) + '\n').encode()
        info = zipfile.ZipInfo(path.relative_to(root).as_posix(), (1980, 1, 1, 0, 0, 0))
        info.compress_type = zipfile.ZIP_DEFLATED
        info.external_attr = 0o644 << 16
        archive.writestr(info, data)
print(hashlib.sha256(output.read_bytes()).hexdigest())
