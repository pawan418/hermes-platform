import os
import re

paths = [
    r'c:\Users\pawan\Downloads\lspl-claude\lspl\longwaysoftronix_v2\app.js',
    r'c:\Users\pawan\Downloads\lspl-claude\lspl\lspl.xyz_v2\app.js',
    r'c:\Users\pawan\Downloads\lspl-claude\lspl\lsxpl_v2\app.js'
]

for p in paths:
    print(f"=== File: {p} ===")
    if not os.path.exists(p):
        print("Does not exist!")
        continue
    with open(p, 'r', encoding='utf-8') as f:
        content = f.read()
    
    # Let's find updateBudgetEstimate or calculateEstimate function
    match = re.search(r'function\s+updateBudgetEstimate[\s\S]+?\}[\r\n]\s*\}', content)
    if match:
        print(match.group(0))
    else:
        # Just search for budget / multiplier text around the estimator code
        lines = content.splitlines()
        for idx, line in enumerate(lines):
            if 'multiplier' in line.lower() or 'base' in line.lower() or 'budget' in line.lower():
                if 'estimator' in ''.join(lines[max(0, idx-10):idx]):
                    print(f"{idx}: {line}")
    print()
