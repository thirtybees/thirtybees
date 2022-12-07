<?php
/**
 * 2007-2016 PrestaShop
 *
 * thirty bees is an extension to the PrestaShop e-commerce software developed by PrestaShop SA
 * Copyright (C) 2017-2018 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://www.thirtybees.com for more information.
 *
 *  @author    thirty bees <contact@thirtybees.com>
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2017-2018 thirty bees
 *  @copyright 2007-2016 PrestaShop SA
 *  @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

// echo '<pre>';
// print_r($_POST);
// die;
include_once('../init.php');
$iso = Tools::getValue('iso');

if (Tools::isSubmit('submitTranslations')) {
    if (!file_exists('../langs/'.$iso.'/install.php')) {
        die('translation file does not exists');
    }
    $translated_content = include('../langs/'.$iso.'/install.php');
    unset($_POST['iso']);
    unset($_POST['submitTranslations']);
    foreach ($_POST as $post_key => $post_value) {
        if (!empty($post_value)) {
            $translated_content['translations'][my_urldecode($post_key)] = $post_value;
        }
    }
    $new_content = "<?php\nreturn array(\n";
    foreach ($translated_content as $key1 => $value1) {
        $new_content .= "\t'".just_quotes($key1)."' => array(\n";
        foreach ($value1 as $key2 => $value2) {
            $new_content .= "\t\t'".just_quotes($key2)."' => '".just_quotes($value2)."',\n";
        }
        $new_content .= "\t),\n";
    }
    $new_content .= ");";
    file_put_contents('../langs/'.$iso.'/install.php', $new_content);
    echo '<span class="label label-success">Translations Updated</span><br /><br />';
}

$regex = '/->l\(\'(.*[^\\\\])\'(, ?\'(.+)\')?(, ?(.+))?\)/U';
$dirs = ['classes', 'controllers', 'models', 'theme'];
$languages = scandir('../langs');
$files = $translations = $translations_source = [];
foreach ($dirs as $dir) {
    $files = array_merge($files, Tools::scandir('..', 'php', $dir, true));
    $files = array_merge($files, Tools::scandir('..', 'phtml', $dir, true));
}

foreach ($files as $file) {
    $content = file_get_contents('../'.$file);
    preg_match_all($regex, $content, $matches);
    $translations_source = array_merge($translations_source, $matches[1]);
}
$translations_source = array_map('stripslashes', $translations_source);

if ($iso && (file_exists('../langs/'.$iso.'/install.php'))) {
    $translated_content = include('../langs/'.$iso.'/install.php');
    $translations = $translated_content['translations'];
}

echo '
<html>
	<head>
		<meta http-equiv="Content-Type" content="application/xhtml+xml; charset=utf-8" />
		<link href="//netdna.bootstrapcdn.com/twitter-bootstrap/2.3.0/css/bootstrap-combined.min.css" rel="stylesheet">
		<style type="text/css">
			body {padding: 20px}
			input[type=text] {width:600px}
		</style>
	</head>
	<body>
		<form action="translate.php" method="post">
			<select name="iso" onchange="document.location = \'translate.php?iso=\'+this.value;">
				<option>- Choose your language -</option>';
foreach ($languages as $language) {
    if (file_exists('../langs/'.$language.'/install.php')) {
        echo '<option value="'.htmlspecialchars($language, ENT_COMPAT, 'utf-8').'" '.($iso == $language ? 'selected="selected"' : '').'>'.htmlspecialchars($language, ENT_NOQUOTES, 'utf-8').'</option>'."\n";
    }
}
echo '		</select>
			<table class="table table-bordered table-striped">
				<thead>
					<tr>
						<th>Source</th>
						<th>Your translation</th>
					</tr>
				</thead>
				<tbody>';
foreach ($translations_source as $translation_source) {
    echo '			<tr '.(!isset($translations[$translation_source]) ? 'class="error"' : '').'>
						<td>
							'.htmlspecialchars($translation_source, ENT_NOQUOTES, 'utf-8').'
						</td>
						<td>
							<input type="text" name="'.my_urlencode($translation_source).'"
								'.(isset($translations[$translation_source]) ? 'value="'.htmlspecialchars($translations[$translation_source], ENT_COMPAT, 'utf-8').'"' : '').'
							/>
						</td>
					</tr>';
}
echo '			</tbody>
			</table>
			<input type="submit" name="submitTranslations" class="btn btn-primary" />
		</form>
	</body>
</html>';

/**
 * @param string $s
 * @return string
 */
function just_quotes($s)
{
    return addcslashes($s, '\\\'');
}

/**
 * @param string $s
 * @return string
 */
function my_urlencode($s)
{
    return str_replace('.', '_dot_', urlencode($s));
}

/**
 * @param string $s
 * @return string
 */
function my_urldecode($s)
{
    return str_replace('_dot_', '.', urldecode($s));
}
