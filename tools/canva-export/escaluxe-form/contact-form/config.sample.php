<?php
// Copy this file to config.php (same folder) and fill it in. config.php holds a password:
// never commit it or send it around. PHP files are run, not shown, so the web server
// won't reveal it; the included .htaccess / the nginx rule in README.txt also block it.
return [
    // where messages go (one address, or several)
    'to'          => ['erika@escaluxeglobal.com'],

    // the mailbox the website sends from (use a real mailbox on your domain)
    'from_email'  => 'website@escaluxe.com',
    'from_name'   => 'Escaluxe Website',

    // SMTP login for that mailbox (your email host's settings)
    'smtp_host'   => 'smtp.example.com',
    'smtp_port'   => 587,
    'smtp_secure' => 'tls',          // 'tls' (port 587), 'ssl' (port 465) or '' (none)
    'smtp_user'   => 'website@escaluxe.com',
    'smtp_pass'   => 'CHANGE-ME',

    // spam limit: messages allowed from one connection per 10 minutes
    'max_per_10_min' => 5,
];
