import os
import re
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

# 1. Search for "Get Estimate"
print("--- SEARCH FOR GET ESTIMATE BUTTON ---")
for fn in files:
    if not os.path.exists(fn):
        continue
    with open(fn, 'r', encoding='utf-8') as f:
        content = f.read()
    
    # Find occurrences of 'Get Estimate' or 'Estimate' around button tags or custom links
    matches = [m.start() for m in re.finditer(r'Get Estimate|Estimate|Calculator|Configurator', content, re.IGNORECASE)]
    if matches:
        print(f"File: {fn} (found {len(matches)} occurrences):")
        for pos in matches[:4]:
            print(f"  Snippet: {content[max(0, pos-60):min(len(content), pos+120)].strip()}")

# 2. Search for how services and solutions are displayed on the homepage index.php
print("\n--- SEARCH FOR SERVICES & SOLUTIONS RENDERING ON HOMEPAGE ---")
for fn in ['longwaysoftronix_v2/index.php', 'lsxpl_v2/index.php']:
    if not os.path.exists(fn):
        continue
    with open(fn, 'r', encoding='utf-8') as f:
        html = f.read()
    
    print(f"\nFile: {fn}")
    # Search for foreach loops over services or industries
    matches = re.findall(r'foreach\s*\(\s*\$(?:services|industries)[^)]*\)', html, re.IGNORECASE)
    print("  Foreach loops found:", matches)
    
    # Let's print around the first few loops
    for loop in ['foreach ($services', 'foreach ($industries']:
        idx = html.find(loop)
        if idx != -1:
            print(f"  Snippet for loop '{loop}':")
            print(html[idx:idx+800])
