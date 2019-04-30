<?php
/*
Author: itsmeJAY
Year: 2019
Version tested: 1.8.19
Contact and support exclusively in the MyBB.de Forum (German Community - https://www.mybb.de/). 
*/


if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.");
}

// Hooks and Functions
$plugins->add_hook("parse_message", "hideurls_hide");
$plugins->add_hook("postbit", "hideurls_single");


function hideurls_info()
{
global $db, $lang;
// Sprachdatei laden
$lang->load("hideurls");

	return array(
		"name"			=> "$lang->hurl_name",
		"description"	=> "$lang->hurl_desc",
		"website"		=> "https://www.mybb.de/forum/user-10220.html",
		"author"		=> "itsmeJAY",
		"authorsite"	=> "https://www.mybb.de/forum/user-10220.html",
		"version"		=> "1.5",
		"guid" 			=> "",
		"codename"		=> "hideurls",
		"compatibility" => "18*"
	);
}

function hideurls_activate()
{
  global $db, $mybb, $lang;
// Sprachdatei laden
$lang->load("hideurls");

  $setting_group = array(
      'name' => 'hideurlsgroup',
      'title' => "$lang->hurl_sg_title",
      'description' => "$lang->hurl_sg_desc",
      'disporder' => 5,
      'isdefault' => 0
  );
  
  $gid = $db->insert_query("settinggroups", $setting_group);
  
  // Einstellungen

  $setting_array = array(
    // hurl_plugin_enable oder nicht?
    'hurl_plugin_enable' => array(
        'title' => "$lang->hurl_enable_title",
        'description' => "$lang->hurl_enable_desc",
        'optionscode' => 'yesno',
        'value' => 1, // Default
        'disporder' => 1
    ),
    // ID der Gruppe
    'hurl_groups_ids' => array(
        'title' => "$lang->hurl_id_title",
        'description' => "$lang->hurl_id_desc",
        'optionscode' => "groupselect",
        'value' => 1,
        'disporder' => 2
    ),
    // Text welcher angezeigt wird
    'hurl_text_hideurls' => array(
        'title' => "$lang->hurl_text_title",
        'description' => "$lang->hurl_text_desc",
        'optionscode' => 'textarea',
        'value' => "$lang->hurl_posts_text",
        'disporder' => 3
    ),
        // Welche Beiträge sollen ersetzt werden?
        'hurl_select_posts' => array(
            'title' => "$lang->hurl_posts_title",
            'description' => "$lang->hurl_posts_desc",
            'optionscode' => "select\n0=$lang->hurl_posts_all\n1=$lang->hurl_posts_fe\n2=$lang->hurl_posts_si\n3=$lang->hurl_posts_fesi",
            'value' => "0",
            'disporder' => 4
        ),
        // Welche Beiträge sollen ersetzt werden?
                'hurl_whitelist' => array(
                    'title' => "$lang->hurl_whitelist_title",
                    'description' => "$lang->hurl_whitelist_desc",
                    'optionscode' => "textarea",
                    'value' => "www.google.com\ngoogle.com\nwww.mybb.de\nmybb.de\nwww.mybb.com\nmybb.com",
                    'disporder' => 5
                ),
);

    // Einstellungen in Datenbank speichern
    foreach($setting_array as $name => $setting)
    {
        $setting['name'] = $name;
        $setting['gid'] = $gid;
    
        $db->insert_query('settings', $setting);
    }

    // Rebuild Settings! :-)
    rebuild_settings();

}

function hideurls_deactivate()
{

  global $db;

  $db->delete_query('settings', "name IN ('hurl_plugin_enable','hurl_groups_ids','hurl_text_hideurls', 'hurl_select_posts', 'hurl_whitelist')");
  $db->delete_query('settinggroups', "name = 'hideurlsgroup'");
  
  // Rebuild Settings! :-)
  rebuild_settings();

}

// Ersetzen von Beiträgen sofern "Alle" gewählt wurden

function hide_now(&$a) {
  $a = preg_replace_callback("#<\s*?a\b[^>]*>(.*?)</a\b[^>]*>#s", function ($treffer) {
    global $settings, $mybb;

    if(strpos($treffer[1],"://") == false && substr($treffer[1],0,1)!="/") {
      $treffer[1] = "http://" . $treffer[1];  
    }

    $whitelist = preg_split('/\s+/', $mybb->settings['hurl_whitelist']);

    $parsedurl = parse_url($treffer[1]);
    
    if (in_array($parsedurl['host'], $whitelist)) {
      return $treffer[0];
    } else {
      return "{$mybb->settings['hurl_text_hideurls']}";
    }
  }, $a);
}

// Funktionsaufrufe
function hideurls_hide(&$message)
{
	global $settings, $mybb;
	
	if ($mybb->settings['hurl_plugin_enable'] == "1" && $mybb->settings['hurl_select_posts'] == "0")
	{
        if (in_array($mybb->user['usergroup'], explode(',',$mybb->settings['hurl_groups_ids'])) OR $mybb->settings['hurl_groups_ids'] == "-1")
		{
                hide_now($message);
                
    }
	}
}

// Ersetze einzelne Beiträge
function hideurls_single(&$post)
{
    global $settings, $mybb;
    
    if ($mybb->settings['hurl_plugin_enable'] == "1" && $mybb->settings['hurl_select_posts'] == "1" && (in_array($mybb->user['usergroup'], explode(',',$mybb->settings['hurl_groups_ids'])) || $mybb->settings['hurl_groups_ids'] == "-1") ) {

      hide_now($post['message']);

    } else if ($mybb->settings['hurl_plugin_enable'] == "1" && $mybb->settings['hurl_select_posts'] == "2" && (in_array($mybb->user['usergroup'], explode(',',$mybb->settings['hurl_groups_ids'])) || $mybb->settings['hurl_groups_ids'] == "-1") ) {

      hide_now($post['signature']);

    } else if ($mybb->settings['hurl_plugin_enable'] == "1" && $mybb->settings['hurl_select_posts'] == "3" && (in_array($mybb->user['usergroup'], explode(',',$mybb->settings['hurl_groups_ids'])) || $mybb->settings['hurl_groups_ids'] == "-1") ) {

      hide_now($post['message']);
      hide_now($post['signature']);

    }
}
?>
