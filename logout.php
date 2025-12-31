<?php
require_once __DIR__ . '/includes/functions.php';

destroySession();
setFlashMessage('success', 'You have successfully logged out.');
redirectBase('/login.php');
