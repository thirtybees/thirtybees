<?php
/** @noinspection PhpUnhandledExceptionInspection */

include('config/config.php');

if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'view':
            setViewType((int)Tools::getValue('type', 0));
            break;
        case 'sort':
            setSortBy(Tools::getValue('sort_by'));
            setDescending(Tools::getValue('descending') === 'true');
            break;
        case 'extract':
            if (strpos($_POST['path'], '/') === 0 || strpos($_POST['path'], '../') !== false || strpos($_POST['path'], './') === 0) {
                die('wrong path');
            }
            $path = FILE_MANAGER_BASE_DIR.$_POST['path'];
            $info = pathinfo($path);
            $base_folder = FILE_MANAGER_BASE_DIR.fix_dirname($_POST['path']).'/';
            switch ($info['extension']) {
                case 'zip':
                    $zip = new ZipArchive;
                    if ($zip->open($path) === true) {
                        //make all the folders
                        for ($i = 0; $i < $zip->numFiles; $i++) {
                            $OnlyFileName = $zip->getNameIndex($i);
                            $FullFileName = $zip->statIndex($i);
                            if ($FullFileName['name'][strlen($FullFileName['name']) - 1] == '/') {
                                create_folder($base_folder.$FullFileName['name']);
                            }
                        }
                        //unzip into the folders
                        for ($i = 0; $i < $zip->numFiles; $i++) {
                            $OnlyFileName = $zip->getNameIndex($i);
                            $FullFileName = $zip->statIndex($i);

                            if (!($FullFileName['name'][strlen($FullFileName['name']) - 1] == '/')) {
                                $fileinfo = pathinfo($OnlyFileName);
                                if (in_array(strtolower($fileinfo['extension']), getFileExtensions())) {
                                    copy('zip://'.$path.'#'.$OnlyFileName, $base_folder.$FullFileName['name']);
                                }
                            }
                        }
                        $zip->close();
                    } else {
                        echo 'failed to open file';
                    }
                    break;
                case 'gz':
                    $p = new PharData($path);
                    $p->decompress(); // creates files.tar
                    break;
                case 'tar':
                    // unarchive from the tar
                    $phar = new PharData($path);
                    $phar->decompressFiles();
                    $files = [];
                    check_files_extensions_on_phar($phar, $files, '', getFileExtensions());
                    $phar->extractTo(FILE_MANAGER_BASE_DIR.fix_dirname($_POST['path']).'/', $files, true);

                    break;
            }
            break;
        case 'media_preview':

            $preview_file = $_GET['file'];
            $info = pathinfo($preview_file);
            ?>
			<div id="jp_container_1" class="jp-video " style="margin:0 auto;">
				<div class="jp-type-single">
					<div id="jquery_jplayer_1" class="jp-jplayer"></div>
					<div class="jp-gui">
						<div class="jp-video-play">
							<a href="javascript:;" class="jp-video-play-icon" tabindex="1">play</a>
						</div>
						<div class="jp-interface">
							<div class="jp-progress">
								<div class="jp-seek-bar">
									<div class="jp-play-bar"></div>
								</div>
							</div>
							<div class="jp-current-time"></div>
							<div class="jp-duration"></div>
							<div class="jp-controls-holder">
								<ul class="jp-controls">
									<li><a href="javascript:;" class="jp-play" tabindex="1">play</a></li>
									<li><a href="javascript:;" class="jp-pause" tabindex="1">pause</a></li>
									<li><a href="javascript:;" class="jp-stop" tabindex="1">stop</a></li>
									<li><a href="javascript:;" class="jp-mute" tabindex="1" title="mute">mute</a></li>
									<li><a href="javascript:;" class="jp-unmute" tabindex="1" title="unmute">unmute</a>
									</li>
									<li><a href="javascript:;" class="jp-volume-max" tabindex="1" title="max volume">max
											volume</a></li>
								</ul>
								<div class="jp-volume-bar">
									<div class="jp-volume-bar-value"></div>
								</div>
								<ul class="jp-toggles">
									<li><a href="javascript:;" class="jp-full-screen" tabindex="1" title="full screen">full
											screen</a></li>
									<li>
										<a href="javascript:;" class="jp-restore-screen" tabindex="1" title="restore screen">restore
											screen</a></li>
									<li><a href="javascript:;" class="jp-repeat" tabindex="1" title="repeat">repeat</a>
									</li>
									<li><a href="javascript:;" class="jp-repeat-off" tabindex="1" title="repeat off">repeat
											off</a></li>
								</ul>
							</div>
							<div class="jp-title" style="display:none;">
								<ul>
									<li></li>
								</ul>
							</div>
						</div>
					</div>
					<div class="jp-no-solution">
						<span>Update Required</span>
						To play the media you will need to either update your browser to a recent version or update your
						<a href="http://get.adobe.com/flashplayer/" class="_blank">Flash plugin</a>.
					</div>
				</div>
			</div>
			<?php
            if (in_array(strtolower($info['extension']), getFileExtensions('audio'))) {
            ?>
				<script type="text/javascript">
					$(document).ready(function () {

						$("#jquery_jplayer_1").jPlayer({
							ready: function () {
								$(this).jPlayer("setMedia", {
									title: "<?php Tools::safeOutput($_GET['title']); ?>",
									mp3: "<?php echo Tools::safeOutput($preview_file); ?>",
									m4a: "<?php echo Tools::safeOutput($preview_file); ?>",
									oga: "<?php echo Tools::safeOutput($preview_file); ?>",
									wav: "<?php echo Tools::safeOutput($preview_file); ?>"
								});
							},
							swfPath: "js",
							solution: "html,flash",
							supplied: "mp3, m4a, midi, mid, oga,webma, ogg, wav",
							smoothPlayBar: true,
							keyEnabled: false
						});
					});
				</script>

			<?php } elseif (in_array(strtolower($info['extension']), getFileExtensions('video'))) { ?>

				<script type="text/javascript">
					$(document).ready(function () {

						$("#jquery_jplayer_1").jPlayer({
							ready: function () {
								$(this).jPlayer("setMedia", {
									title: "<?php Tools::safeOutput($_GET['title']); ?>",
									m4v: "<?php echo Tools::safeOutput($preview_file); ?>",
									ogv: "<?php echo Tools::safeOutput($preview_file); ?>"
								});
							},
							swfPath: "js",
							solution: "html,flash",
							supplied: "mp4, m4v, ogv, flv, webmv, webm",
							smoothPlayBar: true,
							keyEnabled: false
						});

					});
				</script>

			<?php

            }
            break;
    }
} else {
    die('no action passed');
}
?>
