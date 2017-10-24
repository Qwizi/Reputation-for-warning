<?php
if(!defined("IN_MYBB"))
{
   die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

$plugins->add_hook("warnings_do_warn_end", "rep_warning_action");

function rep_warning_info()
{
   return array(
      "name"         => "Reputacja za ostrzeżenie.",
      "description"  => "Reputacja za ostrzeżenie.",
      "website"      => "http://sharkservers.eu",
      "author"       => "Qwizi",
      "authorsite"   => "http://sharkservers.eu",
      "version"      => "1.0",
      "compatibility"   => "18*",
      "codename"     => "",
   );
}

function rep_warning_is_installed()
{
   global $mybb;
   return $mybb->settings['rep_warning_on'] !== null;
}

function rep_warning_install()
{
   global $db;
   $group = array(
      "gid"             => "NULL",
      "title"            => "Reputacja za ostrzeżenie",
      "name"         => "rep_warning",
      "description"  => "Ustawienia pluginu Reputacja za ostrzeżenie",
      "disporder"     => "1",
      "isdefault"       => "0",
   );
   $db->insert_query("settinggroups", $group);
   $gid = $db->insert_id();

   $setting  = array(
      "sid"             => "NULL",
      "name"         => "rep_warning_on",
      "title"             => "Plugin włączony/wyłączony",
      "description"  => "Plugin ma być włączony/wyłączony?",
      "optionscode"   => "yesno",
      "value"           => "yes",
      "disporder"     => "2",
      "gid"              => intval($gid),
   );
   $db->insert_query("settings", $setting);

   $setting  = array(
      "sid"             => "NULL",
      "name"         => "rep_warning_id",
      "title"             => "ID użytkownika",
      "description"  => "Podaj id użytkownika, który będzie przyznawał reputację",
      "optionscode"   => "numeric",
      "value"           => "1",
      "disporder"     => "3",
      "gid"              => intval($gid),
   );
   $db->insert_query("settings", $setting);

   $setting  = array(
      "sid"             => "NULL",
      "name"         => "rep_warning_pkt",
      "title"             => "Ilość punktów przyznawanych za ostrzeżenie",
      "description"  => "Podaj jaka ma być ilość punktów przyznawanych za ostrzeżenie",
      "optionscode"   => "numeric",
      "value"           => "-10",
      "disporder"     => "4",
      "gid"              => intval($gid),
   );
   $db->insert_query("settings", $setting);

   $setting  = array(
      "sid"             => "NULL",
      "name"         => "rep_warning_comments",
      "title"             => "Komentarz do reputacji",
      "description"  => "Podaj komentarz do reputacji",
      "optionscode"   => "text",
      "value"           => "Karne punkty za ostrzeżenie",
      "disporder"     => "5",
      "gid"              => intval($gid),
   );
   $db->insert_query("settings", $setting);
   rebuild_settings();
}

function rep_warning_uninstall()
{
   global $db;
   $db->delete_query("settinggroups", "name=\"rep_warning\"");
   $db->delete_query("settings", "name LIKE \"rep_warning%\"");
   rebuild_settings();
}

function rep_warning_action()
{
   global $db, $mybb;
   if ($mybb->settings['rep_warning_on'])
   {
      $sql = "SELECT uid, dateline FROM ".TABLE_PREFIX."warnings ORDER BY dateline DESC LIMIT 1";
      $result = $db->query($sql);
      $row = $db->fetch_array($result);
      $userid = $row['uid'];

      $query = array(
         "rid"          => "",
         "uid"          => $userid,
         "adduid"    => $mybb->settings['rep_warning_id'],
         "pid"          => "",
         "reputation"   => $mybb->settings['rep_warning_pkt'],
         "dateline"       => TIME_NOW,
         "comments"   => $mybb->settings['rep_warning_comments'],
      );
      $db->insert_query("reputation", $query);
   }
}

?>
