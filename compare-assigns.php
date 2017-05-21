<?php

include_once('config/defines.inc.php');
include_once('config/autoload.php');

// Fake a number of classes just to allow loading an array mentioning them.
class Smarty_Variable {
  static function __set_state($a) {
    return $a;
  }
}
class Category {
  static function __set_state($a) {
    return $a;
  }
}
class Link {
  static function __set_state($a) {
    return $a;
  }
}
class Manufacturer {
  static function __set_state($a) {
    return $a;
  }
}
class Product {
  static function __set_state($a) {
    return $a;
  }
}

eval('$set16 = '.file_get_contents('config/debug').';');
eval('$set17 = '.file_get_contents('../www.reprap-diy.com/shop2/config/debug').';');

print("# Keys in 16, but not in 17:\n");
foreach (array_keys($set16) as $key) {
  // Ignore fields assigned by modules.
  if (in_array($key, [
                        'ctheme',  // ctconfiguration
                     ])) {
    continue;
  }

  // Ignore fields introduced for module retrocompatibility.
  if (in_array($key, [
                        'request',
                        'shop_name',
                        'meta_title',
                        'meta_description',
                        'PS_CATALOG_MODE',
                     ])) {
    continue;
  }

  if (!array_key_exists($key, $set17)) {
    print("$key\n");
  }
}

print("\n# Keys in 17, but not in 16:\n");
foreach (array_keys($set17) as $key) {
  if (!array_key_exists($key, $set16)) {
    print("$key\n");
  }
}

print("\n");
