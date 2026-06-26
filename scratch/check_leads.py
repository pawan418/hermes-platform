import sqlite3
import os
import sys

sys.stdout.reconfigure(encoding='utf-8')

dbs = {
    'enterprise': r'c:\Users\pawan\Downloads\lspl-claude\lspl\longwaysoftronix_v2\lspl_main_v2.sqlite',
    'academy': r'c:\Users\pawan\Downloads\lspl-claude\lspl\lspl.xyz_v2\lspl_academy_v2.sqlite',
    'ai': r'c:\Users\pawan\Downloads\lspl-claude\lspl\lsxpl_v2\lsxpl_ai_v2.sqlite'
}

for name, path in dbs.items():
    print(f"=== Leads in DB: {name} ===")
    if not os.path.exists(path):
        print("Database file does not exist!")
        continue
    try:
        conn = sqlite3.connect(path)
        cursor = conn.cursor()
        cursor.execute("SELECT * FROM leads ORDER BY id DESC;")
        rows = cursor.fetchall()
        
        # Get column names
        cursor.execute("PRAGMA table_info(leads);")
        cols = [col[1] for col in cursor.fetchall()]
        
        print(f"Columns: {cols}")
        print(f"Total leads: {len(rows)}")
        for r in rows:
            print(dict(zip(cols, r)))
        conn.close()
    except Exception as e:
        print(f"Error querying table leads: {e}")
    print()
