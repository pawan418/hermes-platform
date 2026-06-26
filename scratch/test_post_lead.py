import urllib.request
import json
import sqlite3

url = "http://localhost:8000/api.php?action=submit_lead"
payload = {
    "portal": "enterprise",
    "name": "Android Test Lead",
    "email": "android@example.com",
    "phone": "9876543210",
    "service_selected": "Web Designing & UI/UX",
    "duration_selected": "",
    "budget": "$10,000",
    "message": "Testing native lead submission from Android client.",
    "type": "General"
}

headers = {
    "Content-Type": "application/json"
}

req = urllib.request.Request(url, data=json.dumps(payload).encode('utf-8'), headers=headers, method='POST')

try:
    with urllib.request.urlopen(req) as response:
        html = response.read().decode('utf-8')
        print("API Response:", html)
        
        # Verify in database
        conn = sqlite3.connect('longwaysoftronix_v2/lspl_main_v2.sqlite')
        c = conn.cursor()
        c.execute("SELECT * FROM leads ORDER BY id DESC LIMIT 1")
        row = c.fetchone()
        print("\nLatest Lead in SQLite Database:")
        print(row)
        conn.close()
except Exception as e:
    print("Error:", e)
