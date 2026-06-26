import urllib.request

base_url = "http://127.0.0.1:8000"

def check_fixes():
    # 1. Check style path on lspl.xyz_v2
    print("Checking stylesheet resolution for lspl.xyz_v2...")
    r = urllib.request.urlopen(f"{base_url}/lspl.xyz_v2/")
    html = r.read().decode("utf-8")
    assert r.status == 200
    
    # Check that style.css resolves with the correct folder path prefix
    expected_style_ref = 'href="/lspl.xyz_v2/style.css"'
    if expected_style_ref in html:
        print("   [PASS] Stylesheet path is correct: /lspl.xyz_v2/style.css")
    else:
        # Find where it links style.css
        style_match = [line for line in html.splitlines() if "style.css" in line]
        print(f"   [FAIL] Stylesheet path: {style_match}")
        assert False, "style.css path did not resolve to /lspl.xyz_v2/style.css"

    # 2. Check session notices on login page
    print("Checking for session_start notices on login page...")
    r_admin = urllib.request.urlopen(f"{base_url}/lspl.xyz_v2/admin")
    html_admin = r_admin.read().decode("utf-8")
    assert r_admin.status == 200
    
    if "session_start()" in html_admin or "Ignoring session_start()" in html_admin:
        print("   [FAIL] Found session_start notice on login page!")
        print(html_admin[:1000])
        assert False, "session_start notice detected"
    else:
        print("   [PASS] No session_start notices detected on login page")

try:
    check_fixes()
    print("\nALL FIXES VERIFIED SUCCESSFULLY!")
except AssertionError as e:
    print(f"\nVERIFICATION FAILED! AssertionError: {e}")
except Exception as e:
    print(f"\nVERIFICATION FAILED! Exception: {e}")
