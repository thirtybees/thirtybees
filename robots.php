<?php
require_once __DIR__ . '/config/config.inc.php';

$shop = Context::getContext()->shop;
$id_shop = $shop->id;

$robots_content = Db::getInstance()->getValue(
    'SELECT `robots_content`
     FROM `' . _DB_PREFIX_ . 'robots`
     WHERE `id_shop` = ' . (int) $id_shop
);

header('Content-Type: text/plain');
header('X-Content-Type-Options: nosniff');

if ($robots_content !== false) {
    echo $robots_content;
} else {
    PrestaShopLogger::addLog(
        'robots.txt content is missing for shop ID: ' . $id_shop,
        3, // Severity level: 3 = Error
        null,
        'Shop',
        $id_shop
    );
    
    // Default fallback robots.txt
    echo "User-agent: *\n";
    echo "Allow: /modules/*.css\n";
    echo "Allow: /modules/*.js\n";
    echo "Disallow: */classes/\n";
    echo "Disallow: */config/\n";
    echo "Disallow: */download/\n";
    echo "Disallow: */mails/\n";
    echo "Disallow: */translations/\n";
    echo "Disallow: */tools/\n";
}
