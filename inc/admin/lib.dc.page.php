<?php
# -- BEGIN LICENSE BLOCK ---------------------------------------
#
# This file is part of Dotclear 2.
#
# Copyright (c) 2003-2013 Olivier Meunier & Association Dotclear
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK -----------------------------------------
if (!defined('DC_RC_PATH')) { return; }

define('DC_AUTH_PAGE','auth.php');

class dcPage
{
	private static $loaded_js = array();
	private static $N_TYPES = array(
		"success" => "success",
		"warning" => "warning-msg",
		"error" => "error",
		"message" => "message",
		"static" => "static-msg");

	# Auth check
	public static function check($permissions)
	{
		global $core;

		if ($core->blog && $core->auth->check($permissions,$core->blog->id)) {
			return;
		}

		if (session_id()) {
			$core->session->destroy();
		}
		http::redirect(DC_AUTH_PAGE);
	}

	# Check super admin
	public static function checkSuper()
	{
		global $core;

		if (!$core->auth->isSuperAdmin())
		{
			if (session_id()) {
				$core->session->destroy();
			}
			http::redirect(DC_AUTH_PAGE);
		}
	}

	# Top of admin page
	public static function open($title='',$head='',$breadcrumb='')
	{
		global $core;

		# List of user's blogs
		if ($core->auth->getBlogCount() == 1 || $core->auth->getBlogCount() > 20)
		{
			$blog_box =
			'<p>'.__('Blog:').' <strong title="'.html::escapeHTML($core->blog->url).'">'.
			html::escapeHTML($core->blog->name).'</strong>';

			if ($core->auth->getBlogCount() > 20) {
				$blog_box .= ' - <a href="blogs.php">'.__('Change blog').'</a>';
			}
			$blog_box .= '</p>';
		}
		else
		{
			$rs_blogs = $core->getBlogs(array('order'=>'LOWER(blog_name)','limit'=>20));
			$blogs = array();
			while ($rs_blogs->fetch()) {
				$blogs[html::escapeHTML($rs_blogs->blog_name.' - '.$rs_blogs->blog_url)] = $rs_blogs->blog_id;
			}
			$blog_box =
			'<p><label for="switchblog" class="classic">'.
			__('Blogs:').'</label> '.
			$core->formNonce().
			form::combo('switchblog',$blogs,$core->blog->id).
			'<input type="submit" value="'.__('ok').'" class="hidden-if-js" /></p>';
		}

		$safe_mode = isset($_SESSION['sess_safe_mode']) && $_SESSION['sess_safe_mode'];

		# Display
		header('Content-Type: text/html; charset=UTF-8');
		echo
		'<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" '.
		' "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">'."\n".
		'<html xmlns="http://www.w3.org/1999/xhtml" '.
		'xml:lang="'.$core->auth->getInfo('user_lang').'" '.
		'lang="'.$core->auth->getInfo('user_lang').'">'."\n".
		"<head>\n".
		'  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />'."\n".
		'  <meta name="ROBOTS" content="NOARCHIVE,NOINDEX,NOFOLLOW" />'."\n".
		'  <meta name="GOOGLEBOT" content="NOSNIPPET" />'."\n".
		'  <meta name="viewport" content="width=device-width, initial-scale=1.0" />'."\n".
		'  <title>'.$title.' - '.html::escapeHTML($core->blog->name).' - '.html::escapeHTML(DC_VENDOR_NAME).' - '.DC_VERSION.'</title>'."\n".


		self::jsLoadIE7().
		'  <link rel="stylesheet" href="style/default.css" type="text/css" media="screen" />'."\n";
		if (l10n::getTextDirection($GLOBALS['_lang']) == 'rtl') {
			echo
			'  <link rel="stylesheet" href="style/default-rtl.css" type="text/css" media="screen" />'."\n";
		}

		$core->auth->user_prefs->addWorkspace('interface');
		$user_ui_hide_std_favicon = $core->auth->user_prefs->interface->hide_std_favicon;
		if (!$user_ui_hide_std_favicon) {
			echo
			'<link rel="icon" type="image/png" href="images/favicon96-login.png" />'.
			'<link rel="shortcut icon" href="images/favicon.ico" type="image/x-icon" />';
		}
		echo
		self::jsCommon().
		self::jsToggles().
		$head;

		# --BEHAVIOR-- adminPageHTMLHead
		$core->callBehavior('adminPageHTMLHead');

		echo
		"</head>\n".
		'<body id="dotclear-admin'.
		($safe_mode ? ' safe-mode' : '').'" class="no-js">'."\n".

		'<ul id="prelude">'.
		'<li><a href="#content">'.__('Go to the content').'</a></li>'.
		'<li><a href="#main-menu">'.__('Go to the menu').'</a></li>'.
		'<li><a href="#qx">'.__('Go to search').'</a></li>'.
		'<li><a href="#help">'.__('Go to help').'</a></li>'.
		'</ul>'."\n".
		'<div id="header">'.
		'<h1><a href="index.php"><span class="hidden">'.DC_VENDOR_NAME.'</span></a></h1>'."\n";

		echo
		'<form action="index.php" method="post" id="top-info-blog">'.
		$blog_box.
		'<p><a href="'.$core->blog->url.'" class="outgoing" title="'.__('Go to site').
		'">'.__('Go to site').'<img src="images/outgoing.png" alt="" /></a>'.
		'</p></form>'.
		'<ul id="top-info-user">'.
		'<li><a class="'.(preg_match('/index.php$/',$_SERVER['REQUEST_URI']) ? ' active' : '').'" href="index.php">'.__('My dashboard').'</a></li>'.
		'<li><a class="smallscreen'.(preg_match('/preferences.php(\?.*)?$/',$_SERVER['REQUEST_URI']) ? ' active' : '').
		'" href="preferences.php">'.__('My preferences').'</a></li>'.
		'<li><a href="index.php?logout=1" class="logout"><span class="nomobile">'.sprintf(__('Logout %s'),$core->auth->userID()).
		'</span><img src="images/logout.png" alt="" /></a></li>'.
		'</ul>'.
		'</div>'; // end header

		echo
		'<div id="wrapper" class="clearfix">'."\n".
		'<div class="hidden-if-no-js collapser-box"><a href="#" id="collapser">'.
		'<img class="collapse-mm" src="images/collapser-hide.png" alt="'.__('Hide main menu').'" />'.
		'<img class="expand-mm" src="images/collapser-show.png" alt="'.__('Show main menu').'" />'.
		'</a></div>'.
		'<div id="main">'."\n".
		'<div id="content" class="clearfix">'."\n";

		# Safe mode
		if ($safe_mode)
		{
			echo
			'<div class="warning"><h3>'.__('Safe mode').'</h3>'.
			'<p>'.__('You are in safe mode. All plugins have been temporarily disabled. Remind to log out then log in again normally to get back all functionalities').'</p>'.
			'</div>';
		}

		// Display breadcrumb (if given) before any error message
		echo $breadcrumb;

		if ($core->error->flag()) {
			echo
			'<div class="error"><p><strong>'.(count($core->error->getErrors()) > 1 ? __('Errors:') : __('Error:')).'</strong></p>'.
			$core->error->toHTML().
			'</div>';
		}

		// Display notices
		echo self::notices();
	}

	public static function notices()
	{
		// return notices if any
		$res = '';
		if (isset($_SESSION['notifications'])) {
			$notifications = $_SESSION['notifications'];
			foreach ($_SESSION['notifications'] as $notification) {
				$res .= self::getNotification($notification);
			}
			unset($_SESSION['notifications']);
		}
		return $res;
	}

	public static function addNotice($type,$message,$options=array())
	{
		if (isset(self::$N_TYPES[$type])){
			$class = self::$N_TYPES[$type];
		} else {
			$class=$type;
		}
		if (isset($_SESSION['notifications']) && is_array($_SESSION['notifications'])) {
			$notifications = $_SESSION['notifications'];
		} else {
			$notifications = array();
		}

		$n = array_merge($options,array('class' => $class,'ts' => time(), 'text' => $message));
		if ($type != "static") {
			$notifications[] = $n;
		} else {
			array_unshift($notifications, $n);
		}
		$_SESSION['notifications'] = $notifications;
	}

	public static function addSuccessNotice($message,$options=array())
	{
		self::addNotice("success",$message,$options);
	}

	public static function addWarningNotice($message,$options=array())
	{
		self::addNotice("warning",$message,$options);
	}

	public static function addErrorNotice($message,$options=array())
	{
		self::addNotice("error",$message,$options);
	}

	protected static function getNotification($n)
	{
		global $core;
		$tag = (isset($n['divtag'])&& $n['divtag'])?'div':'p';
		$ts = '';
		if (!isset($n['with_ts']) || ($n['with_ts'] == true)) {
			$ts = dt::str(__('[%H:%M:%S]'),$n['ts'],$core->auth->getInfo('user_tz')).' ';
		}
		$res = '<'.$tag.' class="'.$n['class'].'">'.$ts.$n['text'].'</'.$tag.'>';
		return $res;
	}

	public static function close()
	{
		global $core;

		if (!$GLOBALS['__resources']['ctxhelp']) {
			echo
			'<p id="help-button"><a href="help.php" class="outgoing" title="'.
			__('Global help').'">'.__('Global help').'</a></p>';
		}

		$menu =& $GLOBALS['_menu'];

		echo
		"</div>\n".		// End of #content
		"</div>\n".		// End of #main

		'<div id="main-menu">'."\n".

		'<form id="search-menu" action="search.php" method="get">'.
		'<p><label for="qx" class="hidden">'.__('Search:').' </label>'.form::field('qx',30,255,'').
		'<input type="submit" value="'.__('OK').'" /></p>'.
		'</form>';

		foreach ($menu as $k => $v) {
			echo $menu[$k]->draw();
		}

		$text = sprintf(__('Thank you for using %s.'),'Dotclear '.DC_VERSION);

		# --BEHAVIOR-- adminPageFooter
		$textAlt = $core->callBehavior('adminPageFooter',$core,$text);
		if ($textAlt != '') {
			$text = $textAlt;
		}
		$text = html::escapeHTML($text);

		echo
		'</div>'."\n".		// End of #main-menu
		"</div>\n";		// End of #wrapper

		echo
		'<div id="footer">'.
		'<a href="http://dotclear.org/" title="'.$text.'">'.
		'<img src="style/dc_logos/w-dotclear90.png" alt="'.$text.'" /></a></div>'."\n".
        "<!-- \n                  \n               ,;:'`'::\n".
		"            __||\n      _____/LLLL\_\n      \__________\"|\n".
        "    ~^~^~^~^~^~^~^~^~^~\n -->\n";

		if (defined('DC_DEV') && DC_DEV === true) {
			echo self::debugInfo();
		}

		echo
		'</body></html>';
	}

	public static function openPopup($title='',$head='',$breadcrumb='')
	{
		global $core;

		# Display
		header('Content-Type: text/html; charset=UTF-8');
		echo
		'<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" '.
		' "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">'."\n".
		'<html xmlns="http://www.w3.org/1999/xhtml" '.
		'xml:lang="'.$core->auth->getInfo('user_lang').'" '.
		'lang="'.$core->auth->getInfo('user_lang').'">'."\n".
		"<head>\n".
		'  <meta name="viewport" content="width=device-width, initial-scale=1.0" />'."\n".
		'  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />'."\n".
		'  <title>'.$title.' - '.html::escapeHTML($core->blog->name).' - '.html::escapeHTML(DC_VENDOR_NAME).' - '.DC_VERSION.'</title>'."\n".

		'  <meta name="ROBOTS" content="NOARCHIVE,NOINDEX,NOFOLLOW" />'."\n".
		'  <meta name="GOOGLEBOT" content="NOSNIPPET" />'."\n".

		self::jsLoadIE7().
		'	<link rel="stylesheet" href="style/default.css" type="text/css" media="screen" />'."\n";
		if (l10n::getTextDirection($GLOBALS['_lang']) == 'rtl') {
			echo
			'	<link rel="stylesheet" href="style/default-rtl.css" type="text/css" media="screen" />'."\n";
		}

		echo
		self::jsCommon().
		self::jsToggles().
		$head;

		# --BEHAVIOR-- adminPageHTMLHead
		$core->callBehavior('adminPageHTMLHead');

		echo
		"</head>\n".
		'<body id="dotclear-admin" class="popup">'."\n".

		'<h1>'.DC_VENDOR_NAME.'</h1>'."\n";

		echo
		'<div id="wrapper">'."\n".
		'<div id="main">'."\n".
		'<div id="content">'."\n";

		// display breadcrumb if given
		echo $breadcrumb;

		if ($core->error->flag()) {
			echo
			'<div class="error"><strong>'.__('Errors:').'</strong>'.
			$core->error->toHTML().
			'</div>';
		}
	}

	public static function closePopup()
	{
		echo
		"</div>\n".		// End of #content
		"</div>\n".		// End of #main
		"</div>\n".		// End of #wrapper
		'<div id="footer"><p>&nbsp;</p></div>'."\n".
		'</body></html>';
	}

	public static function breadcrumb($elements=null,$options=array())
	{
		$with_home_link = isset($options['home_link'])?$options['home_link']:true;
		$hl = isset($options['hl'])?$options['hl']:true;
		$hl_pos = isset($options['hl_pos'])?$options['hl_pos']:-1;
		// First item of array elements should be blog's name, System or Plugins
		$res = '<h2>'.($with_home_link ?
			'<a class="go_home" href="index.php"><img src="style/dashboard.png" alt="'.__('Go to dashboard').'" /></a>' :
			'<img src="style/dashboard-alt.png" alt="" />');
		$index = 0;
		if ($hl_pos < 0) {
			$hl_pos = count($elements)+$hl_pos;
		}
		foreach ($elements as $element => $url) {
			if ($hl && $index == $hl_pos) {
				$element = sprintf('<span class="page-title">%s</span>',$element);
			}
			$res .= ($with_home_link ? ($index == 1 ? ' : ' : ' &rsaquo; ') : ($index == 0 ? ' ' : ' &rsaquo; ')).
				($url ? '<a href="'.$url.'">' : '').$element.($url ? '</a>' : '');
			$index++;
		}
		$res .= '</h2>';
		return $res;
	}

	public static function message($msg,$timestamp=true,$div=false,$echo=true,$class='message')
	{
		global $core;

		$res = '';
		if ($msg != '') {
			$res = ($div ? '<div class="'.$class.'">' : '').'<p'.($div ? '' : ' class="'.$class.'"').'>'.
			($timestamp ? dt::str(__('[%H:%M:%S]'),null,$core->auth->getInfo('user_tz')).' ' : '').$msg.
			'</p>'.($div ? '</div>' : '');
			if ($echo) {
				echo $res;
			}
		}
		return $res;
	}

	public static function success($msg,$timestamp=true,$div=false,$echo=true)
	{
		return self::message($msg,$timestamp,$div,$echo,"success");
	}

	public static function warning($msg,$timestamp=true,$div=false,$echo=true)
	{
		return self::message($msg,$timestamp,$div,$echo,"warning-msg");
	}

	private static function debugInfo()
	{
		$global_vars = implode(', ',array_keys($GLOBALS));

		$res =
		'<div id="debug"><div>'.
		'<p>memory usage: '.memory_get_usage().' ('.files::size(memory_get_usage()).')</p>';

		if (function_exists('xdebug_get_profiler_filename'))
		{
			$res .= '<p>Elapsed time: '.xdebug_time_index().' seconds</p>';

			$prof_file = xdebug_get_profiler_filename();
			if ($prof_file) {
				$res .= '<p>Profiler file : '.xdebug_get_profiler_filename().'</p>';
			} else {
				$prof_url = http::getSelfURI();
				$prof_url .= (strpos($prof_url,'?') === false) ? '?' : '&';
				$prof_url .= 'XDEBUG_PROFILE';
				$res .= '<p><a href="'.html::escapeURL($prof_url).'">Trigger profiler</a></p>';
			}

			/* xdebug configuration:
			zend_extension = /.../xdebug.so
			xdebug.auto_trace = On
			xdebug.trace_format = 0
			xdebug.trace_options = 1
			xdebug.show_mem_delta = On
			xdebug.profiler_enable = 0
			xdebug.profiler_enable_trigger = 1
			xdebug.profiler_output_dir = /tmp
			xdebug.profiler_append = 0
			xdebug.profiler_output_name = timestamp
			*/
		}

		$res .=
		'<p>Global vars: '.$global_vars.'</p>'.
		'</div></div>';

		return $res;
	}

	public static function help($page,$index='')
	{
		# Deprecated but we keep this for plugins.
	}

	public static function helpBlock()
	{
		$args = func_get_args();

		$args = new ArrayObject($args);

		# --BEHAVIOR-- adminPageHelpBlock
		$GLOBALS['core']->callBehavior('adminPageHelpBlock',$args);

		if (empty($args)) {
			return;
		};

		global $__resources;
		if (empty($__resources['help'])) {
			return;
		}

		$content = '';
		foreach ($args as $v)
		{
			if (is_object($v) && isset($v->content)) {
				$content .= $v->content;
				continue;
			}

			if (!isset($__resources['help'][$v])) {
				continue;
			}
			$f = $__resources['help'][$v];
			if (!file_exists($f) || !is_readable($f)) {
				continue;
			}

			$fc = file_get_contents($f);
			if (preg_match('|<body[^>]*?>(.*?)</body>|ms',$fc,$matches)) {
				$content .= $matches[1];
			} else {
				$content .= $fc;
			}
		}

		if (trim($content) == '') {
			return;
		}

		// Set contextual help global flag
		$GLOBALS['__resources']['ctxhelp'] = true;

		echo
		'<div id="help"><hr /><div class="help-content clear"><h3>'.__('Help about this page').'</h3>'.
		$content.
		'</div>'.
		'<div id="helplink"><hr />'.
		'<p>'.
		sprintf(__('See also %s'),sprintf('<a href="help.php">%s</a>',__('the global help'))).
		'.</p>'.
		'</div></div>';
	}

	public static function jsLoad($src)
	{
		$escaped_src = html::escapeHTML($src);
		if (!isset(self::$loaded_js[$escaped_src])) {
			self::$loaded_js[$escaped_src]=true;
			return '<script type="text/javascript" src="'.$escaped_src.'"></script>'."\n";
		}
	}

	public static function jsVar($n,$v)
	{
		return $n." = '".html::escapeJS($v)."';\n";
	}

	public static function jsToggles()
	{
		if($GLOBALS['core']->auth->user_prefs->toggles) {
			$unfolded_sections = explode(',',$GLOBALS['core']->auth->user_prefs->toggles->unfolded_sections);
			foreach ($unfolded_sections as $k=>&$v) {
				if ($v == '') {
					unset($unfolded_sections[$k]);
				} else {
					$v = "'".html::escapeJS($v)."':true";
				}
			}
		} else {
			$unfolded_sections=array();
		}
		return '<script type="text/javascript">'."\n".
					"//<![CDATA[\n".
					'dotclear.unfolded_sections = {'.join(",",$unfolded_sections)."};\n".
					"\n//]]>\n".
				"</script>\n";
	}

	public static function jsCommon()
	{
		$mute_or_no = '';
		if (empty($GLOBALS['core']->blog) || $GLOBALS['core']->blog->settings->system->jquery_migrate_mute) {
			$mute_or_no .=
				'<script type="text/javascript">'."\n".
				"//<![CDATA[\n".
				'jQuery.migrateMute = true;'.
				"\n//]]>\n".
				"</script>\n";
		}

		return
		self::jsLoad('js/jquery/jquery.js').
		$mute_or_no.
		self::jsLoad('js/jquery/jquery-migrate-1.2.1.js').
		self::jsLoad('js/jquery/jquery.biscuit.js').
		self::jsLoad('js/jquery/jquery.bgFade.js').
		self::jsLoad('js/common.js').
		self::jsLoad('js/prelude.js').

		'<script type="text/javascript">'."\n".
		"//<![CDATA[\n".
		self::jsVar('dotclear.nonce',$GLOBALS['core']->getNonce()).
		self::jsVar('dotclear.img_plus_src','images/expand.png').
		self::jsVar('dotclear.img_plus_alt',__('uncover')).
		self::jsVar('dotclear.img_minus_src','images/hide.png').
		self::jsVar('dotclear.img_minus_alt',__('hide')).
		self::jsVar('dotclear.img_menu_on','images/menu_on.png').
		self::jsVar('dotclear.img_menu_off','images/menu_off.png').

		self::jsVar('dotclear.img_plus_theme_src','images/plus-theme.png').
		self::jsVar('dotclear.img_plus_theme_alt',__('uncover')).
		self::jsVar('dotclear.img_minus_theme_src','images/minus-theme.png').
		self::jsVar('dotclear.img_minus_theme_alt',__('hide')).

		self::jsVar('dotclear.msg.help',
			__('Need help?')).
		self::jsVar('dotclear.msg.new_window',
			__('new window')).
		self::jsVar('dotclear.msg.help_hide',
			__('Hide')).
		self::jsVar('dotclear.msg.to_select',
			__('Select:')).
		self::jsVar('dotclear.msg.no_selection',
			__('no selection')).
		self::jsVar('dotclear.msg.select_all',
			__('select all')).
		self::jsVar('dotclear.msg.invert_sel',
			__('Invert selection')).
		self::jsVar('dotclear.msg.website',
			__('Web site:')).
		self::jsVar('dotclear.msg.email',
			__('Email:')).
		self::jsVar('dotclear.msg.ip_address',
			__('IP address:')).
		self::jsVar('dotclear.msg.error',
			__('Error:')).
		self::jsVar('dotclear.msg.entry_created',
			__('Entry has been successfully created.')).
		self::jsVar('dotclear.msg.edit_entry',
			__('Edit entry')).
		self::jsVar('dotclear.msg.view_entry',
			__('view entry')).
		self::jsVar('dotclear.msg.confirm_delete_posts',
			__("Are you sure you want to delete selected entries (%s)?")).
		self::jsVar('dotclear.msg.confirm_delete_medias',
			__("Are you sure you want to delete selected medias (%d)?")).
		self::jsVar('dotclear.msg.confirm_delete_categories',
			__("Are you sure you want to delete selected categories (%s)?")).
		self::jsVar('dotclear.msg.confirm_delete_post',
			__("Are you sure you want to delete this entry?")).
		self::jsVar('dotclear.msg.click_to_unlock',
			__("Click here to unlock the field")).
		self::jsVar('dotclear.msg.confirm_spam_delete',
			__('Are you sure you want to delete all spams?')).
		self::jsVar('dotclear.msg.confirm_delete_comments',
			__('Are you sure you want to delete selected comments (%s)?')).
		self::jsVar('dotclear.msg.confirm_delete_comment',
			__('Are you sure you want to delete this comment?')).
		self::jsVar('dotclear.msg.cannot_delete_users',
			__('Users with posts cannot be deleted.')).
		self::jsVar('dotclear.msg.confirm_delete_user',
			__('Are you sure you want to delete selected users (%s)?')).
		self::jsVar('dotclear.msg.confirm_delete_category',
			__('Are you sure you want to delete category "%s"?')).
		self::jsVar('dotclear.msg.confirm_reorder_categories',
			__('Are you sure you want to reorder all categories?')).
		self::jsVar('dotclear.msg.confirm_delete_media',
			__('Are you sure you want to remove media "%s"?')).
		self::jsVar('dotclear.msg.confirm_delete_directory',
			__('Are you sure you want to remove directory "%s"?')).
		self::jsVar('dotclear.msg.confirm_extract_current',
			__('Are you sure you want to extract archive in current directory?')).
		self::jsVar('dotclear.msg.confirm_remove_attachment',
			__('Are you sure you want to remove attachment "%s"?')).
		self::jsVar('dotclear.msg.confirm_delete_lang',
			__('Are you sure you want to delete "%s" language?')).
		self::jsVar('dotclear.msg.confirm_delete_plugin',
			__('Are you sure you want to delete "%s" plugin?')).
		self::jsVar('dotclear.msg.confirm_delete_plugins',
			__('Are you sure you want to delete selected plugins?')).
		self::jsVar('dotclear.msg.use_this_theme',
			__('Use this theme')).
		self::jsVar('dotclear.msg.remove_this_theme',
			__('Remove this theme')).
		self::jsVar('dotclear.msg.confirm_delete_theme',
			__('Are you sure you want to delete "%s" theme?')).
		self::jsVar('dotclear.msg.confirm_delete_themes',
			__('Are you sure you want to delete selected themes?')).
		self::jsVar('dotclear.msg.confirm_delete_backup',
			__('Are you sure you want to delete this backup?')).
		self::jsVar('dotclear.msg.confirm_revert_backup',
			__('Are you sure you want to revert to this backup?')).
		self::jsVar('dotclear.msg.zip_file_content',
			__('Zip file content')).
		self::jsVar('dotclear.msg.xhtml_validator',
			__('XHTML markup validator')).
		self::jsVar('dotclear.msg.xhtml_valid',
			__('XHTML content is valid.')).
		self::jsVar('dotclear.msg.xhtml_not_valid',
			__('There are XHTML markup errors.')).
		self::jsVar('dotclear.msg.warning_validate_no_save_content',
			__('Attention: an audit of a content not yet registered.')).
		self::jsVar('dotclear.msg.confirm_change_post_format',
			__('You have unsaved changes. Switch post format will loose these changes. Proceed anyway?')).
		self::jsVar('dotclear.msg.confirm_change_post_format_noconvert',
			__("Warning: post format change will not convert existing content. You will need to apply new format by yourself. Proceed anyway?")).
		self::jsVar('dotclear.msg.load_enhanced_uploader',
			__('Loading enhanced uploader, please wait.')).

		self::jsVar('dotclear.msg.module_author',
			__('Author:')).
		self::jsVar('dotclear.msg.module_details',
			__('Details')).
		self::jsVar('dotclear.msg.module_support',
			__('Support')).
		self::jsVar('dotclear.msg.module_help',
			__('Help:')).
		self::jsVar('dotclear.msg.module_section',
			__('Section:')).
		self::jsVar('dotclear.msg.module_tags',
			__('Tags:')).
			"\n//]]>\n".
		"</script>\n";
	}

	public static function jsLoadIE7()
	{
		return
		'<!--[if lt IE 9]>'."\n".
		self::jsLoad('js/ie7/IE9.js').
		'<link rel="stylesheet" type="text/css" href="style/iesucks.css" />'."\n".
		'<![endif]-->'."\n";
	}

	public static function jsConfirmClose()
	{
		$args = func_get_args();
		if (count($args) > 0) {
			foreach ($args as $k => $v) {
				$args[$k] = "'".html::escapeJS($v)."'";
			}
			$args = implode(',',$args);
		} else {
			$args = '';
		}

		return
		self::jsLoad('js/confirm-close.js').
		'<script type="text/javascript">'."\n".
		"//<![CDATA[\n".
		"confirmClosePage = new confirmClose(".$args."); ".
		"confirmClose.prototype.prompt = '".html::escapeJS(__('You have unsaved changes.'))."'; ".
		"\n//]]>\n".
		"</script>\n";
	}

	public static function jsPageTabs($default=null)
	{
		if ($default) {
			$default = "'".html::escapeJS($default)."'";
		}

		return
		self::jsLoad('js/jquery/jquery.pageTabs.js').
		'<script type="text/javascript">'."\n".
		"//<![CDATA[\n".
		'$(function() {'."\n".
		'$.pageTabs('.$default.');'."\n".
		'});'.
		"\n//]]>\n".
		"</script>\n".
		'<!--[if lt IE 8]>'."\n".
		self::jsLoad('js/ie7/ie7-hashchange.js').
		'<script type="text/javascript">'."\n".
		"//<![CDATA[\n".
		'$(window).hashchange();'.
		"\n//]]>\n".
		"</script>\n".
		'<![endif]-->'."\n";
	}

	public static function jsModal()
	{
		return
		'<link rel="stylesheet" type="text/css" href="style/modal/modal.css" />'."\n".
		self::jsLoad('js/jquery/jquery.modal.js').
		'<script type="text/javascript">'."\n".
		"//<![CDATA[\n".
		self::jsVar('$.modal.prototype.params.loader_img','style/modal/loader.gif').
		self::jsVar('$.modal.prototype.params.close_img','style/modal/close.png').
		"\n//]]>\n".
		"</script>\n";
	}

	public static function jsColorPicker()
	{
		return
		'<link rel="stylesheet" type="text/css" href="style/farbtastic/farbtastic.css" />'."\n".
		self::jsLoad('js/jquery/jquery.farbtastic.js').
		self::jsLoad('js/color-picker.js');
	}

	public static function jsDatePicker()
	{
		return
		'<link rel="stylesheet" type="text/css" href="style/date-picker.css" />'."\n".
		self::jsLoad('js/date-picker.js').
		'<script type="text/javascript">'."\n".
		"//<![CDATA[\n".

		"datePicker.prototype.months[0] = '".html::escapeJS(__('January'))."'; ".
		"datePicker.prototype.months[1] = '".html::escapeJS(__('February'))."'; ".
		"datePicker.prototype.months[2] = '".html::escapeJS(__('March'))."'; ".
		"datePicker.prototype.months[3] = '".html::escapeJS(__('April'))."'; ".
		"datePicker.prototype.months[4] = '".html::escapeJS(__('May'))."'; ".
		"datePicker.prototype.months[5] = '".html::escapeJS(__('June'))."'; ".
		"datePicker.prototype.months[6] = '".html::escapeJS(__('July'))."'; ".
		"datePicker.prototype.months[7] = '".html::escapeJS(__('August'))."'; ".
		"datePicker.prototype.months[8] = '".html::escapeJS(__('September'))."'; ".
		"datePicker.prototype.months[9] = '".html::escapeJS(__('October'))."'; ".
		"datePicker.prototype.months[10] = '".html::escapeJS(__('November'))."'; ".
		"datePicker.prototype.months[11] = '".html::escapeJS(__('December'))."'; ".

		"datePicker.prototype.days[0] = '".html::escapeJS(__('Monday'))."'; ".
		"datePicker.prototype.days[1] = '".html::escapeJS(__('Tuesday'))."'; ".
		"datePicker.prototype.days[2] = '".html::escapeJS(__('Wednesday'))."'; ".
		"datePicker.prototype.days[3] = '".html::escapeJS(__('Thursday'))."'; ".
		"datePicker.prototype.days[4] = '".html::escapeJS(__('Friday'))."'; ".
		"datePicker.prototype.days[5] = '".html::escapeJS(__('Saturday'))."'; ".
		"datePicker.prototype.days[6] = '".html::escapeJS(__('Sunday'))."'; ".

		"datePicker.prototype.img_src = 'images/date-picker.png'; ".

		"datePicker.prototype.close_msg = '".html::escapeJS(__('close'))."'; ".
		"datePicker.prototype.now_msg = '".html::escapeJS(__('now'))."'; ".

		"\n//]]>\n".
		"</script>\n";
	}

	public static function jsToolBar()
	{
		$res =
		'<link rel="stylesheet" type="text/css" href="style/jsToolBar/jsToolBar.css" />'.
		'<script type="text/javascript" src="js/jsToolBar/jsToolBar.js"></script>';

		if (isset($GLOBALS['core']->auth) && $GLOBALS['core']->auth->getOption('enable_wysiwyg')) {
			$res .= '<script type="text/javascript" src="js/jsToolBar/jsToolBar.wysiwyg.js"></script>';
		}

		$res .=
		'<script type="text/javascript" src="js/jsToolBar/jsToolBar.dotclear.js"></script>'.
		'<script type="text/javascript">'."\n".
		"//<![CDATA[\n".
		"jsToolBar.prototype.dialog_url = 'popup.php'; ".
		"jsToolBar.prototype.iframe_css = '".
		'body{'.
		'font: 12px "DejaVu Sans","Lucida Grande","Lucida Sans Unicode",Arial,sans-serif;'.
		'color : #000;'.
		'background: #f9f9f9;'.
		'margin: 0;'.
		'padding : 2px;'.
		'border: none;'.
		(l10n::getTextDirection($GLOBALS['_lang']) == 'rtl' ? 'direction:rtl;' : '').
		'}'.
		'pre, code, kbd, samp {'.
		'font-family:"Courier New",Courier,monospace;'.
		'font-size : 1.1em;'.
		'}'.
		'code {'.
		'color : #666;'.
		'font-weight : bold;'.
		'}'.
		'body > p:first-child {'.
		'margin-top: 0;'.
		'}'.
		"'; ".
		"jsToolBar.prototype.base_url = '".html::escapeJS($GLOBALS['core']->blog->host)."'; ".
		"jsToolBar.prototype.switcher_visual_title = '".html::escapeJS(__('visual'))."'; ".
		"jsToolBar.prototype.switcher_source_title = '".html::escapeJS(__('source'))."'; ".
		"jsToolBar.prototype.legend_msg = '".
		html::escapeJS(__('You can use the following shortcuts to format your text.'))."'; ".
		"jsToolBar.prototype.elements.blocks.options.none = '".html::escapeJS(__('-- none --'))."'; ".
		"jsToolBar.prototype.elements.blocks.options.nonebis = '".html::escapeJS(__('-- block format --'))."'; ".
		"jsToolBar.prototype.elements.blocks.options.p = '".html::escapeJS(__('Paragraph'))."'; ".
		"jsToolBar.prototype.elements.blocks.options.h1 = '".html::escapeJS(__('Level 1 header'))."'; ".
		"jsToolBar.prototype.elements.blocks.options.h2 = '".html::escapeJS(__('Level 2 header'))."'; ".
		"jsToolBar.prototype.elements.blocks.options.h3 = '".html::escapeJS(__('Level 3 header'))."'; ".
		"jsToolBar.prototype.elements.blocks.options.h4 = '".html::escapeJS(__('Level 4 header'))."'; ".
		"jsToolBar.prototype.elements.blocks.options.h5 = '".html::escapeJS(__('Level 5 header'))."'; ".
		"jsToolBar.prototype.elements.blocks.options.h6 = '".html::escapeJS(__('Level 6 header'))."'; ".
		"jsToolBar.prototype.elements.strong.title = '".html::escapeJS(__('Strong emphasis'))."'; ".
		"jsToolBar.prototype.elements.em.title = '".html::escapeJS(__('Emphasis'))."'; ".
		"jsToolBar.prototype.elements.ins.title = '".html::escapeJS(__('Inserted'))."'; ".
		"jsToolBar.prototype.elements.del.title = '".html::escapeJS(__('Deleted'))."'; ".
		"jsToolBar.prototype.elements.quote.title = '".html::escapeJS(__('Inline quote'))."'; ".
		"jsToolBar.prototype.elements.code.title = '".html::escapeJS(__('Code'))."'; ".
		"jsToolBar.prototype.elements.br.title = '".html::escapeJS(__('Line break'))."'; ".
		"jsToolBar.prototype.elements.blockquote.title = '".html::escapeJS(__('Blockquote'))."'; ".
		"jsToolBar.prototype.elements.pre.title = '".html::escapeJS(__('Preformated text'))."'; ".
		"jsToolBar.prototype.elements.ul.title = '".html::escapeJS(__('Unordered list'))."'; ".
		"jsToolBar.prototype.elements.ol.title = '".html::escapeJS(__('Ordered list'))."'; ".

		"jsToolBar.prototype.elements.link.title = '".html::escapeJS(__('Link'))."'; ".
		"jsToolBar.prototype.elements.link.href_prompt = '".html::escapeJS(__('URL?'))."'; ".
		"jsToolBar.prototype.elements.link.hreflang_prompt = '".html::escapeJS(__('Language?'))."'; ".

		"jsToolBar.prototype.elements.img.title = '".html::escapeJS(__('External image'))."'; ".
		"jsToolBar.prototype.elements.img.src_prompt = '".html::escapeJS(__('URL?'))."'; ".

		"jsToolBar.prototype.elements.img_select.title = '".html::escapeJS(__('Media chooser'))."'; ".
		"jsToolBar.prototype.elements.post_link.title = '".html::escapeJS(__('Link to an entry'))."'; ".

		"jsToolBar.prototype.elements.removeFormat.title = '".html::escapeJS(__('Remove text formating'))."'; ";

		if (!$GLOBALS['core']->auth->check('media,media_admin',$GLOBALS['core']->blog->id)) {
			$res .= "jsToolBar.prototype.elements.img_select.disabled = true;\n";
		}

		$res .=
		"\n//]]>\n".
		"</script>\n";

		return $res;
	}

	public static function jsUpload($params=array(),$base_url=null)
	{
		if (!$base_url) {
			$base_url = path::clean(dirname(preg_replace('/(\?.*$)?/','',$_SERVER['REQUEST_URI']))).'/';
		}

		$params = array_merge($params,array(
			'sess_id='.session_id(),
			'sess_uid='.$_SESSION['sess_browser_uid'],
			'xd_check='.$GLOBALS['core']->getNonce()
		));

		return
		'<script type="text/javascript">'."\n".
		"//<![CDATA[\n".
		"dotclear.jsUpload = {};\n".
		"dotclear.jsUpload.msg = {};\n".
		self::jsVar('dotclear.msg.enhanced_uploader_activate',__('Temporarily activate enhanced uploader')).
		self::jsVar('dotclear.msg.enhanced_uploader_disable',__('Temporarily disable enhanced uploader')).
		self::jsVar('dotclear.jsUpload.msg.limit_exceeded',__('Limit exceeded.')).
		self::jsVar('dotclear.jsUpload.msg.size_limit_exceeded',__('File size exceeds allowed limit.')).
		self::jsVar('dotclear.jsUpload.msg.canceled',__('Canceled.')).
		self::jsVar('dotclear.jsUpload.msg.http_error',__('HTTP Error:')).
		self::jsVar('dotclear.jsUpload.msg.error',__('Error:')).
		self::jsVar('dotclear.jsUpload.msg.choose_file',__('Choose file')).
		self::jsVar('dotclear.jsUpload.msg.choose_files',__('Choose files')).
		self::jsVar('dotclear.jsUpload.msg.cancel',__('Cancel')).
		self::jsVar('dotclear.jsUpload.msg.clean',__('Clean')).
		self::jsVar('dotclear.jsUpload.msg.upload',__('Upload')).
		self::jsVar('dotclear.jsUpload.msg.send',__('Send')).
		self::jsVar('dotclear.jsUpload.msg.file_successfully_uploaded',__('File successfully uploaded.')).
		self::jsVar('dotclear.jsUpload.msg.no_file_in_queue',__('No file in queue.')).
		self::jsVar('dotclear.jsUpload.msg.file_in_queue',__('1 file in queue.')).
		self::jsVar('dotclear.jsUpload.msg.files_in_queue',__('%d files in queue.')).
		self::jsVar('dotclear.jsUpload.msg.queue_error',__('Queue error:')).
		self::jsVar('dotclear.jsUpload.base_url',$base_url).
		"\n//]]>\n".
		"</script>\n".

		self::jsLoad('js/jsUpload/vendor/jquery.ui.widget.js').
		self::jsLoad('js/jsUpload/tmpl.js').
		self::jsLoad('js/jsUpload/template-upload.js').
		self::jsLoad('js/jsUpload/template-download.js').
		self::jsLoad('js/jsUpload/load-image.js').
		self::jsLoad('js/jsUpload/jquery.iframe-transport.js').
		self::jsLoad('js/jsUpload/jquery.fileupload.js').
		self::jsLoad('js/jsUpload/jquery.fileupload-process.js').
		self::jsLoad('js/jsUpload/jquery.fileupload-resize.js').
		self::jsLoad('js/jsUpload/jquery.fileupload-ui.js');
	}

	public static function jsToolMan()
	{
		return
		'<script type="text/javascript" src="js/tool-man/core.js"></script>'.
		'<script type="text/javascript" src="js/tool-man/events.js"></script>'.
		'<script type="text/javascript" src="js/tool-man/css.js"></script>'.
		'<script type="text/javascript" src="js/tool-man/coordinates.js"></script>'.
		'<script type="text/javascript" src="js/tool-man/drag.js"></script>'.
		'<script type="text/javascript" src="js/tool-man/dragsort.js"></script>'.
		'<script type="text/javascript" src="js/dragsort-tablerows.js"></script>';
	}

	public static function jsMetaEditor()
	{
		return
		'<script type="text/javascript" src="js/meta-editor.js"></script>';
	}
}
