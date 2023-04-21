/**
 * plugin.js
 *
 * Copyright, Alberto Peripolli
 * Released under Creative Commons Attribution-NonCommercial 3.0 Unported License.
 *
 * Contributing: https://github.com/trippo/ResponsiveFilemanager
 */
tinymce.PluginManager.add('filemanager', function(editor) {

	tinymce.activeEditor.settings.file_browser_callback = filemanager;
	
	function filemanager (id, value, type, win) {
		const baseUrl = editor.settings.external_filemanager_path.replace(/\/\/filemanager/, '\/filemanager');
		const url = new URL(baseUrl + 'dialog.php');
		url.searchParams.append('lang', editor.settings.language);

		if (type === 'image') {
			url.searchParams.append('type', 1);
		} else if (type === 'media') {
			url.searchParams.append('type', 3);
		} else {
			url.searchParams.append('type', 2);
		}

		let title = 'File Manager';
		if (typeof editor.settings.filemanager_title !== "undefined" && editor.settings.filemanager_title)  {
			title = editor.settings.filemanager_title;
		}
		url.searchParams.append('title', title);

		if (typeof editor.settings.filemanager_sort_by !== "undefined" && editor.settings.filemanager_sort_by) {
			url.searchParams.append('sort_by', editor.settings.filemanager_sort_by);
		}

		if (typeof editor.settings.filemanager_descending !== "undefined" && editor.settings.filemanager_descending) {
			url.searchParams.append('descending', editor.settings.filemanager_descending);
		}

		tinymce.activeEditor.windowManager.open({
				title: title,
				file: url.href,
				width: 860,
				height: 570,
				resizable: true,
				maximizable: true,
				inline: 1
			}, {
			setUrl: function (url) {
				var fieldElm = win.document.getElementById(id);
				fieldElm.value = editor.convertURL(url);
				if ("fireEvent" in fieldElm) {
					fieldElm.fireEvent("onchange")
				} else {
					var evt = document.createEvent("HTMLEvents");
					evt.initEvent("change", false, true);
					fieldElm.dispatchEvent(evt);
				}
			}
		});
	}
	return false;
});
