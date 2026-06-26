import sqlite3
import os

dbs = {
    'enterprise': r'c:\Users\pawan\Downloads\lspl-claude\lspl\longwaysoftronix_v2\lspl_main_v2.sqlite',
    'academy': r'c:\Users\pawan\Downloads\lspl-claude\lspl\lspl.xyz_v2\lspl_academy_v2.sqlite',
    'ai': r'c:\Users\pawan\Downloads\lspl-claude\lspl\lsxpl_v2\lsxpl_ai_v2.sqlite'
}

for name, path in dbs.items():
    print(f"=== DB: {name} ({path}) ===")
    if not os.path.exists(path):
        print("File does not exist!")
        continue
    try:
        conn = sqlite3.connect(path)
        cursor = conn.cursor()
        cursor.execute("SELECT name FROM sqlite_master WHERE type='table';")
        tables = cursor.fetchall()
        print(f"Tables: {[t[0] for t in tables]}")
        for t in tables:
            tbl_name = t[0]
            print(f"  Table: {tbl_name}")
            cursor.execute(f"PRAGMA table_info({tbl_name});")
            info = cursor.fetchall()
            for col in info:
                print(f"    Col: {col[1]} ({col[2]})")
            # Query row count
            cursor.execute(f"SELECT COUNT(*) FROM {tbl_name};")
            count = cursor.fetchone()[0]
            print(f"    Rows: {count}")
        conn.close()
    except Exception as e:
        print(f"Error: {e}")
    print()
