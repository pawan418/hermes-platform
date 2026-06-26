import json

log_path = r"C:\Users\pawan\.gemini\antigravity\brain\7889f20b-5c51-4cb2-9e93-351f92de00a2\.system_generated\logs\transcript.jsonl"

with open(log_path, "r", encoding="utf-8") as f:
    for line in f:
        try:
            data = json.loads(line)
            idx = data.get("step_index")
            if 6700 <= idx <= 6830:
                source = data.get("source")
                type_ = data.get("type")
                content = data.get("content") or ""
                tool_calls = data.get("tool_calls")
                
                print(f"[{idx}] {source} | {type_}")
                if type_ == "USER_INPUT":
                    print(f"  User request: {content.strip()}")
                elif type_ == "PLANNER_RESPONSE":
                    print(f"  Model response: {content[:300].strip()}...")
                    if tool_calls:
                        for tc in tool_calls:
                            print(f"    Tool: {tc.get('name')}({json.dumps(tc.get('args'))})")
                elif type_ in ["REPLACE_FILE_CONTENT", "WRITE_TO_FILE", "MULTI_REPLACE_FILE_CONTENT"]:
                    print(f"  File edit in {type_}")
        except Exception as e:
            pass
