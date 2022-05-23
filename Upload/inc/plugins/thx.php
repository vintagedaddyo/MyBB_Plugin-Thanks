<?php
/*
* MyBB: Thanks
*
* File: thx.php
*
* Authors: Huji Lee, AliReza Tofighi, SaeedGh, Vintagedaddyo, effone
*
* MyBB Version: 1.8
*
* Plugin Version: 3.9.4
*
*/

if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.");
}

if(isset($GLOBALS['templatelist']))
{
	$GLOBALS['templatelist'] .= ", thanks_postbit_count";
}

$plugins->add_hook("postbit", "thx");
$plugins->add_hook("xmlhttp", "do_action");
$plugins->add_hook("showthread_start", "direct_action");
$plugins->add_hook("class_moderation_delete_post", "deletepost_edit");
$plugins->add_hook('admin_tools_action_handler', 'thx_admin_action');
$plugins->add_hook('admin_tools_menu', 'thx_admin_menu');
$plugins->add_hook('admin_tools_permissions', 'thx_admin_permissions');
$plugins->add_hook('admin_load', 'thx_admin');

function thx_info()
{
	global $lang;
	$lang->load("thx");
	$lang->thx_desc = '<form action="https://www.paypal.com/cgi-bin/webscr" method="post" style="float:right;">' . '<input type="hidden" name="cmd" value="_s-xclick">' . '<input type="hidden" name="hosted_button_id" value="AZE6ZNZPBPVUL">' . '<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_SM.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">' . '<img alt="" border="0" src="https://www.paypalobjects.com/pl_PL/i/scr/pixel.gif" width="1" height="1">' . '</form>' . $lang->thx_desc;
	return Array(
		'name' => $lang->thx_name,
		'description' => $lang->thx_desc,
		'website' => $lang->thx_web,
		'author' => $lang->thx_auth,
		'authorsite' => $lang->thx_authsite,
		'version' => $lang->thx_ver,
		'compatibility' => $lang->thx_compat
	);
}


function thx_install()
{
	global $db;
	
	$db->query("CREATE TABLE IF NOT EXISTS ".TABLE_PREFIX."thx ( 
		txid INT UNSIGNED NOT NULL AUTO_INCREMENT, 
		uid int(10) UNSIGNED NOT NULL, 
		adduid int(10) UNSIGNED NOT NULL, 
		pid int(10) UNSIGNED NOT NULL, 
		time bigint(30) NOT NULL DEFAULT '0', 
		PRIMARY KEY (`txid`), 
		INDEX (`adduid`, `pid`, `time`) 
		);"
	);
	
	if(!$db->field_exists("thx", "users"))
	{
		$sq[] = "ALTER TABLE ".TABLE_PREFIX."users ADD `thx` INT NOT NULL DEFAULT '0', ADD `thxcount` INT NOT NULL DEFAULT '0', ADD `thxpost` INT NOT NULL DEFAULT '0'";
	}
	elseif (!$db->field_exists("thxpost", "users"))		
	{
		$sq[] = "ALTER TABLE ".TABLE_PREFIX."users ADD `thxpost` INT NOT NULL DEFAULT '0'";
	}
	
	if($db->field_exists("thx", "posts"))
	{
		$sq[] = "ALTER TABLE ".TABLE_PREFIX."posts DROP thx";
	}
	
	if(!$db->field_exists("pthx", "posts"))
	{
		$sq[] = "ALTER TABLE ".TABLE_PREFIX."posts ADD `pthx` INT(10) NOT NULL DEFAULT '0'";
	}
	
	if(is_array($sq))
	{
		foreach($sq as $q)
		{
			$db->query($q);
		}
	}
}


function thx_is_installed()
{
	global $db;
	if($db->field_exists('thxpost', "users"))
	{
		return true;
	}
	return false;
}


function thx_activate()
{
	global $settings, $mybb, $db, $lang;
	$lang->load("thx");	

	//Update from v3.8
	$thx_tbl_keys = $db->query("SHOW KEYS FROM ".TABLE_PREFIX."thx WHERE Key_name='adduid'");
	
	if(!$db->fetch_field($thx_tbl_keys, "Key_name"))
	{
		$db->query("ALTER TABLE ".TABLE_PREFIX."thx ADD INDEX (`adduid`, `pid`, `time`)");
	}
	
	//Adding templates
	require MYBB_ROOT."inc/adminfunctions_templates.php";
	
	find_replace_templatesets("postbit", '#button_delete_pm([^\<]*)<\/div>#is', 'button_delete_pm$1</div>{$post[\'thxdsp_outline\']}');
	find_replace_templatesets("postbit", '#'.preg_quote('{$post[\'button_quote\']}').'#', '{$post[\'button_quote\']}{$post[\'thanks\']}');
//	find_replace_templatesets("postbit_classic", '#button_delete_pm([^\<]*)<\/div>#is', 'button_delete_pm$1}$2</div>{$post[\'thxdsp_outline\']}');
//	find_replace_templatesets("postbit_classic", '#'.preg_quote('{$post[\'button_quote\']}').'#', '{$post[\'button_quote\']}{$post[\'thanks\']}');
	find_replace_templatesets("postbit_classic", '#button_delete_pm([^\<]*)<\/div>#is', 'button_delete_pm$1</div>{$post[\'thxdsp_outline\']}');
	find_replace_templatesets("postbit_classic", '#'.preg_quote('{$post[\'button_quote\']}').'#', '{$post[\'button_quote\']}{$post[\'thanks\']}');
		
	find_replace_templatesets("headerinclude", "#".preg_quote('{$stylesheets}').'#',
		'<link type="text/css" rel="stylesheet" href="cache/themes/global/thanks/thx.css" />
<script type="text/javascript" src="jscripts/thx.js"></script>
{$stylesheets}');

	find_replace_templatesets("showthread", "#".preg_quote('{$headerinclude}').'#',
		'{$headerinclude}
<script type="text/javascript">
// <!--
	lang.processing = \'{$lang->thx_processing}\';
// -->
</script>');

	$templatearray = array(
		'title' => 'thanks_postbit_count',
		'template' => "<div><span class=\"smalltext\">{\$lang->thx_thank} {\$post[\'thank_count\']}<br />
	{\$post[\'thanked_count\']}<br /></span></div>",
		'sid' => '-1',
		);
	$db->insert_query("templates", $templatearray);

	$templatearray = array(
		'title' => 'thanks_postbit_outline',
		'template' => "<div id=\"thx_list{\$post[\'pid\']}\" class=\"thx_list {\$display_style}\"><span class=\"smalltext thx_list_lable\">{\$lang->thx_givenby}</span><span id=\"thx_entry{\$post[\'pid\']}\">\$entries</span>
		</div>",
		'sid' => '-1',
		);
	$db->insert_query("templates", $templatearray);
	
	$thx_group = array(
		'name'			=> 'Thanks',
		'title' => $lang->thx_title_setting_group,
		'description' => $lang->thx_description_setting_group,
		'disporder'		=> '3',
		'isdefault'		=> '1'
	);	
	$db->insert_query("settinggroups", $thx_group);
	$gid = $db->insert_id();
	
	$thx[]= array(
		'name'			=> 'thx_active',
		'title' => $lang->thx_title_setting_1,
		'description' => $lang->thx_description_setting_1,
		'optionscode' 	=> 'onoff',
		'value'			=> '1',
		'disporder'		=> '1',
		'gid'			=> intval($gid)
	);
	
	$thx[] = array(
		'name'			=> 'thx_count',
		'title' => $lang->thx_title_setting_2,
		'description' => $lang->thx_description_setting_2,
		'optionscode' 	=> 'onoff',
		'value'			=> '1',
		'disporder'		=> '2',
		'gid'			=> intval($gid)
	);
	
	$thx[] = array(
		'name'			=> 'thx_del',
		'title' => $lang->thx_title_setting_3,
		'description' => $lang->thx_description_setting_3,
		'optionscode' 	=> 'onoff',
		'value'			=> '1',
		'disporder'		=> '3',
		'gid'			=> intval($gid)
	);
	
	$thx[] = array(
		'name'			=> 'thx_hidemode',
		'title' => $lang->thx_title_setting_4,
		'description' => $lang->thx_description_setting_4,
		'optionscode' 	=> 'onoff',
		'value'			=> '1',
		'disporder'		=> '4',
		'gid'			=> intval($gid)
	);
	
	foreach($thx as $t)
	{
		$db->insert_query("settings", $t);
	}
	
	rebuild_settings();
}


function thx_deactivate()
{
	global $db;
	require '../inc/adminfunctions_templates.php';
	
	find_replace_templatesets("postbit", '#'.preg_quote('{$post[\'thxdsp_outline\']}').'#', '', 0);
	find_replace_templatesets("postbit", '#'.preg_quote('{$post[\'thanks\']}').'#', '', 0);
	find_replace_templatesets("postbit_classic", '#'.preg_quote('{$post[\'thxdsp_outline\']}').'#', '', 0);
	find_replace_templatesets("postbit_classic", '#'.preg_quote('{$post[\'thanks\']}').'#', '', 0);
	find_replace_templatesets("headerinclude", "#".preg_quote('<link type="text/css" rel="stylesheet" href="cache/themes/global/thanks/thx.css" />
<script type="text/javascript" src="jscripts/thx.js"></script>
').'#', '', 0);
	find_replace_templatesets("showthread", "#".preg_quote('
<script type="text/javascript">
// <!--
	lang.processing = \'{$lang->thx_processing}\';
// -->
</script>').'#', '', 0);

	
	$db->delete_query("settings", "name IN ('thx_active', 'thx_count', 'thx_del', 'thx_hidemode')");
	$db->delete_query("settinggroups", "name='Thanks'");
	$db->delete_query("templates", "title='thanks_postbit_count'");
	$db->delete_query("templates", "title='thanks_postbit_outline'");
	
	rebuild_settings();
}


function thx_uninstall()
{
	global $db;

	/*$db->query("drop TABLE ".TABLE_PREFIX."thx");*/

	if($db->field_exists("thx", "users"))
	{
		$db->query("ALTER TABLE ".TABLE_PREFIX."users DROP thx, DROP thxcount, DROP thxpost");
	}
	
	if($db->field_exists("pthx", "posts"))
	{
		$db->query("ALTER TABLE ".TABLE_PREFIX."posts DROP pthx");
	}
}


function thx(&$post) 
{
	global $db, $mybb, $lang ,$session, $theme, $altbg, $templates, $thx_cache;
	
	if(!$mybb->settings['thx_active'] || !empty($session->is_spider))
	{
		return false;
	}
	
	$lang->load("thx");
	
	if($b = $post['pthx'])
	{
		$entries = build_thank($post['pid'], $b);
	}
	else 
	{
		$entries = "";
	}
	
 	if($mybb->user['uid'] != 0 && $mybb->user['uid'] != $post['uid']) 
	{
		if(!$b)
		{
			$post['thanks'] = "<a class=\"thx_btn_add\" data-thx=\"{$post['pid']}\" href=\"showthread.php?action=thank&tid={$post['tid']}&pid={$post['pid']}\"><span>$lang->thx_main</span></a>";
		}
		else if($mybb->settings['thx_del'] == "1")
		{
			$post['thanks'] = "<a class=\"thx_btn_remove\" data-thx=\"{$post['pid']}\" href=\"showthread.php?action=remove_thank&tid={$post['tid']}&pid={$post['pid']}\"><span>$lang->thx_remove</span></a>";
		}
		else
		{
			$post['thanks'] = "<!-- remove thanks disabled by administrator -->";
		}
	}
	
	$display_style = $entries ?  "" : "hide";
	// 44 sort warning
    $post['thanks'] = isset($post['thanks']) ? $post['thanks'] : ''; 
    // 302 sort warning
    $post['thxdsp_outline'] = isset($post['thxdsp_outline']) ? $post['thxdsp_outline'] : '';
	eval("\$post['thxdsp_outline'] .= \"".$templates->get("thanks_postbit_outline")."\";");
	
	if($mybb->settings['thx_count'] == "1")
	{
		if(!isset($thx_cache['postbit'][$post['uid']]))
		{
			$post['thank_count'] = $post['thx'];
			$post['thanked_count'] = $lang->sprintf($lang->thx_thanked_count, $post['thxcount'], $post['thxpost']);
			eval("\$x = \"".$templates->get("thanks_postbit_count")."\";");
			$thx_cache['postbit'][$post['uid']] = $x;
		}
		
		$post['user_details'] .= $thx_cache['postbit'][$post['uid']];
	}
}

function do_action()
{
	global $mybb, $lang, $theme;
	
	if(($mybb->input['action'] != "thankyou"  &&  $mybb->input['action'] != "remove_thankyou") || $mybb->request_method != "post")
	{
		return false;
	}
		
	if(file_exists($lang->path."/".$lang->language."/thx.lang.php"))	
	{
		$lang->load("thx");
	}
	else 
	{
		$l = $lang->language;
		$lang->set_language();
		$lang->load("thx");
		$lang->set_language($l);
	}
	
	$pid = intval($mybb->input['pid']);
	
	if ($mybb->input['action'] == "thankyou" )
	{
		do_thank($pid);
	}
	else if($mybb->settings['thx_del'] == "1")
	{
		del_thank($pid);
	}
	
	$nonead = 0;
	$list = build_thank($pid, $nonead);
	header('Content-Type: text/xml');
	$output = "<thankyou>
	<list><![CDATA[$list]]></list>
	<display>".($list ? "1" : "0")."</display>
	<pid>$pid</pid>
	<btnclass>".($mybb->input['action'] == "thankyou" ? "thx_btn_remove" : "thx_btn_add")."</btnclass>
	<btntext>".($mybb->input['action'] == "thankyou" ? $lang->thx_remove : $lang->thx_main)."</btntext>
	<del>{$mybb->settings['thx_del']}</del>	
</thankyou>";
	echo $output;
}

function direct_action()
{
	global $mybb, $lang;
	
	if($mybb->input['action'] != "thank"  &&  $mybb->input['action'] != "remove_thank")
	{
		return false;
	}
		
	if(file_exists($lang->path."/".$lang->language."/thx.lang.php"))	
	{
		$lang->load("thx");
	}
	else 
	{
		$l = $lang->language;
		$lang->set_language();
		$lang->load("thx");
		$lang->set_language($l);
	}
	$pid=intval($mybb->input['pid']);
	
	if($mybb->input['action'] == "thank" )
	{
		do_thank($pid);
	}
	else if($mybb->settings['thx_del'] == "1")
	{
		del_thank($pid);
	}
	redirect($_SERVER['HTTP_REFERER']);
}

function build_thank($pid, &$is_thx)
{
	global $db, $mybb, $lang, $thx_cache;
	$is_thx = 0;
	
	$pid = intval($pid);
	
	if(file_exists($lang->path."/".$lang->language."/thx.lang.php"))
	{
		$lang->load("thx");
	}
	else
	{
		$l=$lang->language;
		$lang->set_language();
		$lang->load("thx");
		$lang->set_language($l);
	}
	$dir = $lang->thx_dir;
	
	$query=$db->query("SELECT th.txid, th.uid, th.adduid, th.pid, th.time, u.username, u.usergroup, u.displaygroup 
		FROM ".TABLE_PREFIX."thx th 
		JOIN ".TABLE_PREFIX."users u 
		ON th.adduid=u.uid 
		WHERE th.pid='$pid' 
		ORDER BY th.time ASC" 
	);

	while($record = $db->fetch_array($query))
	{
		if($record['adduid'] == $mybb->user['uid'])
		{
			$is_thx++;
		}
		$date = my_date($mybb->settings['dateformat'].' '.$mybb->settings['timeformat'], $record['time']);
		if(!isset($thx_cache['showname'][$record['username']]))
		{
			$url = get_profile_link($record['adduid']);
			$name = format_name($record['username'], $record['usergroup'], $record['displaygroup']);
			$thx_cache['showname'][$record['username']] = "<a href=\"$url\" dir=\"$dir\">$name</a>";
		}
        // 444 sort warning
        $r1comma = isset($r1comma) ? $r1comma : ''; 
        // 445 sort warning
        $entries = isset($entries) ? $entries : ''; 
		if($mybb->settings['thx_hidemode'])
		{
			$entries .= $r1comma." <span title=\"".$date."\">".$thx_cache['showname'][$record['username']]."</span>";
		}
		else
		{
			$entries .= $r1comma.$thx_cache['showname'][$record['username']]." <span class=\"smalltext\">(".$date.")</span>";
		}
		
		$r1comma = $lang->thx_comma;
	}
	
	return $entries;
}

function do_thank($pid)
{
	global $db, $mybb;
	
	$pid = intval($pid);
	
	$check_query = $db->simple_select("thx", "count(*) as c" ,"adduid='{$mybb->user['uid']}' AND pid='$pid'", array("limit"=>"1"));
			
	$tmp=$db->fetch_array($check_query);
	if($tmp['c'] != 0)
	{
		return false;
	}
		
	$check_query = $db->simple_select("posts", "uid", "pid='$pid'", array("limit"=>1));
	if($db->num_rows($check_query) == 1)
	{
		
		$tmp=$db->fetch_array($check_query);
		
		if($tmp['uid'] == $mybb->user['uid'])
		{
			return false;
		}		
			
		$database = array (
			"uid" =>$tmp['uid'],
			"adduid" => $mybb->user['uid'],
			"pid" => $pid,
			"time" => time()
		);
		
		unset($tmp);
		
		$sq = array (
			"UPDATE ".TABLE_PREFIX."users SET thx=thx+1 WHERE uid='{$mybb->user['uid']}' LIMIT 1",
			"UPDATE ".TABLE_PREFIX."users SET thxcount=thxcount+1, thxpost=CASE( SELECT COUNT(*) FROM ".TABLE_PREFIX."thx WHERE pid='{$pid}' LIMIT 1) WHEN 0 THEN thxpost+1 ELSE thxpost END WHERE uid='{$database['uid']}' LIMIT 1",
			"UPDATE ".TABLE_PREFIX."posts SET pthx=pthx+1 WHERE pid='{$pid}' LIMIT 1"
		);
				  
		foreach($sq as $q)
		{
			$db->query($q);
		}
		$db->insert_query("thx", $database);
	}	
}

function del_thank($pid)
{
	global $mybb, $db;
	
	$pid = intval($pid);
	if($mybb->settings['thx_del'] != "1")
	{
		return false;
	}

	$check_query = $db->simple_select("thx", "`uid`, `txid`" ,"adduid='{$mybb->user['uid']}' AND pid='$pid'", array("limit"=>"1"));		
	
	if($db->num_rows($check_query))
	{
		$data = $db->fetch_array($check_query);
		$uid = intval($data['uid']);
		$thxid = intval($data['txid']);
		unset($data);
		
		$sq = array (
			"UPDATE ".TABLE_PREFIX."users SET thx=thx-1 WHERE uid='{$mybb->user['uid']}' LIMIT 1",
			"UPDATE ".TABLE_PREFIX."users SET thxcount=thxcount-1, thxpost=CASE(SELECT COUNT(*) FROM ".TABLE_PREFIX."thx WHERE pid='{$pid}' LIMIT 1) WHEN 0 THEN thxpost-1 ELSE thxpost END WHERE uid='{$uid}' LIMIT 1",
			"UPDATE ".TABLE_PREFIX."posts SET pthx=pthx-1 WHERE pid='{$pid}' LIMIT 1"
		);
		
		$db->delete_query("thx", "txid='{$thxid}'", "1");
		
		foreach($sq as $q)
		{
			$db->query($q);
		}
	}
}

function deletepost_edit($pid)
{
	global $db;
	
	$pid = intval($pid);
	$q = $db->simple_select("thx", "uid, adduid", "pid='{$pid}'");
	
	$postnum = $db->num_rows($q);
	if($postnum <= 0)
	{
		return false;
	}
	
	$adduids = array();
	
	while($r = $db->fetch_array($q))
	{
		$uid = intval($r['uid']);
		$adduids[] = $r['adduid'];
	}
	
	$adduids = implode(", ", $adduids);
	
	$sq = array();
	$sq[] = "UPDATE ".TABLE_PREFIX."users SET thxcount=thxcount-1, thxpost=thxpost-1 WHERE uid='{$uid}'";
	$sq[] = "UPDATE ".TABLE_PREFIX."users SET thx=thx-1 WHERE uid IN ({$adduids})";
	
	foreach($sq as $q)
	{
		$db->query($q);
	}
	
	$db->delete_query("thx", "pid={$pid}", $postnum);
	
}

function thx_admin_action(&$action)
{
	$action['recount_thanks'] = array ('active'=>'recount_thanks');
}

function thx_admin_menu(&$sub_menu)
{
	$sub_menu['45'] = array	(
		'id'	=> 'recount_thanks',
		'title'	=> 'Recount thanks',
		'link'	=> 'index.php?module=tools/recount_thanks'
	);
}

function thx_admin_permissions(&$admin_permissions)
{
	$admin_permissions['recount_thanks'] = 'Can recount thanks';
}

function thx_admin()
{
	global $mybb, $page, $db;
	require_once MYBB_ROOT.'inc/functions_rebuild.php';
	if($page->active_action != 'recount_thanks')
	{
		return false;
	}

	if($mybb->request_method == "post")
	{
		if(!isset($mybb->input['page']) || intval($mybb->input['page']) < 1)
		{
			$mybb->input['page'] = 1;
		}
		if(isset($mybb->input['do_recountthanks']))
		{
			if(!intval($mybb->input['thx_chunk_size']))
			{
				$mybb->input['thx_chunk_size'] = 500;
			}
			
			do_recount();
		}
		else if(isset($mybb->input['do_recountposts']))
		{
			if(!intval($mybb->input['post_chunk_size']))
			{
				$mybb->input['post_chunk_size'] = 500;
			}
			
			do_recount_post();
		}
	}
	
	$page->add_breadcrumb_item('Recount thanks', "index.php?module=tools/recount_thanks");
	$page->output_header('Recount thanks');
	
	$sub_tabs['thankyoulike_recount'] = array(
		'title'			=> 'Recount thanks',
		'link'			=> "index.php?module=tools/recount_thanks",
		'description'	=> 'Update the thanks counters'
	);
	
	$page->output_nav_tabs($sub_tabs, 'thankyoulike_recount');

	$form = new Form("index.php?module=tools/recount_thanks", "post");
	
	$form_container = new FormContainer('Recount thanks');
	$form_container->output_row_header('Name');
	$form_container->output_row_header('Chunk size', array('width' => 50));
	$form_container->output_row_header("&nbsp;");
	
	$form_container->output_cell("<label>Update thanks counters</label>
	<div class=\"description\">Updates the counters for the number of thanks given/received by users and the number of thanks given to each post.</div>");
	$form_container->output_cell($form->generate_text_box("thx_chunk_size", 100, array('style' => 'width: 150px;')));
	$form_container->output_cell($form->generate_submit_button('Go', array("name" => "do_recountthanks")));
	$form_container->construct_row();
	
	$form_container->output_cell("<label>Update post counters</label>
	<div class=\"description\">Updates the numer of posts in which a user has received thanks.</div>");
	$form_container->output_cell($form->generate_text_box("post_chunk_size", 500, array('style' => 'width: 150px;')));
	$form_container->output_cell($form->generate_submit_button('Go', array("name" => "do_recountposts")));
	$form_container->construct_row();
	
	$form_container->end();

	$form->end();
		
	$page->output_footer();

	exit;
}

function do_recount()
{
	global $db, $mybb;
	
	$cur_page = intval($mybb->input['page']);
	$per_page = intval($mybb->input['thx_chunk_size']);
	$start = ($cur_page-1) * $per_page;
	$end = $start + $per_page;
	
	if ($cur_page == 1)
	{
		$db->write_query("UPDATE ".TABLE_PREFIX."users SET thx='0', thxcount='0'");
		$db->write_query("UPDATE ".TABLE_PREFIX."posts SET pthx='0'");
	}
	
	$query = $db->simple_select("thx", "COUNT(txid) AS thx_count");
	$thx_count = $db->fetch_field($query, 'thx_count');
	
	$query = $db->query("
		SELECT uid, adduid, pid 
		FROM ".TABLE_PREFIX."thx 
		ORDER BY time ASC 
		LIMIT $start, $per_page 
	");
	
	$post_thx = array();
	$user_thx = array();
	$user_thx_to = array();
	
	while($thx = $db->fetch_array($query))
	{
		if($post_thx[$thx['pid']])
		{
			$post_thx[$thx['pid']]++;
		}
		else
		{
			$post_thx[$thx['pid']] = 1;
		}
		if($user_thx[$thx['adduid']])
		{
			$user_thx[$thx['adduid']]++;
		}
		else
		{
			$user_thx[$thx['adduid']] = 1;
		}
		if($user_thx_to[$thx['uid']])
		{
			$user_thx_to[$thx['uid']]++;
		}
		else
		{
			$user_thx_to[$thx['uid']] = 1;
		}
	}
	
	if(is_array($post_thx))
	{
		foreach($post_thx as $pid => $change)
		{
			$db->write_query("UPDATE ".TABLE_PREFIX."posts SET pthx=pthx+$change WHERE pid='$pid'");
		}
	}
	if(is_array($user_thx))
	{
		foreach($user_thx as $adduid => $change)
		{
			$db->write_query("UPDATE ".TABLE_PREFIX."users SET thx=thx+$change WHERE uid='$adduid'");
		}
	}
	if(is_array($user_thx_to))
	{
		foreach($user_thx_to as $uid => $change)
		{
			$db->write_query("UPDATE ".TABLE_PREFIX."users SET thxcount=thxcount+$change WHERE uid='$uid'");
		}
	}
	my_check_proceed($thx_count, $end, $cur_page+1, $per_page, "thx_chunk_size", "do_recountthanks", "Successfully updated the thanks counters");
}

function do_recount_post()
{
	global $db, $mybb;
	
	$cur_page = intval($mybb->input['page']);
	$per_page = intval($mybb->input['post_chunk_size']);
	$start = ($cur_page-1) * $per_page;
	$end = $start + $per_page;
	
	if ($cur_page == 1)
	{
		$db->write_query("UPDATE ".TABLE_PREFIX."users SET thxpost='0'");
	}
	
	$query = $db->simple_select("thx", "COUNT(distinct pid) AS post_count");
	$post_count = $db->fetch_field($query, 'post_count');
	
	$query = $db->query("
		SELECT uid, pid 
		FROM ".TABLE_PREFIX."thx 
		GROUP BY uid, pid 
		ORDER BY pid ASC 
		LIMIT $start, $per_page 
	");

	while($thx = $db->fetch_array($query))
	{
		$db->write_query("UPDATE ".TABLE_PREFIX."users SET thxpost=thxpost+1 WHERE uid='{$thx['uid']}'");
	}
	
	my_check_proceed($post_count, $end, $cur_page+1, $per_page, "post_chunk_size", "do_recountposts", "Successfully updated the post counters");
}

function my_check_proceed($current, $finish, $next_page, $per_page, $name_chunk, $name_submit, $message)
{
	global $page;
	
	if($finish >= $current)
	{
		flash_message($message, 'success');
		admin_redirect("index.php?module=tools/recount_thanks");
	}
	else
	{
		$page->output_header();
		
		$form = new Form("index.php?module=tools/recount_thanks", 'post');
		
		echo $form->generate_hidden_field("page", $next_page);
		echo $form->generate_hidden_field($name_chunk, $per_page);
		echo $form->generate_hidden_field($name_submit, "Go");
		echo "<div class=\"confirm_action\">\n";
		echo "<p>Click \"Proceed\" to continue the recount and rebuild process.</p>\n";
		echo "<br />\n";
		echo "<p class=\"buttons\">\n";
		echo $form->generate_submit_button("Proceed", array('class' => 'button_yes'));
		echo "</p>\n";
		echo "</div>\n";
		
		$form->end();
		
		$page->output_footer();
		exit;
	}
}

?>