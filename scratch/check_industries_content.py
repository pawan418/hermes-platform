import sqlite3
import os

dbs = {
    'enterprise': r'c:\Users\pawan\Downloads\lspl-claude\lspl\longwaysoftronix_v2\lspl_main_v2.sqlite',
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
        cursor.execute("SELECT id, title, slug, description, length(content), substr(content, 1, 100) FROM industries;")
        rows = cursor.fetchall()
        for r in rows:
            print(f"  ID: {r[0]}, Title: {r[1]}, Slug: {r[2]}")
            print(f"    Desc: {r[3]}")
            print(f"    Content Length: {r[4]}")
            print(f"    Content Prefix: {r[5]}")
        conn.close()
    except Exception as e:
        print(f"Error: {e}")
    print()
