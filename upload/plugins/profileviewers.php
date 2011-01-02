<?php
 /**
 * This file is part of Profile Viewers for MyBB.
 * Copyright (C) 2006-2011 StefanT (http://www.mybbcoder.info)
 * https://github.com/Stefan-ST/MyBB-Profile-Viewers
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

if(!defined("IN_MYBB")) {
    die("This file cannot be accessed directly.");
}

$plugins->add_hook("member_profile_end", "profileviewers_do");

function profileviewers_info()
{
	return array(
		"name"				=> "Profile Viewers",
		"description"		=> "",
		"website"			=> "http://www.mybbcoder.info",
		"author"			=> "StefanT",
		"authorsite"		=> "http://www.mybbcoder.info",
		"version"			=> "1.01"
	);
}

function profileviewers_activate()
{
	global $db;

	$db->query("CREATE TABLE IF NOT EXISTS ".TABLE_PREFIX."profileviewers (
pid INT NOT NULL,
uid INT NOT NULL);");

	$db->query("CREATE TABLE IF NOT EXISTS mybb_profileviewers_views (
uid INT NOT NULL,
views INT NOT NULL);");
	require MYBB_ROOT."/inc/adminfunctions_templates.php";
	find_replace_templatesets("member_profile", '#{\$signature}#', "{\$signature}\n{\$profileviewers}");

	$template = array(
		"title"		=> "member_profileviewers",
		"template"	=> "<br />
<table border=\"0\" cellspacing=\"{\$theme[\'borderwidth\']}\" cellpadding=\"{\$theme[\'tablespace\']}\" class=\"tborder\" width=\"100%\">
	<tr>
		<td class=\"thead\"><strong>Ansichten</strong></td>
	</tr>
	<tr>
		<td class=\"trow1\">{\$count}</td>
	</tr>
	<tr>
		<td class=\"trow2\">{\$visitors}</td>
	</tr>
</table>",
		"sid"		=> -1
	);
	$db->insert_query(TABLE_PREFIX."templates", $template);
}

function profileviewers_deactivate()
{
	global $db;
	$db->query("DELETE FROM ".TABLE_PREFIX."templates WHERE title='member_profileviewers'");
	$db->query("DROP TABLE ".TABLE_PREFIX."profileviewers");
	$db->query("DROP TABLE ".TABLE_PREFIX."profileviewers_views");
	require MYBB_ROOT."/inc/adminfunctions_templates.php";
	find_replace_templatesets("member_profile", '#{\$profileviewers}#', '', 0);
}

function profileviewers_do()
{
	global $memprofile, $mybb, $theme, $db, $templates, $profileviewers;
	$uid =  $memprofile['uid'];
	$query = $db->query("SELECT * FROM ".TABLE_PREFIX."profileviewers_views WHERE uid='$uid'");
	$result = $db->fetch_array($query);
	if(!$result['views'])
	{
		$db->query("INSERT INTO ".TABLE_PREFIX."profileviewers_views SET views='1', uid='$uid'");
	}
	else
	{
		$db->query("UPDATE ".TABLE_PREFIX."profileviewers_views SET views=views+1 WHERE uid='$uid'");
	}
	if($mybb->user['uid'] != 0 AND $uid != $mybb->user['uid'])
	{
		$query = $db->query("SELECT * FROM ".TABLE_PREFIX."profileviewers WHERE pid='$uid' AND uid='".$mybb->user['uid']."'");
		$user = $db->fetch_array($query);
		if(!$user)
		{
			$db->query("INSERT INTO ".TABLE_PREFIX."profileviewers SET uid='".$mybb->user['uid']."', pid='$uid'");
		}
	}

	$query = $db->query("SELECT u.username, u.usergroup, u.displaygroup, u.uid FROM ".TABLE_PREFIX."profileviewers v LEFT JOIN ".TABLE_PREFIX."users u ON (v.uid=u.uid) WHERE v.pid='$uid' ORDER BY u.username ASC");
	while($users = $db->fetch_array($query))
	{
		$array[] = build_profile_link(format_name($users['username'], $users['usergroup'], $users['displaygroup']), $users['uid']);
	}
	if(is_array($array))
	{
		$visitors = implode($array, ", ");
	}
	$count = $result['views']+1;
	eval("\$profileviewers = \"".$templates->get("member_profileviewers")."\";");
}
?>