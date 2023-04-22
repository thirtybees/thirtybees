<?php
include('config/config.php');

if (isset($_POST['submit'])) {
    include('upload.php');
} else {

    $subdir = getSubDir(Tools::getValue('fldr'));

    if (! file_exists(FILE_MANAGER_BASE_DIR.$subdir)) {
        $subdir = '';
    }

    $cur_dir = FILE_MANAGER_UPLOAD_DIR . $subdir;
    $cur_path = FILE_MANAGER_BASE_DIR . $subdir;
    $thumbs_path = FILE_MANAGER_THUMB_BASE_DIR;

    if (!is_dir($thumbs_path.$subdir)) {
        create_folder(false, $thumbs_path.$subdir);
    }

    if (isset($_GET['popup'])) {
        $popup = $_GET['popup'];
    } else {
        $popup = 0;
    }
//Sanitize popup
    $popup = !!$popup;

    // resolve view type
    $view = isset($_GET['view'])
        ? setViewType((int)Tools::getValue('view', 0))
        : getViewType();

    $sort_by = isset($_GET['sort_by'])
        ? setSortBy(Tools::getValue('sort_by'))
        : getSortBy();

    $descending = isset($_GET['descending'])
        ? setDescending(Tools::getValue('descending') === 'true')
        : getDescending();

    if (isset($_GET['filter'])) {
        $filter = fix_filename($_GET['filter']);
    } else {
        $filter = '';
    }

    $lang = 'en';
    if (isset($_GET['lang']) && $_GET['lang'] != 'undefined' && $_GET['lang'] != '') {
        $lang = $_GET['lang'];
    }

    $language_file = 'lang/en.php';
    if ($lang !== 'en') {
        $path_parts = pathinfo($lang);
        if (is_readable('lang/'.$path_parts['basename'].'.php')) {
            $language_file = 'lang/'.$path_parts['basename'].'.php';
        } else {
            $lang = 'en';
        }
    }


    require_once $language_file;

    if (!isset($_GET['type'])) {
        $_GET['type'] = 0;
    }
    if (!isset($_GET['field_id'])) {
        $_GET['field_id'] = '';
    }

    $get_params = http_build_query(
        [
            'type' => Tools::safeOutput($_GET['type']),
            'lang' => Tools::safeOutput($lang),
            'popup' => $popup,
            'field_id' => isset($_GET['field_id']) ? (int)$_GET['field_id'] : '',
            'fldr' => ''
        ]
    );
    ?>

	<!DOCTYPE html>
	<html xmlns="https://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"/>
		<meta name="robots" content="noindex,nofollow">
		<title>Responsive FileManager</title>
		<link rel="shortcut icon" href="img/ico/favicon.ico">
		<link href="css/bootstrap.min.css" rel="stylesheet" type="text/css"/>
		<link href="css/bootstrap-responsive.min.css" rel="stylesheet" type="text/css"/>
		<link href="css/bootstrap-lightbox.min.css" rel="stylesheet" type="text/css"/>
		<link href="css/style.css" rel="stylesheet" type="text/css"/>
		<link href="css/dropzone.min.css" type="text/css" rel="stylesheet"/>
		<link href="css/jquery.contextMenu.min.css" rel="stylesheet" type="text/css"/>
		<link href="css/bootstrap-modal.min.css" rel="stylesheet" type="text/css"/>
		<link href="jPlayer/skin/blue.monday/jplayer.blue.monday.css" rel="stylesheet" type="text/css">
		<?php foreach (Media::getJqueryPath() as $jQueryPath) { ?>
		<script type="text/javascript" src="<?php echo $jQueryPath ?>"></script>
		<?php } ?>
		<script type="text/javascript" src="js/bootstrap.min.js"></script>
		<script type="text/javascript" src="js/bootstrap-lightbox.min.js"></script>
		<script type="text/javascript" src="js/dropzone.min.js"></script>
		<script type="text/javascript" src="js/jquery.touchSwipe.min.js"></script>
		<script type="text/javascript" src="js/modernizr.custom.js"></script>
		<script type="text/javascript" src="js/bootbox.min.js"></script>
		<script type="text/javascript" src="js/bootstrap-modal.min.js"></script>
		<script type="text/javascript" src="js/bootstrap-modalmanager.min.js"></script>
		<script type="text/javascript" src="jPlayer/jquery.jplayer.min.js"></script>
		<script type="text/javascript" src="js/imagesloaded.pkgd.min.js"></script>
		<script type="text/javascript" src="js/jquery.queryloader2.min.js"></script>
		<script src="js/jquery.ui.position.min.js" type="text/javascript"></script>
		<script src="js/jquery.contextMenu.min.js" type="text/javascript"></script>

		<script>
			var ext_img = new Array('<?php echo implode("','", getFileExtensions('image'))?>');
			var allowed_ext = new Array('<?php echo implode("','", getFileExtensions())?>');
			var loading_bar = true;
			//dropzone config
			Dropzone.options.myAwesomeDropzone = {
				dictInvalidFileType: "<?php echo lang_Error_extension; ?>",
				dictFileTooBig: "<?php echo lang_Error_Upload; ?>",
				dictResponseError: "SERVER ERROR",
				paramName: "file", // The name that will be used to transfer the file
				maxFilesize: <?php echo (Tools::getMaxUploadSize() / (1024 * 1024)); ?>, // MB
				url: "upload.php",
				accept: function (file, done) {
					var extension = file.name.split('.').pop();
					extension = extension.toLowerCase();
					if ($.inArray(extension, allowed_ext) > -1) {
						done();
					}
					else {
						done("<?php echo lang_Error_extension; ?>");
					}
				}
			};
		</script>
		<script type="text/javascript" src="js/include.min.js"></script>
	</head>
	<body>
	<input type="hidden" id="popup" value="<?php echo Tools::safeOutput($popup); ?>"/>
	<input type="hidden" id="view" value="<?php echo Tools::safeOutput($view); ?>"/>
	<input type="hidden" id="cur_dir" value="<?php echo Tools::safeOutput($cur_dir); ?>"/>
	<input type="hidden" id="cur_dir_thumb" value="<?php echo Tools::safeOutput($subdir); ?>"/>
	<input type="hidden" id="insert_folder_name" value="<?php echo Tools::safeOutput(lang_Insert_Folder_Name); ?>"/>
	<input type="hidden" id="new_folder" value="<?php echo Tools::safeOutput(lang_New_Folder); ?>"/>
	<input type="hidden" id="ok" value="<?php echo Tools::safeOutput(lang_OK); ?>"/>
	<input type="hidden" id="cancel" value="<?php echo Tools::safeOutput(lang_Cancel); ?>"/>
	<input type="hidden" id="rename" value="<?php echo Tools::safeOutput(lang_Rename); ?>"/>
	<input type="hidden" id="lang_duplicate" value="<?php echo Tools::safeOutput(lang_Duplicate); ?>"/>
	<input type="hidden" id="duplicate" value="1"/>
	<input type="hidden" id="base_url" value="<?php echo Tools::safeOutput(FILE_MANAGER_BASE_URL) ?>"/>
	<input type="hidden" id="base_url_true" value="<?php echo base_url(); ?>"/>
	<input type="hidden" id="fldr_value" value="<?php echo Tools::safeOutput($subdir); ?>"/>
	<input type="hidden" id="file_number_limit_js" value="<?php echo Tools::safeOutput(FILE_MANAGER_FILE_NUMBER_LIMIT_JS); ?>"/>
	<input type="hidden" id="descending" value="<?php echo $descending ? "true" : "false"; ?>"/>
	<input type="hidden" id="current_url" value="<?php echo str_replace(['&filter='.$filter], [''], "http://".$_SERVER['HTTP_HOST'].Tools::safeOutput($_SERVER['REQUEST_URI'])); ?>"/>
	<input type="hidden" id="lang_show_url" value="<?php echo Tools::safeOutput(lang_Show_url); ?>"/>
	<input type="hidden" id="lang_extract" value="<?php echo Tools::safeOutput(lang_Extract); ?>"/>
	<input type="hidden" id="lang_file_info" value="<?php echo fix_strtoupper(lang_File_info); ?>"/>
	<input type="hidden" id="lang_edit_image" value="<?php echo Tools::safeOutput(lang_Edit_image); ?>"/>

    <div class="uploader">
        <center>
            <button class="btn btn-inverse close-uploader">
                <i class="icon-backward icon-white"></i> <?php echo Tools::safeOutput(lang_Return_Files_List) ?></button>
        </center>
        <div class="space10"></div>
        <div class="space10"></div>
                    <form action="dialog.php" method="post" enctype="multipart/form-data" id="myAwesomeDropzone" class="dropzone">
                        <input type="hidden" name="path" value="<?php echo Tools::safeOutput($subfolder.$subdir); ?>"/>
                        <input type="hidden" name="path_thumb" value="<?php echo Tools::safeOutput($subfolder.$subdir); ?>"/>

                        <div class="fallback">
                            <?php echo lang_Upload_file ?>:<br/>
                            <input name="file" type="file"/>
                            <input type="hidden" name="fldr" value="<?php echo Tools::safeOutput($subdir); ?>"/>
                            <input type="hidden" name="view" value="<?php echo Tools::safeOutput($view); ?>"/>
                            <input type="hidden" name="type" value="<?php echo Tools::safeOutput($_GET['type']); ?>"/>
                            <input type="hidden" name="field_id" value="<?php echo (int)$_GET['field_id']; ?>"/>
                            <input type="hidden" name="popup" value="<?php echo Tools::safeOutput($popup); ?>"/>
                            <input type="hidden" name="lang" value="<?php echo Tools::safeOutput($lang); ?>"/>
                            <input type="hidden" name="filter" value="<?php echo Tools::safeOutput($filter); ?>"/>
                            <input type="submit" name="submit" value="<?php echo lang_OK ?>"/>
                        </div>
                    </form>
                    <div class="upload-help"><?php echo Tools::safeOutput(lang_Upload_base_help); ?></div>
            </div>
        </div>

    </div>

	<div class="container-fluid">

	<?php

    $class_ext = '';
    $src = '';

    if ($_GET['type'] == 1) {
        $apply = 'apply_img';
    } elseif ($_GET['type'] == 2) {
        $apply = 'apply_link';
    } elseif ($_GET['type'] == 0 && $_GET['field_id'] == '') {
        $apply = 'apply_none';
    } elseif ($_GET['type'] == 3) {
        $apply = 'apply_video';
    } else {
        $apply = 'apply';
    }

    $files = scandir(FILE_MANAGER_BASE_DIR.$subfolder.$subdir);
    $n_files = count($files);

    //php sorting
    $sorted = [];
    $current_folder = [];
    $prev_folder = [];
    foreach ($files as $k => $file) {
        if ($file == ".") {
            $current_folder = ['file' => $file];
        } elseif ($file == "..") {
            $prev_folder = ['file' => $file];
        } elseif (is_dir(FILE_MANAGER_BASE_DIR.$subfolder.$subdir.$file)) {
            $date = filemtime(FILE_MANAGER_BASE_DIR.$subfolder.$subdir.$file);
            $size = foldersize(FILE_MANAGER_BASE_DIR.$subfolder.$subdir.$file);
            $file_ext = lang_Type_dir;
            $sorted[$k] = ['file' => $file, 'date' => $date, 'size' => $size, 'extension' => $file_ext];
        } else {
            $file_path = FILE_MANAGER_BASE_DIR.$subfolder.$subdir.$file;
            $date = filemtime($file_path);
            $size = filesize($file_path);
            $file_ext = substr(strrchr($file, '.'), 1);
            $sorted[$k] = ['file' => $file, 'date' => $date, 'size' => $size, 'extension' => $file_ext];
        }
    }

    /**
     * @param array $x
     * @param array $y
     * @return int
     */
    function filenameSort($x, $y)
    {
        return strcmp((string)$x['file'], (string)$y['file']);
    }

    /**
     * @param array $x
     * @param array $y
     * @return int
     */
    function dateSort($x, $y)
    {
        return $x['date'] - $y['date'];
    }

    /**
     * @param array $x
     * @param array $y
     * @return int
     */
    function sizeSort($x, $y)
    {
        return $x['size'] - $y['size'];
    }

    /**
     * @param array $x
     * @param array $y
     * @return int
     */
    function extensionSort($x, $y)
    {
        return strcmp((string)$x['extension'], (string)$y['extension']);
    }

    switch ($sort_by) {
        case 'name':
            usort($sorted, 'filenameSort');
            break;
        case 'date':
            usort($sorted, 'dateSort');
            break;
        case 'size':
            usort($sorted, 'sizeSort');
            break;
        case 'extension':
            usort($sorted, 'extensionSort');
            break;
        default:
            break;

    }

    if ($descending) {
        $sorted = array_reverse($sorted);
    }

    $files = [];
    if (!empty($prev_folder)) {
        $files = [$prev_folder];
    }
    if (!empty($current_folder)) {
        $files = array_merge($files, [$current_folder]);
    }
    $files = array_merge($files, $sorted);
    ?>
	<!----- header div start ------->
	<div class="navbar navbar-fixed-top">
		<div class="navbar-inner">
			<div class="container-fluid">
				<button type="button" class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
				</button>
				<div class="brand"><?php echo Tools::safeOutput(lang_Toolbar); ?> -></div>
				<div class="nav-collapse collapse">
					<div class="filters">
						<div class="row-fluid">
							<div class="span3 half">
								<span><?php echo Tools::safeOutput(lang_Actions); ?>:</span>
                                <button class="tip btn upload-btn" title="<?php echo Tools::safeOutput(lang_Upload_file); ?>">
                                    <i class="icon-plus"></i><i class="icon-file"></i>
                                </button>
                                <button class="tip btn new-folder" title="<?php echo Tools::safeOutput(lang_New_Folder) ?>">
                                    <i class="icon-plus"></i><i class="icon-folder-open"></i>
                                </button>
							</div>
							<div class="span3 half view-controller">
								<span><?php echo lang_View; ?>:</span>
								<button class="btn tip<?php if ($view == 0) { echo " btn-inverse"; } ?>" id="view0" data-value="0" title="<?php echo Tools::safeOutput(lang_View_boxes); ?>">
									<i class="icon-th <?php if ($view == 0) { echo "icon-white"; } ?>"></i>
                                </button>
								<button class="btn tip<?php if ($view == 1) { echo " btn-inverse"; } ?>" id="view1" data-value="1" title="<?php echo Tools::safeOutput(lang_View_list); ?>">
									<i class="icon-align-justify <?php if ($view == 1) { echo "icon-white"; } ?>"></i>
								</button>
								<button class="btn tip<?php if ($view == 2) { echo " btn-inverse"; } ?>" id="view2" data-value="2" title="<?php echo Tools::safeOutput(lang_View_columns_list); ?>">
									<i class="icon-fire <?php if ($view == 2) { echo "icon-white"; } ?>"></i>
                                </button>
							</div>
							<div class="span6 types">
								<span><?php echo Tools::safeOutput(lang_Filters); ?>:</span>
								<?php if ($_GET['type'] != 1 && $_GET['type'] != 3) { ?>
									<input id="select-type-1" name="radio-sort" type="radio" data-item="ff-item-type-1" checked="checked" class="hide"/>
									<label id="ff-item-type-1" title="<?php echo Tools::safeOutput(lang_Files); ?>" for="select-type-1" class="tip btn ff-label-type-1">
                                        <i class="icon-file"></i>
                                    </label>
									<input id="select-type-2" name="radio-sort" type="radio" data-item="ff-item-type-2" class="hide"/>
									<label id="ff-item-type-2" title="<?php echo Tools::safeOutput(lang_Images); ?>" for="select-type-2" class="tip btn ff-label-type-2">
                                        <i class="icon-picture"></i>
                                    </label>
									<input id="select-type-3" name="radio-sort" type="radio" data-item="ff-item-type-3" class="hide"/>
									<label id="ff-item-type-3" title="<?php echo Tools::safeOutput(lang_Archives); ?>" for="select-type-3" class="tip btn ff-label-type-3">
                                        <i class="icon-inbox"></i>
                                    </label>
									<input id="select-type-4" name="radio-sort" type="radio" data-item="ff-item-type-4" class="hide"/>
									<label id="ff-item-type-4" title="<?php echo Tools::safeOutput(lang_Videos); ?>" for="select-type-4" class="tip btn ff-label-type-4">
                                        <i class="icon-film"></i>
                                    </label>
									<input id="select-type-5" name="radio-sort" type="radio" data-item="ff-item-type-5" class="hide"/>
									<label id="ff-item-type-5" title="<?php echo Tools::safeOutput(lang_Music); ?>" for="select-type-5" class="tip btn ff-label-type-5">
                                        <i class="icon-music"></i>
                                    </label>
								<?php } ?>
								<input accesskey="f" type="text" class="filter-input" id="filter-input" name="filter" placeholder="<?php echo fix_strtolower(lang_Text_filter); ?>..." value="<?php echo Tools::safeOutput($filter); ?>"/>
                                <?php if ($n_files > FILE_MANAGER_FILE_NUMBER_LIMIT_JS) { ?>
                                    <label id="filter" class="btn">
                                        <i class="icon-play"></i>
                                    </label>
                                <?php } ?>
								<input id="select-type-all" name="radio-sort" type="radio" data-item="ff-item-type-all" class="hide"/>
								<label id="ff-item-type-all" title="<?php echo Tools::safeOutput(lang_All); ?>" <?php if (Tools::getValue('type') == 1 || Tools::getValue('type') == 3) { ?>style="visibility: hidden;" <?php } ?> data-item="ff-item-type-all" for="select-type-all" style="margin-rigth:0px;" class="tip btn btn-inverse ff-label-type-all">
                                    <i class="icon-align-justify icon-white"></i>
                                </label>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<!----- header div end ------->

	<!----- breadcrumb div start ------->

	<div class="row-fluid">
		<?php $link = "dialog.php?".$get_params; ?>
		<ul class="breadcrumb">
			<li class="pull-left"><a href="<?php echo Tools::safeOutput($link) ?>/"><i class="icon-home"></i></a></li>
			<li><span class="divider">/</span></li>
			<?php
            $bc = explode("/", $subdir);
    $tmp_path = '';
    if (!empty($bc)) {
        foreach ($bc as $k => $b) {
            $tmp_path .= $b."/";
            if ($k == count($bc) - 2) {
                ?>
						<li class="active"><?php echo Tools::safeOutput($b) ?></li><?php

            } elseif ($b != "") {
                ?>
						<li><a href="<?php echo Tools::safeOutput($link.$tmp_path)?>"><?php echo Tools::safeOutput($b) ?></a></li>
						<li><span class="divider"><?php echo "/";
                ?></span></li>
					<?php
            }
        }
    }
    ?>
			<li class="pull-right">
				<a class="btn-small" href="javascript:void('')" id="info"><i class="icon-question-sign"></i></a></li>
			<li class="pull-right">
				<a id="refresh" class="btn-small" href="dialog.php?<?php echo Tools::safeOutput($get_params.$subdir."&".uniqid()) ?>"><i class="icon-refresh"></i></a>
			</li>

			<li class="pull-right">
				<div class="btn-group">
					<a class="btn dropdown-toggle sorting-btn" data-toggle="dropdown" href="#">
						<i class="icon-signal"></i>
						<span class="caret"></span>
					</a>
					<ul class="dropdown-menu pull-left sorting">
						<li>
							<center><strong><?php echo Tools::safeOutput(lang_Sorting) ?></strong></center>
						</li>
						<li><a class="sorter sort-name <?php if ($sort_by == "name") { echo ($descending) ? "descending" : "ascending"; } ?>" href="javascript:void('')" data-sort="name"><?php echo Tools::safeOutput(lang_Filename); ?></a></li>
						<li><a class="sorter sort-date <?php if ($sort_by == "date") { echo ($descending) ? "descending" : "ascending"; } ?>" href="javascript:void('')" data-sort="date"><?php echo Tools::safeOutput(lang_Date); ?></a></li>
						<li><a class="sorter sort-size <?php if ($sort_by == "size") { echo ($descending) ? "descending" : "ascending"; } ?>" href="javascript:void('')" data-sort="size"><?php echo Tools::safeOutput(lang_Size); ?></a></li>
						<li><a class="sorter sort-extension <?php if ($sort_by == "extension") { echo ($descending) ? "descending" : "ascending"; } ?>" href="javascript:void('')" data-sort="extension"><?php echo Tools::safeOutput(lang_Type); ?></a></li>
					</ul>
				</div>
			</li>
		</ul>
	</div>
	<!----- breadcrumb div end ------->
	<div class="row-fluid ff-container">
	<div class="span12">
	<?php if (@opendir(FILE_MANAGER_BASE_DIR.$subfolder.$subdir) === false) { ?>
		<br/>
		<div class="alert alert-error">There is an error! The upload folder there isn't. Check your config.php file.
		</div>
	<?php } else { ?>
	<h4 id="help"><?php echo Tools::safeOutput(lang_Swipe_help);
    ?></h4>
	<?php if (isset($folder_message)) { ?>
		<div class="alert alert-block"><?php echo Tools::safeOutput($folder_message); ?></div>
	<?php } ?>
    <!-- sorter -->
    <div class="sorter-container <?php echo "list-view".Tools::safeOutput($view); ?>">
        <div class="file-name"><a class="sorter sort-name <?php if ($sort_by == "name") { echo ($descending) ? "descending" : "ascending"; } ?>" href="javascript:void('')" data-sort="name"><?php echo Tools::safeOutput(lang_Filename); ?></a></div>
        <div class="file-date"><a class="sorter sort-date <?php if ($sort_by == "date") { echo ($descending) ? "descending" : "ascending"; } ?>" href="javascript:void('')" data-sort="date"><?php echo Tools::safeOutput(lang_Date); ?></a></div>
        <div class="file-size"><a class="sorter sort-size <?php if ($sort_by == "size") { echo ($descending) ? "descending" : "ascending"; } ?>" href="javascript:void('')" data-sort="size"><?php echo Tools::safeOutput(lang_Size); ?></a></div>
        <div class='img-dimension'><?php echo Tools::safeOutput(lang_Dimension); ?></div>
        <div class='file-extension'><a class="sorter sort-extension <?php if ($sort_by == "extension") { echo ($descending) ? "descending" : "ascending"; } ?>" href="javascript:void('')" data-sort="extension"><?php echo Tools::safeOutput(lang_Type); ?></a></div>
        <div class='file-operations'><?php echo Tools::safeOutput(lang_Operations); ?></div>
    </div>

	<input type="hidden" id="file_number" value="<?php echo Tools::safeOutput($n_files);
    ?>"/>
	<ul class="grid cs-style-2 <?php echo "list-view".Tools::safeOutput($view); ?>">
	<?php

    $jplayer_ext = [
        "mp4",
        "flv",
        "webmv",
        "webma",
        "webm",
        "m4a",
        "m4v",
        "ogv",
        "oga",
        "mp3",
        "midi",
        "mid",
        "ogg",
        "wav"
    ];
    foreach ($files as $file_array) {
        $file = $file_array['file'];
        if ($file == '.' || (isset($file_array['extension']) && $file_array['extension'] != lang_Type_dir) || ($file == '..' && $subdir == '') || ($filter != '' && $file != ".." && strpos($file, $filter) === false)) {
            continue;
        }
        $new_name = fix_filename($file);
        if ($file != '..' && $file != $new_name) {
            //rename
            rename_folder(FILE_MANAGER_BASE_DIR.$subdir.$new_name, $new_name);
            $file = $new_name;
        }
        //add in thumbs folder if not exist
        if (!file_exists($thumbs_path.$subdir.$file)) {
            create_folder(false, $thumbs_path.$subdir.$file);
        }
        $class_ext = 3;
        if ($file == '..' && trim($subdir) != '') {
            $src = explode("/", $subdir);
            unset($src[count($src) - 2]);
            $src = implode("/", $src);
            if ($src == '') {
                $src = "/";
            }
        } elseif ($file != '..') {
            $src = $subdir.$file."/";
        }

        ?>
		<li data-name="<?php echo Tools::safeOutput($file) ?>" <?php if ($file == '..') { echo 'class="back"'; } else { echo 'class="dir"'; } ?>>
			<figure data-name="<?php echo Tools::safeOutput($file) ?>" class="<?php if ($file == "..") { echo "back-"; } ?>directory" data-type="<?php if ($file != "..") { echo "dir"; } ?>">
				<a class="folder-link" href="dialog.php?<?php echo $get_params.rawurlencode($src)."&".uniqid() ?>">
					<div class="img-precontainer">
						<div class="img-container directory"><span></span>
							<img class="directory-img" src="img/<?php echo Tools::safeOutput(FILE_MANAGER_ICON_THEME); ?>/folder<?php if ($file == "..") { echo "_back"; } ?>.jpg" alt="folder"/>
						</div>
					</div>
					<div class="img-precontainer-mini directory">
						<div class="img-container-mini">
							<span></span>
							<img class="directory-img" src="img/<?php echo Tools::safeOutput(FILE_MANAGER_ICON_THEME); ?>/folder<?php if ($file == "..") { echo "_back"; } ?>.png" alt="folder"/>
						</div>
					</div>
					<?php if ($file == "..") { ?>
					<div class="box no-effect">
						<h4><?php echo Tools::safeOutput(lang_Back) ?></h4>
					</div>
				</a>

				<?php } else { ?>
					</a>
					<div class="box">
						<h4 class="ellipsis">
							<a class="folder-link" data-file="<?php echo Tools::safeOutput($file) ?>" href="dialog.php?<?php echo Tools::safeOutput($get_params.rawurlencode($src)."&".uniqid()) ?>"><?php echo Tools::safeOutput($file); ?></a>
						</h4>
					</div>
					<input type="hidden" class="name" value=""/>
					<input type="hidden" class="date" value="<?php echo Tools::safeOutput($file_array['date']); ?>"/>
					<input type="hidden" class="size" value="<?php echo Tools::safeOutput($file_array['size']); ?>"/>
					<input type="hidden" class="extension" value="<?php echo lang_Type_dir; ?>"/>
					<div class="file-date"><?php echo date(lang_Date_type, $file_array['date']) ?></div>
					<div class="file-size"><?php echo makeSize($file_array['size']) ?></div>
					<div class='file-extension'><?php echo lang_Type_dir; ?></div>
					<figcaption>
						<a href="javascript:void('')" class="tip-left edit-button rename-folder" title="<?php echo lang_Rename ?>" data-path="<?php echo Tools::safeOutput($subfolder.$subdir.$file); ?>" data-thumb="<?php echo Tools::safeOutput($subdir.$file); ?>">
							<i class="icon-pencil"></i>
                        </a>
						<a href="javascript:void('')" class="tip-left erase-button delete-folder" title="<?php echo lang_Erase ?>" data-confirm="<?php echo lang_Confirm_Folder_del; ?>" data-path="<?php echo Tools::safeOutput($subfolder.$subdir.$file); ?>" data-thumb="<?php echo Tools::safeOutput($subdir.$file); ?>">
							<i class="icon-trash"></i>
						</a>
					</figcaption>
				<?php } ?>
			</figure>
		</li>
	<?php

    }

    foreach ($files as $nu => $file_array) {
        $file = $file_array['file'];

        if ($file == '.' || $file == '..' || is_dir(FILE_MANAGER_BASE_DIR.$subfolder.$subdir.$file) || !in_array(fix_strtolower($file_array['extension']), getFileExtensions()) || ($filter != '' && strpos($file, $filter) === false)) {
            continue;
        }

        $file_path = FILE_MANAGER_BASE_DIR.$subfolder.$subdir.$file;
        $filename = substr($file, 0, '-'.(strlen($file_array['extension']) + 1));

        if ($file != fix_filename($file)) {
            $file1 = fix_filename($file);
            $file_path1 = (FILE_MANAGER_BASE_DIR.$subfolder.$subdir.$file1);
            if (file_exists($file_path1)) {
                $i = 1;
                $info = pathinfo($file1);
                while (file_exists(FILE_MANAGER_BASE_DIR.$subfolder.$subdir.$info['filename'].".[".$i."].".$info['extension'])) {
                    $i++;
                }
                $file1 = $info['filename'].".[".$i."].".$info['extension'];
                $file_path1 = (FILE_MANAGER_BASE_DIR.$subfolder.$subdir.$file1);
            }

            $filename = substr($file1, 0, '-'.(strlen($file_array['extension']) + 1));
            rename_file($file_path, fix_filename($filename));
            $file = $file1;
            $file_array['extension'] = fix_filename($file_array['extension']);
            $file_path = $file_path1;
        }

        $is_img = false;
        $is_video = false;
        $is_audio = false;
        $show_original = false;
        $show_original_mini = false;
        $mini_src = "";
        $src_thumb = "";
        $extension_lower = fix_strtolower($file_array['extension']);
        if (in_array($extension_lower, getFileExtensions('image'))) {
            $src = FILE_MANAGER_BASE_URL . $cur_dir.rawurlencode($file);
            $mini_src = $src_thumb = $thumbs_path.$subdir.$file;

        //add in thumbs folder if not exist
        if (!file_exists($src_thumb)) {
            try {
                create_img_gd($file_path, $src_thumb, 122, 91);
            } catch (Exception $e) {
                $src_thumb = $mini_src = "";
            }
        }
            $is_img = true;
        //check if is smaller than thumb
        list($img_width, $img_height, $img_type, $attr) = getimagesize($file_path);
            if ($img_width < 122 && $img_height < 91) {
                $src_thumb = FILE_MANAGER_BASE_DIR.$subfolder.$subdir.$file;
                $show_original = true;
            }

            if ($img_width < 45 && $img_height < 38) {
                $mini_src = FILE_MANAGER_BASE_DIR.$subfolder.$subdir.$file;
                $show_original_mini = true;
            }
        }

        $is_icon_thumb = false;
        $no_thumb = false;
        if ($src_thumb == "") {
            $no_thumb = true;
            if (file_exists('img/'.FILE_MANAGER_ICON_THEME.'/'.$extension_lower.".jpg")) {
                $src_thumb = 'img/'.FILE_MANAGER_ICON_THEME.'/'.$extension_lower.".jpg";
            } else {
                $src_thumb = "img/".FILE_MANAGER_ICON_THEME."/default.jpg";
            }
            $is_icon_thumb = true;
        }

        $class_ext = 0;
        if (in_array($extension_lower, getFileExtensions('video'))) {
            $class_ext = 4;
            $is_video = true;
        } elseif (in_array($extension_lower, getFileExtensions('image'))) {
            $class_ext = 2;
        } elseif (in_array($extension_lower, getFileExtensions('audio'))) {
            $class_ext = 5;
            $is_audio = true;
        } elseif (in_array($extension_lower, getFileExtensions('misc'))) {
            $class_ext = 3;
        } else {
            $class_ext = 1;
        }

        if ($src_thumb) {
            if (($src_thumb = preg_replace('#('.addslashes(FILE_MANAGER_BASE_DIR).')#ism', Tools::safeOutput(Context::getContext()->shop->physical_uri.'img/cms/'), $src_thumb)) == $src_thumb) {
                $src_thumb = preg_replace('#('.addslashes(FILE_MANAGER_THUMB_BASE_DIR).')#ism', Tools::safeOutput(Context::getContext()->shop->physical_uri.'img/tmp/cms/'), $src_thumb);
            }
        }

        if ($mini_src) {
            if (($mini_src = preg_replace('#('.addslashes(FILE_MANAGER_BASE_DIR).')#ism', Tools::safeOutput(Context::getContext()->shop->physical_uri.'img/cms/'), $mini_src)) == $mini_src) {
                $mini_src = preg_replace('#('.addslashes(FILE_MANAGER_THUMB_BASE_DIR).')#ism', Tools::safeOutput(Context::getContext()->shop->physical_uri.'img/tmp/cms/'), $mini_src);
            }
        }

    if ((!(Tools::getValue('type') == 1 && !$is_img) && !((Tools::getValue('type') == 3 && !$is_video) && (Tools::getValue('type') == 3 && !$is_audio)))) {
        ?>
	<li class="ff-item-type-<?php echo Tools::safeOutput($class_ext); ?> file" data-name="<?php echo Tools::safeOutput($file); ?>">
		<figure data-name="<?php echo Tools::safeOutput($file) ?>" data-type="<?php if ($is_img) { echo "img"; } else { echo "file"; } ?>">
			<a href="javascript:void('')" class="link" data-file="<?php echo Tools::safeOutput($file); ?>" data-field_id="" data-function="<?php echo Tools::safeOutput($apply); ?>">
				<div class="img-precontainer">
					<?php if ($is_icon_thumb) { ?>
						<div class="filetype"><?php echo $extension_lower ?></div>
                    <?php } ?>
					<div class="img-container">
						<span></span>
						<img alt="<?php echo Tools::safeOutput($filename." thumbnails"); ?>" class="<?php echo $show_original ? "original" : "" ?> <?php echo $is_icon_thumb ? "icon" : "" ?>" src="<?php echo Tools::safeOutput($src_thumb); ?>">
					</div>
				</div>
				<div class="img-precontainer-mini <?php if ($is_img) { echo 'original-thumb'; } ?>">
					<div class="filetype <?php echo $extension_lower ?> <?php if (!$is_icon_thumb) { echo "hide"; } ?>"><?php echo $extension_lower ?></div>
					<div class="img-container-mini">
						<span></span>
						<?php if ($mini_src != "") { ?>
							<img alt="<?php echo Tools::safeOutput($filename." thumbnails"); ?>" class="<?php echo $show_original_mini ? "original" : "" ?> " src="<?php echo Tools::safeOutput($mini_src); ?>">
						<?php } ?>
					</div>
				</div>
				<?php if ($is_icon_thumb) { ?>
					<div class="cover"></div>
				<?php } ?>
			</a>

			<div class="box">
				<h4 class="ellipsis">
					<a href="javascript:void('')" class="link" data-file="<?php echo Tools::safeOutput($file); ?>" data-field_id="" data-function="<?php echo Tools::safeOutput($apply); ?>">
						<?php echo Tools::safeOutput($filename); ?>
                    </a>
                </h4>
			</div>
			<input type="hidden" class="date" value="<?php echo $file_array['date']; ?>"/>
			<input type="hidden" class="size" value="<?php echo $file_array['size'] ?>"/>
			<input type="hidden" class="extension" value="<?php echo $extension_lower; ?>"/>
			<input type="hidden" class="name" value=""/>

			<div class="file-date"><?php echo date(lang_Date_type, $file_array['date']) ?></div>
			<div class="file-size"><?php echo makeSize($file_array['size']) ?></div>
			<div class='img-dimension'><?php if ($is_img) { echo $img_width."x".$img_height; } ?></div>
			<div class='file-extension'><?php echo Tools::safeOutput($extension_lower); ?></div>
			<figcaption>
				<form action="force_download.php" method="post" class="download-form" id="form<?php echo Tools::safeOutput($nu); ?>">
					<input type="hidden" name="path" value="<?php echo Tools::safeOutput($subfolder.$subdir) ?>"/>
					<input type="hidden" class="name_download" name="name" value="<?php echo Tools::safeOutput($file) ?>"/>

					<a title="<?php echo lang_Download ?>" class="tip-right" href="javascript:void('')" onclick="$('#form<?php echo Tools::safeOutput($nu); ?>').submit();">
                        <i class="icon-download"></i>
                    </a>
					<?php if ($is_img && $src_thumb != "") { ?>
						<a class="tip-right preview" title="<?php echo lang_Preview ?>" data-url="<?php echo Tools::safeOutput($src); ?>" data-toggle="lightbox" href="#previewLightbox"><i class=" icon-eye-open"></i></a>
					<?php } elseif (($is_video || $is_audio) && in_array($extension_lower, $jplayer_ext)) { ?>
						<a class="tip-right modalAV <?php if ($is_audio) { echo "audio"; } else { echo "video"; } ?>" title="<?php echo lang_Preview ?>" data-url="ajax_calls.php?action=media_preview&title=<?php echo Tools::safeOutput($filename); ?>&file=<?php echo Tools::safeOutput(Context::getContext()->shop->physical_uri.'img/cms/'.$subfolder.$subdir.$file); ?>"
						   href="javascript:void('');"><i class=" icon-eye-open"></i></a>
					<?php } else { ?>
						<a class="preview disabled"><i class="icon-eye-open icon-white"></i></a>
					<?php } ?>
					<a href="javascript:void('')" class="tip-left edit-button rename-file" title="<?php echo lang_Rename ?>" data-path="<?php echo Tools::safeOutput($subfolder.$subdir.$file); ?>" data-thumb="<?php echo Tools::safeOutput($subdir.$file); ?>">
						<i class="icon-pencil"></i>
                    </a>
					<a href="javascript:void('')" class="tip-left erase-button delete-file" title="<?php echo lang_Erase ?>" data-confirm="<?php echo lang_Confirm_del; ?>" data-path="<?php echo Tools::safeOutput($subfolder.$subdir.$file); ?>" data-thumb="<?php echo Tools::safeOutput($subdir.$file); ?>">
						<i class="icon-trash"></i>
					</a>
				</form>
			</figcaption>
		</figure>
	</li>
	<?php
    }
    }

    ?></div>
	</ul>
	<?php
}
    ?>
	</div>
	</div>
	</div>

	<!----- lightbox div start ------->
	<div id="previewLightbox" class="lightbox hide fade" tabindex="-1" role="dialog" aria-hidden="true">
		<div class='lightbox-content'>
			<img id="full-img" src="">
		</div>
	</div>
	<!----- lightbox div end ------->

	<!----- loading div start ------->
	<div id="loading_container" style="display:none;">
		<div id="loading" style="background-color:#000; position:fixed; width:100%; height:100%; top:0px; left:0px;z-index:100000"></div>
		<img id="loading_animation" src="img/storing_animation.gif" alt="loading" style="z-index:10001; margin-left:-32px; margin-top:-32px; position:fixed; left:50%; top:50%"/>
	</div>
	<!----- loading div end ------->

	<!----- player div start ------->
	<div class="modal hide fade" id="previewAV">
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
			<h3><?php echo lang_Preview;
    ?></h3>
		</div>
		<div class="modal-body">
			<div class="row-fluid body-preview">
			</div>
		</div>

	</div>
	<!----- player div end ------->
	</body>
	</html>
<?php } ?>
