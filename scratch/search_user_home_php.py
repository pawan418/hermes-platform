import os

start_dir = r"C:\Users\pawan"
print("Scanning C:\\Users\\pawan for php.exe...")
found = False
for root, dirs, files in os.walk(start_dir):
    if "php.exe" in files:
        print(f"FOUND: {os.path.join(root, 'php.exe')}")
        found = True
        break
    if root.count(os.sep) - start_dir.count(os.sep) > 4:
        dirs.clear()

if not found:
    print("Search completed, php.exe not found in C:\\Users\\pawan.")
