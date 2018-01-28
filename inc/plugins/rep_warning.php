<?php
if(!defined('IN_MYBB'))
{
   die('Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.');
}

$plugins->add_hook('warnings_do_warn_end', 'rep_warning');
$plugins->add_hook('warnings_warn_end', 'rep_warning_template');
$plugins->add_hook('warnings_do_revoke_start', 'rep_warning_revoke');

function rep_warning_info()
{
   return [
      'name'         => 'Reputation for warning',
      'description'  => 'Reputation for warning',
      'website'      => 'http://sharkservers.eu',
      'author'       => 'Qwizi',
      'authorsite'   => 'http://sharkservers.eu',
      'version'      => '1.1',
      'compatibility'   => '18*',
      'codename'     => '',
   ];
}

function rep_warning_is_installed()
{
   global $mybb;
   return $mybb->settings['rep_warning_onoff'] !== null;
}

function rep_warning_install()
{
   global $db;
   $max_disporder = $db->fetch_field($db->simple_select('settinggroups', 'MAX(disporder) AS max_disporder'), 'max_disporder');

   $group = [
      'gid'             => 'NULL',
      'title'            => 'Reputation for warning',
      'name'         => 'rep_warning',
      'description'  => 'Ustawienia pluginu Reputation for warning',
      'disporder'     => $max_disporder + 1,
      'isdefault'       => '0',
   ];
   $db->insert_query('settinggroups', $group);
   $gid = $db->insert_id();

   $settings = [
      [
         'sid'             => 'NULL',
         'name'         => 'rep_warning_onoff',
         'title'             => 'Plugin włączony/wyłączony',
         'description'  => 'Plugin ma być włączony/wyłączony?',
         'optionscode'   => 'onoff',
         'value'           => '1',
         'disporder'     => '1',
         'gid'              => intval($gid),
      ],
      [
         'sid'             => 'NULL',
         'name'         => 'rep_warning_id',
         'title'             => 'ID użytkownika',
         'description'  => 'Podaj id użytkownika, który będzie przyznawał reputację',
         'optionscode'   => 'numeric',
         'value'           => '1',
         'disporder'     => '2',
         'gid'              => intval($gid),
      ],
      [
         'sid'             => 'NULL',
         'name'         => 'rep_warning_comments',
         'title'             => 'Komentarz do reputacji',
         'description'  => 'Podaj komentarz do reputacji',
         'optionscode'   => 'text',
         'value'           => 'Karne punkty za ostrzeżenie',
         'disporder'     => '3',
         'gid'              => intval($gid),
      ],
   ];
   $db->insert_query_multiple('settings', $settings);
   rebuild_settings();

   $templates = [
      'title' => 'rep_warning',
      'template' => '
<tr>
	<td class="trow1" style="width: 20%; vertical-align: top;"><strong>Ilość punktów reputacji za ostrzeżenie</strong></td>
	<td class="trow1"><input name="rep_pkt" class="textbox" type="text" size="2" value="-10" /></td>
</tr>',
      'sid' => '-1',
      'version' => '',
      'dateline' => time()
   ];
   $db->insert_query('templates', $templates);
}

function rep_warning_uninstall()
{
   global $db;
   $db->delete_query('settinggroups', 'name=\'rep_warning\'');
   $db->delete_query('settings', 'name LIKE \'rep_warning%\'');
   $db->delete_query('templates', 'title LIKE \'rep_warning%\'');
   rebuild_settings();
}

function rep_warning_activate()
{
   require_once MYBB_ROOT.'inc/adminfunctions_templates.php';
   find_replace_templatesets('warnings_warn', '#'.preg_quote('{$pm_notify}').'#', '{$pm_notify}'."\n".'{$rep_warning}');
}

function rep_warning_deactivate()
{
   require_once MYBB_ROOT.'inc/adminfunctions_templates.php';
   find_replace_templatesets('warnings_warn', '#' . preg_quote("\n".'{$rep_warning}') . '#', '', 0);
}

function rep_warning()
{
   global $db, $mybb;
   if ($mybb->settings['rep_warning_onoff'])
   {
      $sql = 'SELECT uid, dateline FROM '.TABLE_PREFIX.'warnings ORDER BY dateline DESC LIMIT 1';
      $result = $db->query($sql);
      $row = $db->fetch_array($result);
      $userid = $row['uid'];

      if(!empty($mybb->input['rep_pkt']))
      {
         $query = [
               'uid'          => $userid,
               'adduid'    => $mybb->settings['rep_warning_id'],
               'reputation'   => $mybb->input['rep_pkt'],
               'dateline'       => TIME_NOW,
               'comments'   => $mybb->settings['rep_warning_comments'],
            ];
         $db->insert_query('reputation', $query);
      }
   }
}

function rep_warning_template()
{
   global $db, $mybb, $templates, $rep_warning;
   if ($mybb->settings['rep_warning_onoff'])
   {
      $rep_warning = '';
      eval("\$rep_warning = \"" . $templates->get("rep_warning") . "\";");
   }else{
      $rep_warning = '';
   }
}

function rep_warning_revoke()
{
   global $db, $mybb;
   if ($mybb->settings['rep_warning_onoff'])
   {
      $db->delete_query('reputation', "comments='{$mybb->settings['rep_warning_comments']}'", 1);
   }
}
?>
