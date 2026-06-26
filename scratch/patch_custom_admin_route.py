import os
import re

portals = [
    {
        "dir": "longwaysoftronix_v2",
        "session_prefix": "lspl",
        "header_setting": "5. Branding & Logo",
        "header_replacement_num": "6"
    },
    {
        "dir": "lspl.xyz_v2",
        "session_prefix": "lspl_xyz",
        "header_setting": "5. Logo Brand Assets",
        "header_replacement_num": "6"
    },
    {
        "dir": "lsxpl_v2",
        "session_prefix": "lsxpl",
        "header_setting": "5. Logo Brand Assets",
        "header_replacement_num": "6"
    }
]

base_dir = r"c:\Users\pawan\Downloads\lspl-claude\lspl"

def patch_admin_php(portal):
    filepath = os.path.join(base_dir, portal["dir"], "admin.php")
    if not os.path.exists(filepath):
        print(f"File not found: {filepath}")
        return

    with open(filepath, "r", encoding="utf-8") as f:
        content = f.read()

    # 1. Update session/auth check and inject dynamic path and logout handling at the top
    auth_check_pattern = r"(session_start\(\);\s+// Auth check\s+if \(!isset\(\$_SESSION\['" + portal["session_prefix"] + r"_admin_logged_in'\]\) \|\| \$_SESSION\['" + portal["session_prefix"] + r"_admin_logged_in'\] !== true\) \{\s+header\('Location: login\.php'\);\s+exit;\s+\})"
    
    replacement_auth = (
        "session_start();\n\n"
        "// Resolve base paths and URL\n"
        "$base_path = $base_path ?? (rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\\\') . '/');\n"
        "$admin_slug = $admin_slug ?? ($site['admin_slug'] ?? 'admin');\n"
        "$admin_url = $base_path . $admin_slug;\n\n"
        "// Handle logout\n"
        "if (isset($_GET['action']) && $_GET['action'] === 'logout') {\n"
        "    $_SESSION = [];\n"
        "    if (ini_get(\"session.use_cookies\")) {\n"
        "        $params = session_get_cookie_params();\n"
        "        setcookie(session_name(), '', time() - 42000,\n"
        "            $params[\"path\"], $params[\"domain\"],\n"
        "            $params[\"secure\"], $params[\"httponly\"]\n"
        "        );\n"
        "    }\n"
        "    session_destroy();\n"
        "    header('Location: ' . $base_path);\n"
        "    exit;\n"
        "}\n\n"
        "// Auth check\n"
        "if (!isset($_SESSION['" + portal["session_prefix"] + "_admin_logged_in']) || $_SESSION['" + portal["session_prefix"] + "_admin_logged_in'] !== true) {\n"
        "    include __DIR__ . '/login.php';\n"
        "    exit;\n"
        "}"
    )

    modified_content, count = re.subn(auth_check_pattern, replacement_auth, content)
    if count > 0:
        print(f"Patched top auth check in {portal['dir']}/admin.php")
    else:
        # Try a more generic match in case spacing differs
        print(f"Warning: Could not match exact top auth check pattern in {portal['dir']}/admin.php")

    # 2. Redirect on invalid tab
    invalid_tab_pattern = r"header\('Location: admin\.php\?tab=' \. \$allowed_tabs\[0\]\);"
    replacement_invalid_tab = "header('Location: ' . $admin_url . '?tab=' . $allowed_tabs[0]);"
    modified_content, count = re.subn(invalid_tab_pattern, replacement_invalid_tab, modified_content)
    if count > 0:
        print(f"Patched invalid tab redirect in {portal['dir']}/admin.php")

    # 3. Success message detection at the top
    success_init_pattern = r"\$success_message = '';\s+\$error_message = '';"
    replacement_success_init = (
        "$success_message = (isset($_GET['success']) && $_GET['success'] == '1') ? 'Settings updated successfully.' : '';\n"
        "$error_message = '';"
    )
    modified_content, count = re.subn(success_init_pattern, replacement_success_init, modified_content)
    if count > 0:
        print(f"Patched success message init in {portal['dir']}/admin.php")

    # 4. Settings update logic - validate and handle dynamic slug redirection
    settings_update_pattern = r"(elseif \(\$action === 'update_settings'\) \{[^}]+)(if \(\$error_message === ''\) \{[^}]+site_manager'\)[^}]+success_message = \"[^\"]+\";)"
    # Let's find update_settings block manually or using simple replace to be safe
    # Let's find: if (!in_array($user_role, ['administrator', 'site_manager'])) { throw new Exception('Unauthorized action.'); }
    # inside update_settings
    settings_header = "} elseif ($action === 'update_settings') {"
    settings_auth_check = "if (!in_array($user_role, ['administrator', 'site_manager'])) { throw new Exception('Unauthorized action.'); }"
    
    settings_auth_pos = modified_content.find(settings_header)
    if settings_auth_pos != -1:
        # Find the next auth check inside this elseif
        auth_pos = modified_content.find(settings_auth_check, settings_auth_pos)
        if auth_pos != -1:
            insertion_code = (
                "\n            $new_admin_slug = isset($_POST['settings']['admin_slug']) ? trim($_POST['settings']['admin_slug']) : '';\n"
                "            if ($new_admin_slug !== '') {\n"
                "                if (!preg_match('/^[a-zA-Z0-9\\-_]+$/', $new_admin_slug)) {\n"
                "                    throw new Exception(\"Custom admin path slug can only contain alphanumeric characters, dashes, and underscores.\");\n"
                "                }\n"
                "            }\n"
            )
            # Insert after the auth check
            insert_at = auth_pos + len(settings_auth_check)
            modified_content = modified_content[:insert_at] + insertion_code + modified_content[insert_at:]
            print(f"Patched update_settings POST handler validation in {portal['dir']}/admin.php")

    # Add redirect check if error_message is empty in update_settings
    success_msg_pos = modified_content.find('updated successfully.";', settings_auth_pos)
    if success_msg_pos != -1:
        # Find the next closing brace
        closing_brace_pos = modified_content.find('}', success_msg_pos)
        if closing_brace_pos != -1:
            redirect_code = (
                "\n                if ($new_admin_slug !== '' && $new_admin_slug !== $admin_slug) {\n"
                "                    $new_admin_url = $base_path . $new_admin_slug . '?tab=settings&success=1';\n"
                "                    header('Location: ' . $new_admin_url);\n"
                "                    exit;\n"
                "                }\n            "
            )
            modified_content = modified_content[:closing_brace_pos] + redirect_code + modified_content[closing_brace_pos:]
            print(f"Patched update_settings POST handler redirect in {portal['dir']}/admin.php")

    # 5. Insert input field in Settings form HTML
    # Find the header
    header_str = f'style="margin-top: 1.5rem; border-bottom: 1px dashed hsl(var(--border)); padding-bottom: 0.5rem; color: hsl(var(--primary));">{portal["header_setting"]}</h4>'
    header_pos = modified_content.find(header_str)
    if header_pos != -1:
        # We replace the header with the admin slug setting field and then the renamed branding header
        setting_field_html = (
            f'style="margin-top: 1.5rem; border-bottom: 1px dashed hsl(var(--border)); padding-bottom: 0.5rem; color: hsl(var(--primary));">5. Security & Admin Path Slug</h4>\n'
            f'                    \n'
            f'                    <div class="form-group">\n'
            f'                        <label for="admin_slug">Custom Admin Path Slug</label>\n'
            f'                        <input type="text" name="settings[admin_slug]" class="form-control" value="<?php echo htmlspecialchars($site[\'admin_slug\'] ?? \'admin\'); ?>" placeholder="e.g. admin, xyz, secure-panel" pattern="[a-zA-Z0-9\\-_]+" title="Only alphanumeric characters, dashes, and underscores are allowed." required>\n'
            f'                        <small style="display: block; margin-top: 0.25rem; color: var(--muted-foreground);">Changing this updates the admin URL immediately (e.g. <code>pawan</code> makes the admin page accessible at <code>/pawan</code>).</small>\n'
            f'                    </div>\n'
            f'\n'
            f'                    <h4 class="full-width" style="margin-top: 1.5rem; border-bottom: 1px dashed hsl(var(--border)); padding-bottom: 0.5rem; color: hsl(var(--primary));">{portal["header_replacement_num"]}. {portal["header_setting"].split(". ")[1]}</h4>'
        )
        modified_content = modified_content.replace(header_str, setting_field_html)
        print(f"Inserted Custom Admin Path Slug input field in Settings form of {portal['dir']}/admin.php")
    else:
        print(f"Warning: Could not find header '{portal['header_setting']}' in {portal['dir']}/admin.php")

    # 6. Replace all occurrences of admin.php? with ?
    # Let's do this safely by replacing 'admin.php?' with '?'
    modified_content, count = re.subn(r'admin\.php\?', '?', modified_content)
    print(f"Replaced {count} instances of admin.php? with ? in {portal['dir']}/admin.php")

    # 7. Replace the logout link target
    modified_content, count = re.subn(r'href="logout\.php"', 'href="?action=logout"', modified_content)
    print(f"Replaced {count} instances of logout.php links in {portal['dir']}/admin.php")

    with open(filepath, "w", encoding="utf-8") as f:
        f.write(modified_content)


def patch_login_php(portal):
    filepath = os.path.join(base_dir, portal["dir"], "login.php")
    if not os.path.exists(filepath):
        print(f"File not found: {filepath}")
        return

    with open(filepath, "r", encoding="utf-8") as f:
        content = f.read()

    # 1. Inject dynamic path and URL resolution right after session_start();
    session_start_str = "session_start();"
    session_start_pos = content.find(session_start_str)
    if session_start_pos != -1:
        path_resolution_code = (
            "\n\n"
            "// Resolve base paths and URL\n"
            "$base_path = $base_path ?? (rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\\\') . '/');\n"
            "if (!isset($admin_slug)) {\n"
            "    try {\n"
            "        $slug_q = $db->query(\"SELECT value FROM settings WHERE key = 'admin_slug' LIMIT 1\");\n"
            "        $slug_r = $slug_q->fetch();\n"
            "        $admin_slug = $slug_r ? $slug_r['value'] : 'admin';\n"
            "    } catch (Exception $e) {\n"
            "        $admin_slug = 'admin';\n"
            "    }\n"
            "}\n"
            "$admin_url = $base_path . $admin_slug;"
        )
        insert_at = session_start_pos + len(session_start_str)
        content = content[:insert_at] + path_resolution_code + content[insert_at:]
        print(f"Injected path resolution code in {portal['dir']}/login.php")

    # 2. Redirect to dynamic admin URL instead of admin.php
    content, count = re.subn(r"header\('Location: admin\.php'\);", "header('Location: ' . $admin_url);", content)
    print(f"Patched {count} redirect(s) in {portal['dir']}/login.php")

    # 3. Change form action to submit to empty string (self/current URL)
    content, count = re.subn(r'action="login\.php"', 'action=""', content)
    print(f"Patched form action in {portal['dir']}/login.php")

    with open(filepath, "w", encoding="utf-8") as f:
        f.write(content)

for portal in portals:
    print(f"\n--- Processing {portal['dir']} ---")
    patch_admin_php(portal)
    patch_login_php(portal)

print("\nDone!")
