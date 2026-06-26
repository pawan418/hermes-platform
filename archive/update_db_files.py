import sqlite3
import os
import sys

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8')

print("Updating database tables and db.php seeders...")

dbs = {
    'longwaysoftronix_v2': 'longwaysoftronix_v2/lspl_main_v2.sqlite',
    'lspl.xyz_v2': 'lspl.xyz_v2/lspl_academy_v2.sqlite',
    'lsxpl_v2': 'lsxpl_v2/lsxpl_ai_v2.sqlite'
}

# 1. Update SQLite tables
for name, path in dbs.items():
    if not os.path.exists(path):
        print(f"DB not found: {path}")
        continue
    
    conn = sqlite3.connect(path)
    cursor = conn.cursor()
    
    try:
        # Update header_menu_items
        cursor.execute("UPDATE header_menu_items SET custom_url = 'estimator.php' WHERE custom_url IN ('index.php#estimator', '#estimator')")
        print(f"[{name}] Updated {cursor.rowcount} header menu items.")
        
        # Update footer_items
        cursor.execute("UPDATE footer_items SET custom_url = 'estimator.php' WHERE custom_url IN ('index.php#estimator', '#estimator')")
        print(f"[{name}] Updated {cursor.rowcount} footer items.")
        
        conn.commit()
    except Exception as e:
        print(f"[{name}] Error updating tables: {e}")
    finally:
        conn.close()

# 2. Update db.php seeder arrays and resolve_url helper
db_files = [
    'longwaysoftronix_v2/db.php',
    'lspl.xyz_v2/db.php',
    'lsxpl_v2/db.php'
]

for db_path in db_files:
    if not os.path.exists(db_path):
        print(f"File not found: {db_path}")
        continue
        
    with open(db_path, 'r', encoding='utf-8') as f:
        content = f.read()
    
    # Replace the seeder value 'index.php#estimator' or '#estimator' in the seeder data arrays
    # (Since we saw the exact lines, we can safely replace all occurrences of 'index.php#estimator' with 'estimator.php' inside arrays)
    updated_content = content.replace("'index.php#estimator'", "'estimator.php'")
    updated_content = updated_content.replace('"index.php#estimator"', '"estimator.php"')
    
    # Replace inside resolve_url function:
    # We find where:
    # if ($url === 'index.php') {
    #     return $base_path;
    # }
    # is defined, and add the estimator.php mapping right after it.
    target_resolve = """        if ($url === 'index.php') {
            return $base_path;
        }"""
    
    replacement_resolve = """        if ($url === 'index.php') {
            return $base_path;
        }
        if ($url === 'estimator.php') {
            return $base_path . 'estimator';
        }"""
        
    if target_resolve in updated_content:
        updated_content = updated_content.replace(target_resolve, replacement_resolve)
        print(f"[{db_path}] Injected estimator clean URL rule into resolve_url.")
    else:
        # Check with unix newlines
        target_resolve_unix = target_resolve.replace('\r\n', '\n')
        if target_resolve_unix in updated_content:
            updated_content = updated_content.replace(target_resolve_unix, replacement_resolve.replace('\r\n', '\n'))
            print(f"[{db_path}] Injected estimator clean URL rule into resolve_url (unix newlines).")
        else:
            print(f"[{db_path}] Warning: Could not locate resolve_url target.")
            
    with open(db_path, 'w', encoding='utf-8') as f:
        f.write(updated_content)
    print(f"[{db_path}] Seeder code updated successfully.")

print("Database and seeder updates done.")
