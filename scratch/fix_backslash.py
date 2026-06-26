import os

base_dir = r"c:\Users\pawan\Downloads\lspl-claude\lspl"
portals = ["longwaysoftronix_v2", "lspl.xyz_v2", "lsxpl_v2"]

for portal in portals:
    filepath = os.path.join(base_dir, portal, "admin.php")
    if os.path.exists(filepath):
        with open(filepath, "r", encoding="utf-8") as f:
            content = f.read()
        
        # Replace '/\' with '/\\'
        target = "rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/')"
        # Wait, let's look at the exact line in the file:
        # $base_path = $base_path ?? (rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\') . '/');
        
        target = "rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\') . '/')"
        replacement = "rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\\\') . '/')"
        
        if target in content:
            content = content.replace(target, replacement)
            with open(filepath, "w", encoding="utf-8") as f:
                f.write(content)
            print(f"Fixed backslash in {portal}/admin.php")
        else:
            # Let's try matching with double backslash to see if it's already there
            print(f"Target not found in {portal}/admin.php. Let's do a direct replace of '/\\') . '/')" )
            content = content.replace("'/\\') . '/'", "'/\\\\') . '/'")
            with open(filepath, "w", encoding="utf-8") as f:
                f.write(content)
            print(f"Direct replaced backslash in {portal}/admin.php")
