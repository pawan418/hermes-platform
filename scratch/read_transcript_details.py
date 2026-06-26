import json

log_path = r"C:\Users\pawan\.gemini\antigravity\brain\7889f20b-5c51-4cb2-9e93-351f92de00a2\.system_generated\logs\transcript.jsonl"

with open(log_path, "r", encoding="utf-8") as f:
    for line in f:
        try:
            data = json.loads(line)
            idx = data.get("step_index")
            if idx >= 6700:
                # print summary of the step
                source = data.get("source")
                type_ = data.get("type")
                content = data.get("content") or ""
                tool_calls = data.get("tool_calls")
                
                print(f"--- STEP {idx} | {source} | {type_} ---")
                if content:
                    print(f"Content: {content[:300]}...")
                if tool_calls:
                    print(f"Tool calls: {json.dumps(tool_calls)}")
        except Exception as e:
            pass
