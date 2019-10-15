<?php
defined('IN_MYBB') or die('Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.');

defined('PLUGINLIBRARY') or define('PLUGINLIBRARY', MYBB_ROOT . "inc/plugins/pluginlibrary.php");

$plugins->add_hook('warnings_do_warn_end', 'repwarning_do_warn_end');
$plugins->add_hook('warnings_warn_end', 'repwarning_warn_end');

function repwarning_info()
{
   return [
      'name'         => 'Reputation for warning',
      'description'  => 'Reputation for warning',
      'author'       => 'Adrian \'Qwizi\' Ciołek',
      'authorsite'   => 'https://github.com/Qwizi/',
      'version'      => '1.2',
      'compatibility'   => '18*',
      'codename'     => 'repwarning',
   ];
}

function repwarning_install() {
   global $PL, $lang;

   if (!file_exists(PLUGINLIBRARY)) {
      flash_message("PluginLibrary is missing.", "error");
      admin_redirect("index.php?module=config-plugins");
   }

   $PL or require_once PLUGINLIBRARY;

   $PL->settings(
      'repwarning',
      'Reputation for warning',
      'Settings of reputation of warning',
      [
         'bot_id' => [
            'title' => 'Bot\'s ID',
            'description' => 'Id of a bot',
            'optionscode' => 'numeric',
            'value' => 1
         ],
         'comment' => [
            'title' => 'Comment',
            'description' => 'Reputation comment',
            'optionscode' => 'textarea',
            'value' => 'Penalty points for a warning' 
         ]
      ]
   );

   $PL->templates(
      'repwarning',
      'Reputation for warning',
      [
         '' => '
<tr>
	<td class="trow1" style="width: 20%; vertical-align: top;"><strong>Value of reputation points</strong></td>
	<td class="trow1"><input name="rep_points" class="textbox" type="text" size="2" value="-10" /></td>
</tr>
'
      ]
   );

   require_once MYBB_ROOT.'inc/adminfunctions_templates.php';
   find_replace_templatesets('warnings_warn', '#'.preg_quote('{$pm_notify}').'#', '{$pm_notify}'."\n".'{$repwarning}');
}

function repwarning_uninstall()
{
   global $PL;

   $PL or require_once PLUGINLIBRARY;

   $PL->settings_delete('repwarning', true);
   $PL->templates_delete('repwarning');

   require_once MYBB_ROOT.'inc/adminfunctions_templates.php';
   find_replace_templatesets('warnings_warn', '#' . preg_quote("\n".'{$repwarning}') . '#', '', 0);
}

function repwarning_is_installed()
{
   global $db;
   $query = $db->simple_select('settinggroups', 'gid', "name='repwarning'");
   return (bool) $db->num_rows($query);
}

function repwarning_warn_end() {
   global $templates, $repwarning;
   eval("\$repwarning = \"" . $templates->get("repwarning") . "\";");
}

function repwarning_do_warn_end() {
   global $db, $mybb, $warning;


   if (!empty($mybb->input['rep_points'])) {
      $reputation = [
         'uid' => $warning['uid'],
         'adduid' => $mybb->settings['repwarning_bot_id'],
         'reputation' => $mybb->get_input('rep_points', MyBB::INPUT_INT),
         'dateline' => TIME_NOW,
         'comments' => $db->escape_string($mybb->settings['repwarning_comment'])
      ];

      $db->insert_query('reputation', $reputation);

      $query = $db->simple_select("reputation", "SUM(reputation) AS reputation_count", "uid='{$warning['uid']}'");
		$reputation_value = $db->fetch_field($query, "reputation_count");
		$db->update_query("users", array('reputation' => (int)$reputation_value), "uid='{$warning['uid']}'");
   }
}
