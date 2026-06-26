import os

dirs_to_search = [
    r"C:\xampp",
    r"C:\laragon",
    r"C:\wamp",
    r"C:\php",
    r"C:\Program Files",
    r"C:\Program Files (x86)",
    r"C:\Users\pawan\AppData\Local",
    r"C:\Users\pawan\AppData\Roaming"
]

print("Searching for php.exe in directories...")
found = False
for d in dirs_to_search:
    if not os.path.exists(d):
        continue
    print(f"Scanning {d}...")
    for root, dirs, files in os.walk(d):
        if "php.exe" in files:
            print(f"FOUND: {os.path.join(root, 'php.exe')}")
            found = True
            break
        # Don't recurse too deep in massive system folders
        if root.count(os.sep) - d.count(os.sep) > 3:
            dirs.clear()

if not found:
    print("Search completed, php.exe not found in standard paths.")
