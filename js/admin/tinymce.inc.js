/**
 * 2007-2016 PrestaShop
 *
 * thirty bees is an extension to the PrestaShop e-commerce software developed by PrestaShop SA
 * Copyright (C) 2017-2024 thirty bees
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
 *  @copyright 2017-2024 thirty bees
 *  @copyright 2007-2016 PrestaShop SA
 *  @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

/* global window, tinyMCE, tinymce_override_config, ad, iso */

function tinySetup(config) {
  if (typeof tinyMCE === 'undefined') {
    setTimeout(function () {
      tinySetup(config);
    }, 100);
    return;
  }

  if (!config) {
    config = {};
  }

  if (typeof config['editor_selector'] !== 'undefined') {
    config.selector = '.' + config['editor_selector'];
  }

  let defaultConfig = {
    selector: ".rte",
    plugins: "colorpicker link image paste pagebreak table contextmenu filemanager table code media autoresize textcolor anchor directionality codemirror",
    browser_spellcheck: true,
    toolbar1: "code,|,bold,italic,underline,strikethrough,|,alignleft,aligncenter,alignright,alignfull,rtl,ltr,formatselect,|,blockquote,colorpicker,pasteword,|,bullist,numlist,|,outdent,indent,|,link,unlink,|,anchor,|,media,image",
    toolbar2: "",
    rel_list: [
        { title: 'noopener nofollow', value: 'noopener nofollow' }
    ],
    external_filemanager_path: ad + "/filemanager/",
    filemanager_title: "File manager",
    external_plugins: { "filemanager": ad + "/filemanager/plugin.min.js" },
    language: iso,
    skin: "prestashop",
    statusbar: false,
    relative_urls: false,
    convert_urls: false,
    entity_encoding: "raw",
    extended_valid_elements: "em[class|name|id]",
    valid_children: "+*[*]",
    valid_elements: "*[*]",
    
    // Prevents empty <p></p> generation fix
    forced_root_block: false,  // Prevents automatically wrapping content in <p> tags
    force_br_newlines: false,  // Prevents <br> from being inserted when pressing Enter
    force_p_newlines: true,  // Ensures that new lines are wrapped in <p> tags
    convert_newlines_to_brs: false,  // Prevents new lines from being converted into <br>
    
    menu: {
      edit: { title: 'Edit', items: 'undo redo | cut copy paste | selectall' },
      insert: { title: 'Insert', items: 'media image link | pagebreak' },
      view: { title: 'View', items: 'visualaid' },
      format: {
        title: 'Format',
        items: 'bold italic underline strikethrough superscript subscript | formats | removeformat'
      },
      table: { title: 'Table', items: 'inserttable tableprops deletetable | cell row column' },
      tools: { title: 'Tools', items: 'code' }
    },
    autoresize_min_height: 100,
    codemirror: {
      indentOnInit: true,
      path: 'codemirror-5.65',
      config: {
        lineNumbers: true,
      },
      width: 1200,
      height: 600,
      saveCursorPosition: false,
    },
    init_instance_callback: function (editor) {
      editor.on('PostProcess', function (e) {
        e.content = e.content.replace(/\s*\/>/g, '>');
      });
    },
  };

  // allow extending default config
  if (typeof window['tinymce_override_config'] !== 'undefined') {
    defaultConfig = {
      ...defaultConfig,
      ...window['tinymce_override_config']
    };
  }

  config = {
    ...defaultConfig,
    ...config
  };

  tinyMCE.init(config);
}
