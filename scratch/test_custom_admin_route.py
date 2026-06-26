import urllib.request
import urllib.parse
import urllib.error
import re
from http.cookiejar import CookieJar

base_url = "http://127.0.0.1:8000"

# Custom opener to handle redirects and cookies
class NoRedirectHandler(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, req, fp, code, msg, headers, newurl):
        # Prevent automatic redirect
        return None

def test_portal(portal_name, default_title):
    print(f"\n======================================")
    print(f"Testing Portal: {portal_name}")
    print(f"======================================")
    
    # Setup cookie jar and opener
    cj = CookieJar()
    cookie_processor = urllib.request.HTTPCookieProcessor(cj)
    
    # Opener that follows redirects automatically
    opener = urllib.request.build_opener(cookie_processor)
    # Opener that does NOT follow redirects (for testing redirect codes)
    no_redirect_opener = urllib.request.build_opener(cookie_processor, NoRedirectHandler())
    
    # 1. Accessing /admin (logged out) -> should render login screen
    login_url = f"{base_url}/{portal_name}/admin"
    req = urllib.request.Request(login_url)
    try:
        res = opener.open(req)
        status_code = res.status
        html = res.read().decode("utf-8")
    except urllib.error.HTTPError as e:
        status_code = e.code
        html = e.read().decode("utf-8")
        
    print(f"1. GET {login_url} -> Status: {status_code}")
    assert status_code == 200
    assert "username" in html or "Password" in html
    print("   [PASS] Login page rendered directly at /admin")
    
    # 2. Accessing direct .php paths -> should redirect to homepage
    req_direct_admin = urllib.request.Request(f"{base_url}/{portal_name}/admin.php")
    try:
        res_direct_admin = no_redirect_opener.open(req_direct_admin)
        status_code = res_direct_admin.status
        redirect_url = res_direct_admin.getheader("Location")
    except urllib.error.HTTPError as e:
        status_code = e.code
        redirect_url = e.headers.get("Location")
        
    print(f"2. GET direct admin.php -> Status: {status_code}")
    assert status_code in [302, 301]
    assert redirect_url == f"/{portal_name}/" or redirect_url == f"/{portal_name}"
    print("   [PASS] Direct admin.php access blocked & redirected to homepage")
    
    req_direct_login = urllib.request.Request(f"{base_url}/{portal_name}/login.php")
    try:
        res_direct_login = no_redirect_opener.open(req_direct_login)
        status_code = res_direct_login.status
        redirect_url = res_direct_login.getheader("Location")
    except urllib.error.HTTPError as e:
        status_code = e.code
        redirect_url = e.headers.get("Location")
        
    print(f"3. GET direct login.php -> Status: {status_code}")
    assert status_code in [302, 301]
    assert redirect_url == f"/{portal_name}/" or redirect_url == f"/{portal_name}"
    print("   [PASS] Direct login.php access blocked & redirected to homepage")

    # 4. Perform Login
    login_data = urllib.parse.urlencode({
        "username": "admin",
        "password": "admin123"
    }).encode("utf-8")
    
    req_login = urllib.request.Request(login_url, data=login_data, method="POST")
    try:
        res_login = no_redirect_opener.open(req_login)
        status_code = res_login.status
        target_url = res_login.getheader("Location")
    except urllib.error.HTTPError as e:
        status_code = e.code
        target_url = e.headers.get("Location")
        
    print(f"4. POST Login -> Status: {status_code}")
    assert status_code in [302, 301]
    print(f"   Redirect target: {target_url}")
    assert target_url == f"/{portal_name}/admin" or target_url == f"/{portal_name}/admin/"
    
    # Follow redirect to dashboard
    req_dash = urllib.request.Request(base_url + target_url)
    res_dash = opener.open(req_dash)
    html_dash = res_dash.read().decode("utf-8")
    print(f"   GET Dash -> Status: {res_dash.status}")
    assert res_dash.status == 200
    assert "Console" in html_dash or "Admin" in html_dash or "Manager" in html_dash
    print("   [PASS] Login successful & redirected to admin console")

    # 5. Fetch Settings Page to get existing settings values
    settings_url = f"{base_url}/{portal_name}/admin?tab=settings"
    req_settings = urllib.request.Request(settings_url)
    res_settings = opener.open(req_settings)
    html_settings = res_settings.read().decode("utf-8")
    assert res_settings.status == 200
    
    # Parse existing settings form fields using simple regex
    post_settings = {
        "action": "update_settings"
    }
    
    # Find all input tags
    input_matches = re.findall(r'<input\s+[^>]*name=["\'](settings\[[^"\']+\.?)["\'][^>]*>', html_settings)
    for name in input_matches:
        # Extract value if present
        val_match = re.search(r'value=["\']([^"\']*)["\']', re.search(r'<input\s+[^>]*name=["\']' + re.escape(name) + r'["\'][^>]*>', html_settings).group(0))
        val = val_match.group(1) if val_match else ""
        post_settings[name] = val
        
    # Find all textareas
    textarea_matches = re.finditer(r'<textarea\s+[^>]*name=["\'](settings\[[^"\']+\.?)["\'][^>]*>(.*?)</textarea>', html_settings, re.DOTALL)
    for m in textarea_matches:
        post_settings[m.group(1)] = m.group(2).strip()
        
    # Find all select options selected
    select_matches = re.finditer(r'<select\s+[^>]*name=["\'](settings\[[^"\']+\.?)["\'][^>]*>.*?</select>', html_settings, re.DOTALL)
    for m in select_matches:
        select_name = m.group(1)
        # Find which option is selected
        selected_match = re.search(r'<option\s+[^>]*value=["\']([^"\']*)["\'][^>]*selected', m.group(0))
        post_settings[select_name] = selected_match.group(1) if selected_match else ""
            
    print(f"   Loaded existing settings fields (count: {len(post_settings)-1})")
    
    # 6. Change admin_slug to 'xyz'
    post_settings["settings[admin_slug]"] = "xyz"
    
    update_data = urllib.parse.urlencode(post_settings).encode("utf-8")
    req_update = urllib.request.Request(settings_url, data=update_data, method="POST")
    try:
        res_update = no_redirect_opener.open(req_update)
        status_code = res_update.status
        new_redirect = res_update.getheader("Location")
    except urllib.error.HTTPError as e:
        status_code = e.code
        new_redirect = e.headers.get("Location")
        
    print(f"5. POST Update settings slug -> Status: {status_code}")
    assert status_code in [302, 301]
    print(f"   Redirect target after slug change: {new_redirect}")
    assert "xyz" in new_redirect
    print("   [PASS] Settings update redirected user to new slug URL!")
    
    # Verify that visiting the new slug works
    req_new_slug = urllib.request.Request(base_url + new_redirect)
    res_new_slug = opener.open(req_new_slug)
    html_new_slug = res_new_slug.read().decode("utf-8")
    print(f"6. GET new slug URL -> Status: {res_new_slug.status}")
    assert res_new_slug.status == 200
    assert "Settings updated successfully." in html_new_slug or "updated successfully." in html_new_slug
    print("   [PASS] Served admin panel correctly under the new secure slug!")
    
    # Verify that visiting /admin now serves the homepage instead of the admin panel (or 404s)
    req_old_slug = urllib.request.Request(f"{base_url}/{portal_name}/admin")
    try:
        res_old_slug = opener.open(req_old_slug)
        status_code = res_old_slug.status
        html_old_slug = res_old_slug.read().decode("utf-8")
    except urllib.error.HTTPError as e:
        status_code = e.code
        html_old_slug = e.read().decode("utf-8")
        
    print(f"7. GET old slug /admin -> Status: {status_code}")
    assert status_code in [200, 404]
    if status_code == 200:
        assert "Brand Console" not in html_old_slug and "Academy Console" not in html_old_slug and "Lab Console" not in html_old_slug
        assert "Admin Area" not in html_old_slug and "Secure Login" not in html_old_slug
    print("   [PASS] Accessing old slug /admin does not load admin panel anymore (homepage or 404)")

    # 7. Reset the slug back to 'admin'
    post_settings["settings[admin_slug]"] = "admin"
    reset_data = urllib.parse.urlencode(post_settings).encode("utf-8")
    new_settings_url = f"{base_url}/{portal_name}/xyz?tab=settings"
    req_reset = urllib.request.Request(new_settings_url, data=reset_data, method="POST")
    try:
        res_reset = no_redirect_opener.open(req_reset)
        status_code = res_reset.status
        reset_redirect = res_reset.getheader("Location")
    except urllib.error.HTTPError as e:
        status_code = e.code
        reset_redirect = e.headers.get("Location")
        
    print(f"8. Reset slug back to 'admin' -> Status: {status_code}")
    assert status_code in [302, 301]
    assert "admin" in reset_redirect
    print("   [PASS] Successfully reset admin_slug back to 'admin'")
    
    # Perform Logout
    logout_url = f"{base_url}/{portal_name}/admin?action=logout"
    req_logout = urllib.request.Request(logout_url)
    try:
        res_logout = no_redirect_opener.open(req_logout)
        status_code = res_logout.status
    except urllib.error.HTTPError as e:
        status_code = e.code
        
    print(f"9. GET Logout -> Status: {status_code}")
    assert status_code in [302, 301]
    print("   [PASS] Logout completed and redirected back to base site")

try:
    test_portal("longwaysoftronix_v2", "LSPL")
    test_portal("lspl.xyz_v2", "Academy")
    test_portal("lsxpl_v2", "AI Lab")
    print("\nALL PORTALS PASSED ALL CHECKS!")
except AssertionError as e:
    print(f"\nTEST FAILED! AssertionError: {e}")
except Exception as e:
    print(f"\nTEST FAILED! Exception: {e}")
