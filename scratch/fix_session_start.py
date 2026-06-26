import os

base_dir = r"c:\Users\pawan\Downloads\lspl-claude\lspl"
portals = ["longwaysoftronix_v2", "lspl.xyz_v2", "lsxpl_v2"]
filenames = ["admin.php", "login.php", "logout.php"]

for portal in portals:
    for filename in filenames:
        filepath = os.path.join(base_dir, portal, filename)
        if os.path.exists(filepath):
            with open(filepath, "r", encoding="utf-8") as f:
                content = f.read()
            
            # Replace session_start(); with a check
            # We want to match session_start(); exactly, but handle optional whitespace
            target = "session_start();"
            replacement = (
                "if (session_status() === PHP_SESSION_NONE) {\n"
                "    session_start();\n"
                "}"
            )
            
            if target in content:
                content = content.replace(target, replacement)
                with open(filepath, "w", encoding="utf-8") as f:
                    f.write(content)
                print(f"Patched session_start in {portal}/{filename}")
            else:
                # Check if it was already patched
                if "session_status()" in content:
                    print(f"Already patched in {portal}/{filename}")
                else:
                    print(f"session_start(); not found in {portal}/{filename}")
