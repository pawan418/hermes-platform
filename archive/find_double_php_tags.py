import os
import re

files = [
    'longwaysoftronix_v2/index.php',
    'longwaysoftronix_v2/estimator.php',
    'lspl.xyz_v2/index.php',
    'lspl.xyz_v2/estimator.php',
    'lsxpl_v2/index.php',
    'lsxpl_v2/estimator.php'
]

print("Scanning for duplicate PHP tags in all PHP files...")
for fn in files:
    if not os.path.exists(fn):
        continue
    with open(fn, 'r', encoding='utf-8') as f:
        content = f.read()
        
    matches = list(re.finditer(r'<\?php\s*<\?php', content))
    if matches:
        print(f"[{fn}] Found {len(matches)} duplicate tags. Fixing...")
        content_fixed = re.sub(r'<\?php\s*<\?php', '<?php', content)
        with open(fn, 'w', encoding='utf-8') as f:
            f.write(content_fixed)
        print(f"[{fn}] Fixed duplicate tags.")
    else:
        print(f"[{fn}] No duplicate PHP tags found.")
