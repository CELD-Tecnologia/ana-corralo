<?php
/*
 * This file is part of Mibew Messenger project.
 * 
 * Copyright (c) 2005-2011 Mibew Messenger Community
 * All rights reserved. The contents of this file are subject to the terms of
 * the Eclipse Public License v1.0 which accompanies this distribution, and
 * is available at http://www.eclipse.org/legal/epl-v10.html
 * 
 * Alternatively, the contents of this file may be used under the terms of
 * the GNU General Public License Version 2 or later (the "GPL"), in which case
 * the provisions of the GPL are applicable instead of those above. If you wish
 * to allow use of your version of this file only under the terms of the GPL, and
 * not to allow others to use your version of this file under the terms of the
 * EPL, indicate your decision by deleting the provisions above and replace them
 * with the notice and other provisions required by the GPL.
 * 
 * Contributors:
 *    Evgeny Gryaznov - initial API and implementation
 */

$dbtables = array(
	"${mysqlprefix}chatgroup" => array(
		"groupid" => "int NOT NULL auto_increment PRIMARY KEY",
		"vcemail" => "varchar(64)",
		"vclocalname" => "varchar(64) NOT NULL",
		"vccommonname" => "varchar(64) NOT NULL",
		"vclocaldescription" => "varchar(1024) NOT NULL",
		"vccommondescription" => "varchar(1024) NOT NULL",
	),

	"${mysqlprefix}chatthread" => array(
		"threadid" => "int NOT NULL auto_increment PRIMARY KEY",
		"userName" => "varchar(64) NOT NULL",
		"userid" => "varchar(255)",
		"agentName" => "varchar(64)",
		"agentId" => "int NOT NULL DEFAULT 0",
		"dtmcreated" => "datetime DEFAULT 0",
		"dtmmodified" => "datetime DEFAULT 0",
		"lrevision" => "int NOT NULL DEFAULT 0",
		"istate" => "int NOT NULL DEFAULT 0",
		"ltoken" => "int NOT NULL",
		"remote" => "varchar(255)",
		"referer" => "text",
		"nextagent" => "int NOT NULL DEFAULT 0",
		"locale" => "varchar(8)",
		"lastpinguser" => "datetime DEFAULT 0",
		"lastpingagent" => "datetime DEFAULT 0",
		"userTyping" => "int DEFAULT 0",
		"agentTyping" => "int DEFAULT 0",
		"shownmessageid" => "int NOT NULL DEFAULT 0",
		"userAgent" => "varchar(255)",
		"messageCount" => "varchar(16)",
		"groupid" => "int references ${mysqlprefix}chatgroup(groupid)",
	),

	"${mysqlprefix}chatmessage" => array(
		"messageid" => "int NOT NULL auto_increment PRIMARY KEY",
		"threadid" => "int NOT NULL references ${mysqlprefix}chatthread(threadid)",
		"ikind" => "int NOT NULL",
		"agentId" => "int NOT NULL DEFAULT 0",
		"tmessage" => "text NOT NULL",
		"dtmcreated" => "datetime DEFAULT 0",
		"tname" => "varchar(64)"
	),

	"${mysqlprefix}chatoperator" => array(
		"operatorid" => "int NOT NULL auto_increment PRIMARY KEY",
		"vclogin" => "varchar(64) NOT NULL",
		"vcpassword" => "varchar(64) NOT NULL",
		"vclocalename" => "varchar(64) NOT NULL",
		"vccommonname" => "varchar(64) NOT NULL",
		"vcemail" => "varchar(64)",
		"dtmlastvisited" => "datetime DEFAULT 0",
		"istatus" => "int DEFAULT 0", /* 0 - online, 1 - away */
		"vcavatar" => "varchar(255)",
		"vcjabbername" => "varchar(255)",
		"iperm" => "int DEFAULT 65535",
		"dtmrestore" => "datetime DEFAULT 0",
		"vcrestoretoken" => "varchar(64)",
	),

	"${mysqlprefix}chatrevision" => array(
		"id" => "INT NOT NULL"
	),

	"${mysqlprefix}chatgroupoperator" => array(
		"groupid" => "int NOT NULL references ${mysqlprefix}chatgroup(groupid)",
		"operatorid" => "int NOT NULL references ${mysqlprefix}chatoperator(operatorid)",
	),

	"${mysqlprefix}chatban" => array(
		"banid" => "INT NOT NULL auto_increment PRIMARY KEY",
		"dtmcreated" => "datetime DEFAULT 0",
		"dtmtill" => "datetime DEFAULT 0",
		"address" => "varchar(255)",
		"comment" => "varchar(255)",
		"blockedCount" => "int DEFAULT 0"
	),

	"${mysqlprefix}chatconfig" => array(
		"id" => "INT NOT NULL auto_increment PRIMARY KEY",
		"vckey" => "varchar(255)",
		"vcvalue" => "varchar(255)",
	),

	"${mysqlprefix}chatresponses" => array(
		"id" => "INT NOT NULL auto_increment PRIMARY KEY",
		"locale" => "varchar(8)",
		"groupid" => "int references ${mysqlprefix}chatgroup(groupid)",
		"vcvalue" => "varchar(1024) NOT NULL",
	)
);

$memtables = array();

$dbtables_can_update = array(
	"${mysqlprefix}chatthread" => array("agentId", "userTyping", "agentTyping", "messageCount", "nextagent", "shownmessageid", "userid", "userAgent", "groupid"),
	"${mysqlprefix}chatmessage" => array("agentId"),
	"${mysqlprefix}chatoperator" => array("vcavatar", "vcjabbername", "iperm", "istatus", "vcemail", "dtmrestore", "vcrestoretoken"),
	"${mysqlprefix}chatban" => array(),
	"${mysqlprefix}chatgroup" => array("vcemail"),
	"${mysqlprefix}chatgroupoperator" => array(),
	"${mysqlprefix}chatresponses" => array(),
);

function show_install_err($text)
{
	global $page, $version, $errors, $webimroot;
	$page = array(
		'version' => $version,
		'localeLinks' => get_locale_links("$webimroot/install/index.php")
	);
	$errors = array($text);
	start_html_output();
	require('../view/install_err.php');
	exit;
}

function create_table($id, $link)
{
	global $dbtables, $memtables, $dbencoding, $mysqlprefix;

	if (!isset($dbtables[$id])) {
		show_install_err("Unknown table: $id, " . mysqli_error($link));
	}

	$query =
			"CREATE TABLE $id\n" .
			"(\n";
	foreach ($dbtables[$id] as $k => $v) {
		$query .= "	$k $v,\n";
	}

	$query = preg_replace("/,\n$/", "", $query);
	$query .= ") charset $dbencoding";
	if (in_array($id, $memtables)) {
		$query .= " ENGINE=MEMORY";
	} else {
		$query .= " ENGINE=InnoDb";
	}

	mysqli_query($query, $link) or show_install_err(' Query failed: ' . mysqli_error($link));

	if ($id == "${mysqlprefix}chatoperator") {
		create_operator_("admin", "", "", "Administrator", "Administrator", "", $link);
	} else if ($id == "${mysqlprefix}chatrevision") {
		perform_query("INSERT INTO ${mysqlprefix}chatrevision VALUES (1)", $link);
	}
}

function get_tables($link)
{
	global $mysqldb, $errors;
	$result = mysqli_query("SHOW TABLES FROM `$mysqldb`", $link);
	if ($result) {
		$arr = array();
		while ($row = mysqli_fetch_array($result, mysqli_NUM)) {
			$arr[] = $row[0];
		}
		mysqli_free_result($result);
		return $arr;

	} else {
		$errors[] = "Cannot get tables from database. Error: " . mysqli_error($link);
		return false;
	}
}

function get_columns($tablename, $link)
{
	global $errors;
	$result = mysqli_query("SHOW COLUMNS FROM $tablename", $link);
	if ($result) {
		$arr = array();
		while ($row = mysqli_fetch_array($result, mysqli_NUM)) {
			$arr[] = $row[0];
		}
		mysqli_free_result($result);
		return $arr;

	} else {
		$errors[] = "Cannot get columns from table \"$tablename\". Error: " . mysqli_error($link);
		return false;
	}
}

?>