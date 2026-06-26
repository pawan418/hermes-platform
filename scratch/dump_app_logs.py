import subprocess

pid = "4778"
try:
    output = subprocess.check_output(f"C:\\Users\\pawan\\AppData\\Local\\Android\\Sdk\\platform-tools\\adb.exe logcat -d --pid={pid}", shell=True, text=True)
except Exception as e:
    print(f"Error running adb logcat: {e}")
    exit(1)

lines = output.splitlines()
print(f"Total log lines for PID {pid}: {len(lines)}")

keywords = ["exception", "error", "failed", "connect", "api.php", "get_data", "http", "system.err", "retrofit", "gson", "serialization"]

found = 0
for i, line in enumerate(lines):
    if any(kw in line.lower() for kw in keywords):
        print(f"[{i}] {line}")
        found += 1

print(f"\nFound {found} matching lines.")
