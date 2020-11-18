<?PHP

/* ====================
Seditio - Website engine
Copyright Neocrome & Seditio Team
http://www.neocrome.net
http://www.seditio.org
[BEGIN_SED]
File=admin.tools.inc.php
Version=177
Updated=2015-feb-06
Type=Core.admin
Author=Neocrome
Description=Administration panel
[END_SED]
==================== */

if ( !defined('SED_CODE') || !defined('SED_ADMIN') ) { die('Wrong URL.'); }

list($usr['auth_read'], $usr['auth_write'], $usr['isadmin']) = sed_auth('admin', 'a');
sed_block($usr['isadmin']);

$adminpath[] = array (sed_url("admin", "m=tools"), $L['adm_manage']);
$adminhelp = $L['adm_help_tools'];

$p = sed_import('p','G','ALP');

$t = new XTemplate(sed_skinfile('admin.tools', true)); 

if (!empty($p))
	{
	$path_lang_def	= "plugins/$p/lang/$p.en.lang.php";
	$path_lang_alt	= "plugins/$p/lang/$p.$lang.lang.php";

	if (@file_exists($path_lang_alt))
		{ require($path_lang_alt); }
	elseif (@file_exists($path_lang_def))
		{ require($path_lang_def); }

	$extp = array();

	if (is_array($sed_plugins))
		{
		foreach($sed_plugins as $i => $k)
			{
			if ($k['pl_hook']=='tools' && $k['pl_code']==$p)
				{ $extp[$i] = $k; }
			}
		}

	if (count($extp)==0)
		{
		sed_redirect(sed_url("message", "msg=907", "", true));
		exit;
		}

	$extplugin_info = "plugins/".$p."/".$p.".setup.php";

	if (file_exists($extplugin_info))
		{
		$info = sed_infoget($extplugin_info, 'SED_EXTPLUGIN');
		}
	else
		{
		sed_redirect(sed_url("message", "msg=907", "", true));
		exit;
		}

	$adminpath[] = array (sed_url("admin", "m=tools&p=".$p), $info['Name']);

	$t-> assign(array(	
		"TOOL_TITLE" => $info['Name'],
		"TOOL_ICON" => sed_plugin_icon($p)
	));	

	if (is_array($extp))
		{
		foreach($extp as $k => $pl)
			{
			include('plugins/'.$pl['pl_code'].'/'.$pl['pl_file'].'.php');

			$t-> assign(array(	
				"TOOL_BODY" => $plugin_body
			));
							 
			$t->parse("ADMIN_TOOL.TOOL_BODY_LIST");
			}
		}

	$adminhelp = $L['Description']." : ".$info['Description']."<br />".$L['Version']." : ".$info['Version']."<br />".$L['Date']." : ".$info['Date']."<br />".$L['Author']." : ".$info['Author']."<br />".$L['Copyright']." : ".$info['Copyright']."<br />".$L['Notes']." : ".$info['Notes'];

	$t->parse("ADMIN_TOOL");
	$adminmain .= $t -> text("ADMIN_TOOL");

	}
else
	{

$sql = sed_sql_query("SELECT DISTINCT(config_cat), COUNT(*) FROM $db_config WHERE config_owner!='plug' GROUP BY config_cat");
while ($row = sed_sql_fetchassoc($sql))
	{ $cfgentries[$row['config_cat']] = $row['COUNT(*)']; }

$sql = sed_sql_query("SELECT DISTINCT(auth_code), COUNT(*) FROM $db_auth WHERE 1 GROUP BY auth_code");
while ($row = sed_sql_fetchassoc($sql))
	{ $authentries[$row['auth_code']] = $row['COUNT(*)']; }

$sql = sed_sql_query("SELECT * FROM $db_core WHERE ct_code NOT IN ('admin', 'message', 'index', 'forums', 'users', 'plug', 'page', 'trash') ORDER BY ct_title ASC");
$lines = array();

while ($row = sed_sql_fetchassoc($sql))
	{	
	$row['ct_title_loc'] = (empty($L["core_".$row['ct_code']])) ? $row['ct_title'] : $L["core_".$row['ct_code']];
		
	if ($authentries[$row['ct_code']]>0) 
		{
		$t-> assign(array(	
			"MODULES_LIST_RIGHTS_URL" => sed_url("admin", "m=rightsbyitem&ic=".$row['ct_code']."&io=a")
		));
		$t->parse("ADMIN_TOOLS.MODULES_LIST.MODULES_LIST_RIGHTS");		
		}
			
	if ($cfgentries[$row['ct_code']]>0)
		{
		$t-> assign(array(
			"MODULES_LIST_CONFIG_URL" => sed_url("admin", "m=config&n=edit&o=core&p=".$row['ct_code'])
		));				
		$t->parse("ADMIN_TOOLS.MODULES_LIST.MODULES_LIST_CONFIG");
		}	
		
	$t-> assign(array(	
		"MODULES_LIST_URL" => sed_url("admin", "m=".$row['ct_code']),
		"MODULES_LIST_CODE" => $row['ct_code'],
		"MODULES_LIST_TITLE" => $row['ct_title_loc']
	));	
			
	$t->parse("ADMIN_TOOLS.MODULES_LIST");	
	}

	$t->assign(array(	
		"MODULES_LIST_BANLIST_URL" => sed_url("admin", "m=banlist")
	));
	
	$t->parse("ADMIN_TOOLS.MODULES_LIST_BANLIST");

	$t->assign(array(	
		"MODULES_LIST_CACHE_URL" => sed_url("admin", "m=cache")
	));
	
	$t->parse("ADMIN_TOOLS.MODULES_LIST_CACHE");

	$t->assign(array(	
		"MODULES_LIST_SMILIES_URL" => sed_url("admin", "m=smilies")
	));
	
	$t->parse("ADMIN_TOOLS.MODULES_LIST_SMILIES");

	$t->assign(array(	
		"MODULES_LIST_HITS_URL" => sed_url("admin", "m=hits")
	));
	
	$t->parse("ADMIN_TOOLS.MODULES_LIST_HITS");

	$t->assign(array(	
		"MODULES_LIST_REFERERS_URL" => sed_url("admin", "m=referers")
	));
	
	$t->parse("ADMIN_TOOLS.MODULES_LIST_REFERERS");

	$plugins = array();

	function cmp ($a, $b, $k=1)
		{
		if ($a[$k] == $b[$k]) return 0;
		return ($a[$k] < $b[$k]) ? -1 : 1;
		}

	/* === Hook === */
	$extp = sed_getextplugins('tools');

	if (is_array($extp))
		{
		$sql = sed_sql_query("SELECT DISTINCT(config_cat), COUNT(*) FROM $db_config WHERE config_owner='plug' GROUP BY config_cat");
		while ($row = sed_sql_fetchassoc($sql))
			{ $cfgentries[$row['config_cat']] = $row['COUNT(*)']; }
		
		
		foreach($extp as $k => $pl)
			{ $plugins[]= array ($pl['pl_code'], $pl['pl_title']); }

		usort($plugins, "cmp");

		//while (list($i,$x) = each($plugins))
			foreach($plugins as $i => $x)
			{
			$extplugin_info = "plugins/".$x[0]."/".$x[0].".setup.php";

			if (file_exists($extplugin_info))
				{
				$info = sed_infoget($extplugin_info, 'SED_EXTPLUGIN');
				}
			else
				{
				include ("system/lang/".$usr['lang']."/message.lang.php");
				$info['Name'] = $x[0]." : ".$L['msg907_1'];
				}
			
			if ($cfgentries[$info['Code']] > 0)
				{
				$t-> assign(array(	
					"TOOLS_LIST_CONFIG_URL" => sed_url("admin", "m=config&n=edit&o=plug&p=".$info['Code'])
				));				
				$t->parse("ADMIN_TOOLS.TOOLS_LIST.TOOLS_LIST_CONFIG");						
				}
				
			$t-> assign(array(				
				"TOOLS_LIST_URL" => sed_url("admin", "m=tools&p=".$x[0]),
				"TOOLS_LIST_TITLE" => $info['Name'],
				"TOOLS_LIST_ICON" => sed_plugin_icon($x[0])
			));

			$t->parse("ADMIN_TOOLS.TOOLS_LIST");						
			}
		}
	else
		{
		$adminmain = $L['adm_listisempty'];
		}
		
	$t->parse("ADMIN_TOOLS");
	$adminmain .= $t -> text("ADMIN_TOOLS");		
	}
		
?>
