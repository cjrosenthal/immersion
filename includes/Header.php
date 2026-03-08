<?php

class Header {
    
    public static function render($title = 'Immersion') {
        require_once BASE_PATH . '/lib/SessionManagement.php';
        require_once BASE_PATH . '/lib/UserManagement.php';
        require_once BASE_PATH . '/lib/ImageManagement.php';
        require_once BASE_PATH . '/lib/SettingsManagement.php';
        
        $isLoggedIn = SessionManagement::isLoggedIn();
        $user = null;
        $isAdmin = false;
        
        if ($isLoggedIn) {
            $user = UserManagement::getUserById(SessionManagement::getUserId());
            $isAdmin = $user && $user['is_admin'];
        }
        
        $siteTitle = SettingsManagement::get('site_title', 'Immersion');
        $flash = Application::getFlashMessage();
        
        ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title); ?></title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <nav class="main-nav">
        <div class="nav-container">
            <div class="nav-left">
                <a href="/index.php" class="site-title"><?php echo htmlspecialchars($siteTitle); ?></a>
            </div>
            <?php if ($isLoggedIn): ?>
            <div class="nav-right">
                <div class="user-menu">
                    <button class="user-menu-button" onclick="toggleUserMenu()">
                        <?php if ($user['image_id']): ?>
                            <img src="<?php echo htmlspecialchars(ImageManagement::getImageUrl($user['image_id'])); ?>" 
                                 alt="Profile" class="profile-photo-small">
                        <?php else: 
                            $initials = '';
                            if ($user['first_name']) {
                                $initials .= strtoupper(substr($user['first_name'], 0, 1));
                            }
                            if ($user['last_name']) {
                                $initials .= strtoupper(substr($user['last_name'], 0, 1));
                            }
                            if (empty($initials)) {
                                $initials = strtoupper(substr($user['email'], 0, 2));
                            }
                        ?>
                            <div class="profile-initials-small"><?php echo htmlspecialchars($initials); ?></div>
                        <?php endif; ?>
                        <span class="user-name"><?php echo htmlspecialchars($user['first_name'] ?? $user['email']); ?></span>
                        <span class="dropdown-arrow">▼</span>
                    </button>
                    <div class="user-menu-dropdown" id="userMenuDropdown">
                        <a href="/profile.php">Profile</a>
                        <?php if ($isAdmin): ?>
                        <div class="submenu">
                            <a href="#" class="submenu-toggle">Admin ▶</a>
                            <div class="submenu-content">
                                <a href="/admin/users/list.php">Users</a>
                                <a href="/admin/threads/list.php">Threads</a>
                                <a href="/admin/settings.php">Settings</a>
                            </div>
                        </div>
                        <?php endif; ?>
                        <a href="/logout.php">Logout</a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </nav>
    
    <?php if ($flash): ?>
    <div class="flash-message flash-<?php echo htmlspecialchars($flash['type']); ?>">
        <?php echo htmlspecialchars($flash['message']); ?>
    </div>
    <?php endif; ?>
    
    <main class="main-content">
<?php
    }
}
