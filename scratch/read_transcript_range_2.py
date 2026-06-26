import json

log_path = r"C:\Users\pawan\.gemini\antigravity\brain\7889f20b-5c51-4cb2-9e93-351f92de00a2\.system_generated\logs\transcript.jsonl"

with open(log_path, "r", encoding="utf-8") as f:
    for line in f:
        try:
            data = json.loads(line)
            idx = data.get("step_index")
            if 6700 <= idx <= 6800:
                print(f"[{idx}] {data.get('source')} | {data.get('type')}")
                content = data.get("content") or ""
                if content:
                    print(f"  Content: {content[:300].strip()}...")
                tool_calls = data.get("tool_calls")
                if tool_calls:
                    for tc in tool_calls:
                        print(f"    Tool: {tc.get('name')}({json.dumps(tc.get('args'))})")
        except Exception as e:
            pass
