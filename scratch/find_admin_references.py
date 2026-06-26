import os

portal_dir = r"c:\Users\pawan\Downloads\lspl-claude\lspl\lspl.xyz_v2"
target_files = ["admin.php", "login.php", "logout.php", "db.php"]

for filename in target_files:
    file_path = os.path.join(portal_dir, filename)
    if not os.path.exists(file_path):
        continue
    
    print(f"=== File: {filename} ===")
    with open(file_path, "r", encoding="utf-8") as f:
        lines = f.readlines()
    
    for i, line in enumerate(lines):
        # Search for any references to admin.php, login.php, logout.php
        if any(term in line for term in ("admin.php", "login.php", "logout.php")):
            print(f"Line {i+1}: {line.strip()}")
    print()
