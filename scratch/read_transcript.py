import json

log_path = r"C:\Users\pawan\.gemini\antigravity\brain\7889f20b-5c51-4cb2-9e93-351f92de00a2\.system_generated\logs\transcript.jsonl"

with open(log_path, "r", encoding="utf-8") as f:
    for line in f:
        try:
            data = json.loads(line)
            if data.get("type") == "USER_INPUT":
                print(f"[{data.get('step_index')}] {data.get('source')}: {data.get('content')}")
        except Exception as e:
            pass
