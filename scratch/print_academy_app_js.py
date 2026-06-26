import re

path = r'c:\Users\pawan\Downloads\lspl-claude\lspl\lspl.xyz_v2\app.js'
with open(path, 'r', encoding='utf-8') as f:
    content = f.read()

match = re.search(r'function\s+updateBudgetEstimate[\s\S]+?\}[\r\n]\s*\}', content)
if match:
    print(match.group(0))
else:
    # Let's search for lines containing updateBudgetEstimate
    lines = content.splitlines()
    for idx, line in enumerate(lines):
        if 'updateBudgetEstimate' in line or 'calculateEstimate' in line or 'budgetVal' in line or 'feeVal' in line:
            print(f"=== {idx} ===")
            print('\n'.join(lines[max(0, idx-10):min(len(lines), idx+30)]))
