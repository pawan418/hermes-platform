import os

search_paths = [
    r"C:\Program Files",
    r"C:\Program Files (x86)",
    os.path.expandvars(r"%LOCALAPPDATA%"),
    r"C:\ProgramData",
    r"C:\tools",
    r"C:\jdk"
]

found = []

for base_path in search_paths:
    if not os.path.exists(base_path):
        continue
    print(f"Scanning {base_path}...")
    for root, dirs, files in os.walk(base_path):
        # Limit depth to 5
        depth = root.count(os.sep) - base_path.count(os.sep)
        if depth > 4:
            dirs[:] = []  # Don't descend further
            continue
        
        if "java.exe" in files:
            java_path = os.path.join(root, "java.exe")
            print(f"Found java: {java_path}")
            found.append(java_path)
            
        # Avoid scanning giant directories like WindowsApps or epic games if we hit them
        if any(bad in root.lower() for bad in ["windowsapps", "epic games", "steam", "packages", "cache", "temp"]):
            dirs[:] = []
            continue

if found:
    print(f"\nSearch complete. Found {len(found)} Java executables.")
else:
    print("\nSearch complete. No Java executables found.")
