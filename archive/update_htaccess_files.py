import os
import sys

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8')

print("Updating .htaccess files...")

htaccess_files = [
    'longwaysoftronix_v2/.htaccess',
    'lspl.xyz_v2/.htaccess',
    'lsxpl_v2/.htaccess'
]

rule_text = """
# Route /estimator to /estimator.php
RewriteRule ^estimator/?$ estimator.php [L,QSA]
"""

for fn in htaccess_files:
    if not os.path.exists(fn):
        print(f"File not found: {fn}")
        continue
        
    with open(fn, 'r', encoding='utf-8') as f:
        content = f.read()
        
    # Check if the rule is already present
    if 'estimator.php' in content:
        print(f"[{fn}] Rule already exists.")
        continue
        
    # Append the rule right after the blog rule, or at the end
    target = 'RewriteRule ^blog/?$ blog.php [L,QSA]'
    if target in content:
        content = content.replace(target, target + rule_text)
        print(f"[{fn}] Injected estimator rule after blog rule.")
    else:
        # Append at the end
        content += rule_text
        print(f"[{fn}] Appended estimator rule to end of file.")
        
    with open(fn, 'w', encoding='utf-8') as f:
        f.write(content)

print(".htaccess updates done.")
