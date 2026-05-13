<?php

function __($key) {
    static $currentLang = null;
    global $_lang_data;

    if ($currentLang === null) {
        $currentLang = isset($_COOKIE['edc_lang']) && $_COOKIE['edc_lang'] === 'bn' ? 'bn' : 'en';
    }

    return isset($_lang_data[$key][$currentLang])
        ? $_lang_data[$key][$currentLang]
        : $key;
}

function currentLang() {
    return isset($_COOKIE['edc_lang']) && $_COOKIE['edc_lang'] === 'bn' ? 'bn' : 'en';
}

function setLang($l) {
    setcookie('edc_lang', $l === 'bn' ? 'bn' : 'en', time() + 86400 * 365, '/');
    $_COOKIE['edc_lang'] = $l === 'bn' ? 'bn' : 'en';
}

// ── Theme functions ──
function currentTheme() {
    $valid = ['light', 'dark', 'purple'];
    $theme = isset($_COOKIE['edc_theme']) ? $_COOKIE['edc_theme'] : 'light';
    return in_array($theme, $valid) ? $theme : 'light';
}

function setTheme($t) {
    $valid = ['light', 'dark', 'purple'];
    $theme = in_array($t, $valid) ? $t : 'light';
    setcookie('edc_theme', $theme, time() + 86400 * 365, '/');
    $_COOKIE['edc_theme'] = $theme;
}

// Handle toggle via GET param
if (isset($_GET['theme'])) {
    setTheme($_GET['theme']);
    $redirectUrl = strtok($_SERVER['REQUEST_URI'], '?');
    $params = $_GET;
    unset($params['theme']);
    if (!empty($params)) {
        $redirectUrl .= '?' . http_build_query($params);
    }
    header('Location: ' . $redirectUrl);
    exit;
}

// Handle lang via GET param
if (isset($_GET['lang'])) {
    setLang($_GET['lang']);
    $redirectUrl = strtok($_SERVER['REQUEST_URI'], '?');
    $params = $_GET;
    unset($params['lang']);
    if (!empty($params)) {
        $redirectUrl .= '?' . http_build_query($params);
    }
    header('Location: ' . $redirectUrl);
    exit;
}

$_lang_data = [];

// ── Global ──
$_lang_data['app_name'] = ['en' => 'EDC Monitoring System', 'bn' => 'ইডিসি মনিটরিং সিস্টেম'];
$_lang_data['app_subtitle'] = ['en' => 'ISP Monitoring System', 'bn' => 'আইএসপি মনিটরিং সিস্টেম'];
$_lang_data['dashboard'] = ['en' => 'Dashboard', 'bn' => 'ড্যাশবোর্ড'];
$_lang_data['login'] = ['en' => 'Sign In', 'bn' => 'সাইন ইন'];
$_lang_data['logout'] = ['en' => 'Logout', 'bn' => 'লগআউট'];
$_lang_data['save'] = ['en' => 'Save', 'bn' => 'সংরক্ষণ'];
$_lang_data['cancel'] = ['en' => 'Cancel', 'bn' => 'বাতিল'];
$_lang_data['delete'] = ['en' => 'Delete', 'bn' => 'মুছুন'];
$_lang_data['edit'] = ['en' => 'Edit', 'bn' => 'সম্পাদনা'];
$_lang_data['create'] = ['en' => 'Create', 'bn' => 'তৈরি করুন'];
$_lang_data['update'] = ['en' => 'Update', 'bn' => 'হালনাগাদ'];
$_lang_data['search'] = ['en' => 'Search', 'bn' => 'অনুসন্ধান'];
$_lang_data['clear'] = ['en' => 'Clear', 'bn' => 'পরিষ্কার'];
$_lang_data['actions'] = ['en' => 'Actions', 'bn' => 'কার্যক্রম'];
$_lang_data['status'] = ['en' => 'Status', 'bn' => 'স্থিতি'];
$_lang_data['name'] = ['en' => 'Name', 'bn' => 'নাম'];
$_lang_data['type'] = ['en' => 'Type', 'bn' => 'ধরন'];
$_lang_data['email'] = ['en' => 'Email', 'bn' => 'ইমেইল'];
$_lang_data['password'] = ['en' => 'Password', 'bn' => 'পাসওয়ার্ড'];
$_lang_data['username'] = ['en' => 'Username', 'bn' => 'ব্যবহারকারীর নাম'];
$_lang_data['full_name'] = ['en' => 'Full Name', 'bn' => 'পূর্ণ নাম'];
$_lang_data['welcome'] = ['en' => 'Welcome', 'bn' => 'স্বাগতম'];
$_lang_data['loading'] = ['en' => 'Loading...', 'bn' => 'লোড হচ্ছে...'];
$_lang_data['no_data'] = ['en' => 'No data found.', 'bn' => 'কোনো তথ্য পাওয়া যায়নি।'];
$_lang_data['toggle_lang'] = ['en' => 'বাংলা', 'bn' => 'English'];

// ── Login ──
$_lang_data['invalid_credentials'] = ['en' => 'Invalid username or password.', 'bn' => 'ভুল ব্যবহারকারীর নাম বা পাসওয়ার্ড।'];
$_lang_data['login_title'] = ['en' => 'Login', 'bn' => 'লগইন'];
$_lang_data['default_creds'] = ['en' => 'Default Admin: admin / admin123', 'bn' => 'ডিফল্ট অ্যাডমিন: admin / admin123'];

// ── Dashboard ──
$_lang_data['admin_dashboard'] = ['en' => 'Admin Dashboard', 'bn' => 'অ্যাডমিন ড্যাশবোর্ড'];
$_lang_data['vendor_dashboard'] = ['en' => 'Vendor Dashboard', 'bn' => 'ভেন্ডর ড্যাশবোর্ড'];
$_lang_data['total_institutions'] = ['en' => 'Total Institutions', 'bn' => 'মোট প্রতিষ্ঠান'];
$_lang_data['online'] = ['en' => 'Online', 'bn' => 'অনলাইন'];
$_lang_data['offline'] = ['en' => 'Offline', 'bn' => 'অফলাইন'];
$_lang_data['unknown'] = ['en' => 'Unknown', 'bn' => 'অজানা'];
$_lang_data['all_institutions'] = ['en' => 'All Institutions', 'bn' => 'সকল প্রতিষ্ঠান'];
$_lang_data['my_institutions'] = ['en' => 'My Institutions', 'bn' => 'আমার প্রতিষ্ঠান'];
$_lang_data['recent_activity'] = ['en' => 'Recent Activity', 'bn' => 'সাম্প্রতিক কার্যকলাপ'];
$_lang_data['sync_now'] = ['en' => 'Sync Now', 'bn' => 'এখন সিঙ্ক করুন'];
$_lang_data['last_checked'] = ['en' => 'Last checked', 'bn' => 'সর্বশেষ পরীক্ষিত'];
$_lang_data['never'] = ['en' => 'Never', 'bn' => 'কখনও নয়'];
$_lang_data['syncing'] = ['en' => 'Syncing...', 'bn' => 'সিঙ্ক হচ্ছে...'];

// ── Institutions ──
$_lang_data['manage_institutions'] = ['en' => 'Manage Institutions', 'bn' => 'প্রতিষ্ঠান ব্যবস্থাপনা'];
$_lang_data['new_institution'] = ['en' => '+ New Institution', 'bn' => '+ নতুন প্রতিষ্ঠান'];
$_lang_data['institution_name'] = ['en' => 'Institution Name', 'bn' => 'প্রতিষ্ঠানের নাম'];
$_lang_data['pppoe_user'] = ['en' => 'PPPoE Username', 'bn' => 'PPPoE ব্যবহারকারীর নাম'];
$_lang_data['ip_address'] = ['en' => 'IP Address', 'bn' => 'আইপি ঠিকানা'];
$_lang_data['thana'] = ['en' => 'Thana / Upazila', 'bn' => 'থানা / উপজেলা'];
$_lang_data['union'] = ['en' => 'Union / Ward', 'bn' => 'ইউনিয়ন / ওয়ার্ড'];
$_lang_data['vendor'] = ['en' => 'Vendor', 'bn' => 'ভেন্ডর'];
$_lang_data['assigned_vendor'] = ['en' => 'Assigned Vendor', 'bn' => 'নির্ধারিত ভেন্ডর'];
$_lang_data['check'] = ['en' => 'Check', 'bn' => 'পরীক্ষা'];
$_lang_data['institution_created'] = ['en' => 'Institution created.', 'bn' => 'প্রতিষ্ঠান তৈরি হয়েছে।'];
$_lang_data['institution_updated'] = ['en' => 'Institution updated.', 'bn' => 'প্রতিষ্ঠান হালনাগাদ হয়েছে।'];
$_lang_data['institution_deleted'] = ['en' => 'Institution deleted.', 'bn' => 'প্রতিষ্ঠান মুছে ফেলা হয়েছে।'];
$_lang_data['select_vendor'] = ['en' => '-- Select Vendor --', 'bn' => '-- ভেন্ডর নির্বাচন --'];
$_lang_data['all_vendors'] = ['en' => 'All Vendors', 'bn' => 'সকল ভেন্ডর'];
$_lang_data['all_types'] = ['en' => 'All Types', 'bn' => 'সব ধরন'];
$_lang_data['all_thanas'] = ['en' => 'All Thanas', 'bn' => 'সব থানা'];
$_lang_data['all_status'] = ['en' => 'All Status', 'bn' => 'সব স্থিতি'];
$_lang_data['govt'] = ['en' => 'Government', 'bn' => 'সরকারি'];
$_lang_data['private'] = ['en' => 'Private', 'bn' => 'বেসরকারি'];
$_lang_data['others'] = ['en' => 'Others', 'bn' => 'অন্যান্য'];

// ── Vendors ──
$_lang_data['manage_vendors'] = ['en' => 'Manage Vendors', 'bn' => 'ভেন্ডর ব্যবস্থাপনা'];
$_lang_data['new_vendor'] = ['en' => '+ New Vendor', 'bn' => '+ নতুন ভেন্ডর'];
$_lang_data['vendor_created'] = ['en' => 'Vendor created successfully.', 'bn' => 'ভেন্ডর সফলভাবে তৈরি হয়েছে।'];
$_lang_data['vendor_updated'] = ['en' => 'Vendor updated successfully.', 'bn' => 'ভেন্ডর সফলভাবে হালনাগাদ হয়েছে।'];
$_lang_data['vendor_toggled'] = ['en' => 'Vendor status toggled.', 'bn' => 'ভেন্ডরের স্থিতি পরিবর্তন করা হয়েছে।'];
$_lang_data['username_exists'] = ['en' => 'Username already exists.', 'bn' => 'ব্যবহারকারীর নাম ইতিমধ্যে বিদ্যমান।'];
$_lang_data['institutions_count'] = ['en' => 'Institutions', 'bn' => 'প্রতিষ্ঠান'];
$_lang_data['active'] = ['en' => 'Active', 'bn' => 'সক্রিয়'];
$_lang_data['inactive'] = ['en' => 'Inactive', 'bn' => 'নিষ্ক্রিয়'];
$_lang_data['activate'] = ['en' => 'Activate', 'bn' => 'সক্রিয় করুন'];
$_lang_data['deactivate'] = ['en' => 'Deactivate', 'bn' => 'নিষ্ক্রিয় করুন'];
$_lang_data['view_institutions'] = ['en' => 'View Inst.', 'bn' => 'প্রতিষ্ঠান দেখুন'];
$_lang_data['pw_keep'] = ['en' => '(leave blank to keep current)', 'bn' => '(বর্তমান রাখতে ফাঁকা রাখুন)'];
$_lang_data['pw_required'] = ['en' => '(required for new)', 'bn' => '(নতুনের জন্য প্রয়োজনীয়)'];

// ── Logs ──
$_lang_data['activity_logs'] = ['en' => 'Activity Logs', 'bn' => 'কার্যকলাপ লগ'];
$_lang_data['status_change_history'] = ['en' => 'Status Change History', 'bn' => 'স্থিতি পরিবর্তনের ইতিহাস'];
$_lang_data['institution'] = ['en' => 'Institution', 'bn' => 'প্রতিষ্ঠান'];
$_lang_data['timestamp'] = ['en' => 'Timestamp', 'bn' => 'সময়'];
$_lang_data['no_logs'] = ['en' => 'No logs found.', 'bn' => 'কোনো লগ পাওয়া যায়নি।'];
$_lang_data['no_activity'] = ['en' => 'No activity yet.', 'bn' => 'এখনো কোনো কার্যকলাপ নেই।'];

// ── Settings ──
$_lang_data['mt_settings'] = ['en' => 'MikroTik Settings', 'bn' => 'মিক্রোটিক সেটিংস'];
$_lang_data['mt_connection'] = ['en' => 'MikroTik Router Connection', 'bn' => 'মিক্রোটিক রাউটার সংযোগ'];
$_lang_data['router_ip'] = ['en' => 'Router IP Address', 'bn' => 'রাউটারের আইপি ঠিকানা'];
$_lang_data['api_port'] = ['en' => 'API Port', 'bn' => 'এপিআই পোর্ট'];
$_lang_data['api_username'] = ['en' => 'API Username', 'bn' => 'এপিআই ব্যবহারকারীর নাম'];
$_lang_data['api_password'] = ['en' => 'API Password', 'bn' => 'এপিআই পাসওয়ার্ড'];
$_lang_data['check_interval'] = ['en' => 'Check Interval (seconds)', 'bn' => 'পরীক্ষার ব্যবধান (সেকেন্ড)'];
$_lang_data['settings_saved'] = ['en' => 'MikroTik settings saved.', 'bn' => 'মিক্রোটিক সেটিংস সংরক্ষিত হয়েছে।'];
$_lang_data['ip_user_required'] = ['en' => 'IP and Username are required.', 'bn' => 'আইপি এবং ব্যবহারকারীর নাম প্রয়োজন।'];
$_lang_data['test_connection'] = ['en' => 'Test Connection', 'bn' => 'সংযোগ পরীক্ষা'];
$_lang_data['testing'] = ['en' => 'Testing...', 'bn' => 'পরীক্ষা হচ্ছে...'];
$_lang_data['connection_ok'] = ['en' => 'Connection successful!', 'bn' => 'সংযোগ সফল!'];
$_lang_data['connection_fail'] = ['en' => 'Connection failed', 'bn' => 'সংযোগ ব্যর্থ'];
$_lang_data['cron_setup'] = ['en' => 'Cron Job Setup', 'bn' => 'ক্রন জব সেটআপ'];

// ── Filters ──
$_lang_data['filter_search'] = ['en' => 'Search by name, PPPoE user, vendor...', 'bn' => 'নাম, PPPoE, ভেন্ডর দ্বারা খুঁজুন...'];
$_lang_data['filter_search_vendor'] = ['en' => 'Search by name, PPPoE, thana, union...', 'bn' => 'নাম, PPPoE, থানা, ইউনিয়ন দ্বারা খুঁজুন...'];

// ── Access ──
$_lang_data['access_denied'] = ['en' => 'Access denied: Admin only.', 'bn' => 'প্রবেশাধিকার অস্বীকৃত: শুধুমাত্র অ্যাডমিন।'];
$_lang_data['access_denied_vendor'] = ['en' => 'Access denied: Vendor only.', 'bn' => 'প্রবেশাধিকার অস্বীকৃত: শুধুমাত্র ভেন্ডর।'];

// ── Sidebar ──
$_lang_data['nav_vendors'] = ['en' => 'Vendors', 'bn' => 'ভেন্ডর'];
$_lang_data['nav_institutions'] = ['en' => 'Institutions', 'bn' => 'প্রতিষ্ঠান'];
$_lang_data['nav_logs'] = ['en' => 'Activity Logs', 'bn' => 'কার্যকলাপ লগ'];
$_lang_data['nav_mt_settings'] = ['en' => 'MT Settings', 'bn' => 'এমটি সেটিংস'];

// ── Other ──
$_lang_data['no_inst_assigned'] = ['en' => 'No institutions assigned to you yet.', 'bn' => 'আপনার জন্য এখনও কোনো প্রতিষ্ঠান নির্ধারিত হয়নি।'];
$_lang_data['no_institutions'] = ['en' => 'No institutions found.', 'bn' => 'কোনো প্রতিষ্ঠান পাওয়া যায়নি।'];
$_lang_data['no_vendors'] = ['en' => 'No vendors yet.', 'bn' => 'এখনো কোনো ভেন্ডর নেই।'];
$_lang_data['no_logs_yet'] = ['en' => 'No logs yet.', 'bn' => 'এখনো কোনো লগ নেই।'];
$_lang_data['view_all'] = ['en' => 'View All', 'bn' => 'সব দেখুন'];

// ── Theme ──
$_lang_data['theme'] = ['en' => 'Theme', 'bn' => 'থিম'];
$_lang_data['theme_light'] = ['en' => 'Light', 'bn' => 'লাইট'];
$_lang_data['theme_dark'] = ['en' => 'Dark', 'bn' => 'ডার্ক'];
$_lang_data['theme_purple'] = ['en' => 'Purple', 'bn' => 'পার্পল'];
