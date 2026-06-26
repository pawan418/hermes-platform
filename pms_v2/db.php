<?php
// pms_v2/db.php - Database connection and automatic initialization for PMS

$db_file = __DIR__ . '/pms_v2.sqlite';
$is_new = !file_exists($db_file);

try {
    $db = new PDO("sqlite:" . $db_file);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Enable foreign keys
    $db->exec("PRAGMA foreign_keys = ON;");
    
    // Dynamic schema updates for existing databases
    try {
        $has_is_verified = false;
        $cols = $db->query("PRAGMA table_info(invoices)")->fetchAll();
        foreach ($cols as $col) {
            if ($col['name'] === 'is_verified') {
                $has_is_verified = true;
                break;
            }
        }
        if (!$has_is_verified) {
            $db->exec("ALTER TABLE invoices ADD COLUMN is_verified INTEGER DEFAULT 0");
        }
    } catch (Exception $schema_err) {
        // Table probably doesn't exist yet in brand new DB
    }
    
    // Seed Account Manager if missing
    try {
        $chk_user = $db->prepare("SELECT id FROM users WHERE username = ?");
        $chk_user->execute(['manager']);
        if (!$chk_user->fetch()) {
            $manager_pw_hash = password_hash('manager123', PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
            $stmt->execute(['manager', $manager_pw_hash, 'account_manager']);
        }
    } catch (Exception $seed_err) {
        // Table probably doesn't exist yet
    }
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

if ($is_new) {
    try {
        $db->beginTransaction();
        
        // 1. Create Users Table
        $db->exec("CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL,
            role TEXT NOT NULL
        );");
        
        // 2. Create Clients Table
        $db->exec("CREATE TABLE IF NOT EXISTS clients (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            email TEXT NOT NULL,
            phone TEXT,
            company TEXT,
            address TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );");
        
        // 3. Create Projects Table
        $db->exec("CREATE TABLE IF NOT EXISTS projects (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            client_id INTEGER NOT NULL,
            title TEXT NOT NULL,
            description TEXT,
            status TEXT CHECK(status IN ('Planning', 'In Progress', 'Completed', 'On Hold')) DEFAULT 'Planning',
            total_budget REAL DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
        );");
        
        // 4. Create Proposals Table
        $db->exec("CREATE TABLE IF NOT EXISTS proposals (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            project_id INTEGER NOT NULL,
            content TEXT NOT NULL,
            status TEXT CHECK(status IN ('Draft', 'Sent', 'Accepted', 'Rejected')) DEFAULT 'Draft',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
        );");
        
        // 5. Create Estimates Table
        $db->exec("CREATE TABLE IF NOT EXISTS estimates (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            project_id INTEGER NOT NULL,
            items TEXT NOT NULL, -- JSON formatted array
            total_amount REAL DEFAULT 0,
            status TEXT CHECK(status IN ('Draft', 'Sent', 'Accepted')) DEFAULT 'Draft',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
        );");
        
        $db->exec("CREATE TABLE IF NOT EXISTS invoices (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            invoice_number TEXT UNIQUE NOT NULL,
            amount REAL NOT NULL,
            status TEXT CHECK(status IN ('Unpaid', 'Paid')) DEFAULT 'Unpaid',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            sent_at DATETIME,
            is_verified INTEGER DEFAULT 0
        );");
        
        // 7. Create Milestones Table
        $db->exec("CREATE TABLE IF NOT EXISTS milestones (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            project_id INTEGER NOT NULL,
            title TEXT NOT NULL,
            description TEXT,
            amount REAL DEFAULT 0,
            due_date TEXT,
            status TEXT CHECK(status IN ('Pending', 'Completed')) DEFAULT 'Pending',
            completed_at DATETIME,
            invoice_id INTEGER,
            FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
            FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE SET NULL
        );");
        
        // 8. Create Email Logs Table
        $db->exec("CREATE TABLE IF NOT EXISTS email_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            recipient TEXT NOT NULL,
            subject TEXT NOT NULL,
            body TEXT NOT NULL,
            attachment_type TEXT, -- 'Estimate', 'Invoice', 'Proposal'
            sent_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );");
        
        // 9. Create Settings Table
        $db->exec("CREATE TABLE IF NOT EXISTS settings (
            key TEXT PRIMARY KEY,
            value TEXT
        );");
        
        // Seed default administrator account
        $admin_pw_hash = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
        $stmt->execute(['admin', $admin_pw_hash, 'administrator']);
        
        // Seed default settings
        $stmt_settings = $db->prepare("INSERT INTO settings (key, value) VALUES (?, ?)");
        $default_settings = [
            'site_title' => 'LSXPL Project Hub',
            'company_name' => 'Longway Softronix Pvt. Ltd.',
            'contact_email' => 'billing@longwaysoftronix.com',
            'company_logo_url' => '',
            'ai_provider' => 'Disabled',
            'ai_model_id' => '',
            'ai_api_key' => '',
            'ai_endpoint' => '',
            'gemini_api_key' => '',
            'smtp_host' => '',
            'smtp_port' => '587',
            'smtp_user' => '',
            'smtp_pass' => ''
        ];
        foreach ($default_settings as $k => $v) {
            $stmt_settings->execute([$k, $v]);
        }
        
        $db->commit();
    } catch (Exception $e) {
        $db->rollBack();
        unlink($db_file); // Remove the corrupt file
        die("Database seeding failed: " . $e->getMessage());
    }
}
?>
