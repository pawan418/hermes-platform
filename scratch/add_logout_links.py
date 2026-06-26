import os

base_dir = r"c:\Users\pawan\Downloads\lspl-claude\lspl"

# 1. Update lspl.xyz_v2/admin.php
filepath_xyz = os.path.join(base_dir, "lspl.xyz_v2", "admin.php")
if os.path.exists(filepath_xyz):
    with open(filepath_xyz, "r", encoding="utf-8") as f:
        content = f.read()
    
    target = (
        "            <?php if (in_array('account', $allowed_tabs)):\n"
        "            $current_tab === 'account' ? 'active' : ''; ?>\">\n"
        "                <a href=\"?tab=account\"><i data-lucide=\"shield\"></i> Security Keys</a>\n"
        "            </li>\n"
        "            <?php endif; ?>\n"
        "        </ul>"
    )
    # Wait, let's look at lines 722-728 exactly in the file to make sure it matches
    # Let's inspect it carefully. In the file, the line was:
    # 722:             <?php if (in_array('account', $allowed_tabs)): ?>
    # 723:             <li class="admin-nav-item <?php echo $current_tab === 'account' ? 'active' : ''; ?>">
    # 724:                 <a href="?tab=account"><i data-lucide="shield"></i> Security Keys</a>
    # 725:             </li>
    # 726:             <?php endif; ?>
    # 727:         </ul>
    
    target = (
        '            <?php if (in_array(\'account\', $allowed_tabs)): ?>\n'
        '            <li class="admin-nav-item <?php echo $current_tab === \'account\' ? \'active\' : \'\'; ?>">\n'
        '                <a href="?tab=account"><i data-lucide="shield"></i> Security Keys</a>\n'
        '            </li>\n'
        '            <?php endif; ?>\n'
        '        </ul>'
    )
    
    replacement = (
        '            <?php if (in_array(\'account\', $allowed_tabs)): ?>\n'
        '            <li class="admin-nav-item <?php echo $current_tab === \'account\' ? \'active\' : \'\'; ?>">\n'
        '                <a href="?tab=account"><i data-lucide="shield"></i> Security Keys</a>\n'
        '            </li>\n'
        '            <?php endif; ?>\n'
        '            <li class="admin-nav-item" style="margin-top: auto;">\n'
        '                <a href="?action=logout" style="color: hsl(var(--destructive));"><i data-lucide="log-out" style="width: 18px; height: 18px;"></i> Log Out</a>\n'
        '            </li>\n'
        '        </ul>'
    )
    
    if target in content:
        content = content.replace(target, replacement)
        with open(filepath_xyz, "w", encoding="utf-8") as f:
            f.write(content)
        print("Successfully added logout link to lspl.xyz_v2/admin.php")
    else:
        print("Target string not found in lspl.xyz_v2/admin.php")

# 2. Update lsxpl_v2/admin.php
filepath_xpl = os.path.join(base_dir, "lsxpl_v2", "admin.php")
if os.path.exists(filepath_xpl):
    with open(filepath_xpl, "r", encoding="utf-8") as f:
        content = f.read()
        
    target = (
        '            <?php if (in_array(\'account\', $allowed_tabs)): ?>\n'
        '            <li class="admin-nav-item <?php echo $current_tab === \'account\' ? \'active\' : \'\'; ?>">\n'
        '                <a href="?tab=account"><i data-lucide="shield" style="width: 18px; height: 18px;"></i> Account Vault</a>\n'
        '            </li>\n'
        '            <?php endif; ?>\n'
        '        </ul>'
    )
    
    replacement = (
        '            <?php if (in_array(\'account\', $allowed_tabs)): ?>\n'
        '            <li class="admin-nav-item <?php echo $current_tab === \'account\' ? \'active\' : \'\'; ?>">\n'
        '                <a href="?tab=account"><i data-lucide="shield" style="width: 18px; height: 18px;"></i> Account Vault</a>\n'
        '            </li>\n'
        '            <?php endif; ?>\n'
        '            <li class="admin-nav-item" style="margin-top: auto;">\n'
        '                <a href="?action=logout" style="color: hsl(var(--destructive));"><i data-lucide="log-out" style="width: 18px; height: 18px;"></i> Log Out</a>\n'
        '            </li>\n'
        '        </ul>'
    )
    
    if target in content:
        content = content.replace(target, replacement)
        with open(filepath_xpl, "w", encoding="utf-8") as f:
            f.write(content)
        print("Successfully added logout link to lsxpl_v2/admin.php")
    else:
        print("Target string not found in lsxpl_v2/admin.php")
