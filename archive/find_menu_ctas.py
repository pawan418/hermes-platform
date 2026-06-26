import os
import sys

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8')

files = [
    'longwaysoftronix_v2/index.php',
    'longwaysoftronix_v2/estimator.php',
    'lspl.xyz_v2/index.php',
    'lspl.xyz_v2/estimator.php',
    'lsxpl_v2/index.php',
    'lsxpl_v2/estimator.php'
]

for fn in files:
    if not os.path.exists(fn):
        continue
    with open(fn, 'r', encoding='utf-8') as f:
        html = f.read()
    
    print(f"\n======================================")
    print(f"File: {fn}")
    print(f"======================================")
    
    # Find navigation bar CTA buttons in the header
    # Usually right before </header>
    header_idx = html.find('</header>')
    if header_idx != -1:
        snippet = html[max(0, header_idx - 600):header_idx]
        print("Header Nav CTA area:")
        print(snippet)
    else:
        print("Header close tag not found.")
