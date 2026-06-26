import sqlite3
import os

dbs = [
    'longwaysoftronix_v2/lspl_main_v2.sqlite',
    'lspl.xyz_v2/lspl_academy_v2.sqlite',
    'lsxpl_v2/lsxpl_ai_v2.sqlite'
]

base_dir = r"c:\Users\pawan\Downloads\lspl-claude\lspl"

for db_rel in dbs:
    db_path = os.path.join(base_dir, db_rel)
    if os.path.exists(db_path):
        conn = sqlite3.connect(db_path)
        cursor = conn.cursor()
        cursor.execute("INSERT OR REPLACE INTO settings (key, value) VALUES ('admin_slug', 'admin')")
        conn.commit()
        conn.close()
        print(f"Reset admin_slug to 'admin' in {db_rel}")
