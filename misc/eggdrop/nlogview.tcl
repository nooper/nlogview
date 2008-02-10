package require mysqltcl


bind pub - "connect" doconnect
proc doconnect { nick uh handle chan thetext } {
	mysql_connect "HOSTNAME" "USERNAME" "PASSWORD" "DATABASE";
}

proc mysql_connect { host user pass db } {
	global db_handle;
	set db_handle [mysqlconnect -host $host -user $user -password $pass -db $db];
	putmsg "#sdf" $db_handle;
}

bind pubm - * event_pub
proc event_pub { nick uh handle chan thetext } {
	insert_event $nick $uh $chan 1;
}

bind join - * event_join
proc event_join { nick uh handle chan } {
	insert_event $nick $uh $chan 2;
}

bind part - * event_part
proc event_part { nick uh handle chan msg } {
	insert_event $nick $uh $chan 3;
}

bind sign - * event_quit
proc event_quit { nick uh handle chan reason } {
	insert_event $nick $uh $chan 4;
}

bind nick - * event_nickchange
proc event_nickchange { nick uh handle chan newnick } {
	insert_event $nick $uh $chan 5;
	insert_event $newnick $uh $chan 6;
}

bind kick - * event_kick
proc event_kick { nick uh handle chan target reason } {
	insert_event $nick $uh $chan 7;
}

proc insert_event { nick uh chan activitytype } {
	global db_handle server serveraddress;
	set ircuserid [getIRCUserID $nick $uh];
	set serverid [getServerID]
	set channelid [getChannelID $chan $serverid]
	set logid [getLogID]
	set sql "INSERT INTO nlogview_activity(channelid, ircuserid, logid, activitytype, activitytime) ";
	append sql "VALUES($channelid, $ircuserid, $logid, $activitytype, now())";
	mysqlexec $db_handle $sql;
}

proc getChannelID { name serverid } {
	global db_handle
	set sql "SELECT channelid FROM nlogview_channels WHERE serverid=$serverid AND name='$name'";
	set result [mysqlquery $db_handle $sql];
	set row [mysqlnext $result];
	if { $row == "" } {
		set sql "INSERT INTO nlogview_channels(serverid, name) VALUES($serverid, '$name')";
		mysqlexec $db_handle $sql;
		set retval [mysqlinsertid $db_handle];
	} else {
		set retval [lindex $row 0]
	}
	return $retval;
}

proc getServerID { } {
	global db_handle server serveraddress
	set sql "SELECT serverid FROM nlogview_servers WHERE name='$server' AND address='$serveraddress'";
	set result [mysqlquery $db_handle $sql];
	set row [mysqlnext $result];
	if { $row == "" } {
		set sql "INSERT INTO nlogview_servers(name, address) VALUES('$server', '$serveraddress')";
		mysqlexec $db_handle $sql;
		set retval [mysqlinsertid $db_handle];
	} else {
		set retval [lindex $row 0];
	}
	return $retval;
}

proc getLogID { } {
	global db_handle botname
	set sql "SELECT logid FROM nlogview_logs WHERE name='$botname' AND source='eggdrop'";
	set result [mysqlquery $db_handle $sql];
	set row [mysqlnext $result];
	if { $row == "" } {
		set sql "INSERT INTO nlogview_logs(name, source, submittime, position, logtype) VALUES('$botname', 'eggdrop', now(), 0, 2)";
		mysqlexec $db_handle $sql;
		set retval [mysqlinsertid $db_handle];
	} else {
		set retval [lindex $row 0];
	}
	return $retval;
}


proc getNUHid { lookup tablename idcolname } {
	global db_handle;
	set sql "SELECT $idcolname FROM $tablename where name = '$lookup'";
	set result [mysqlquery $db_handle $sql]
	set row [mysqlnext $result]
	if { $row == "" } {
		set sql "INSERT INTO $tablename (name) VALUES('$lookup')";
		mysqlexec $db_handle $sql;
		set retval [mysqlinsertid $db_handle];
	} else {
		set retval [lindex $row 0];
	}
	return $retval;
}

proc getIRCUserID { nick uh } {
	global db_handle;
	set uhsplit [split $uh @]
	set user [lindex $uhsplit 0]
	set host [lindex $uhsplit 1]
	set nickid [getNUHid $nick "nlogview_nicks" "nickid"];
	set userid [getNUHid $user "nlogview_users" "userid"];
	set hostid [getNUHid $host "nlogview_hosts" "hostid"];
	set sql "SELECT ircuserid FROM nlogview_ircusers WHERE nickid = $nickid AND userid = $userid AND hostid = $hostid";
	set result [mysqlquery $db_handle $sql]
	set row [mysqlnext $result]
	if { $row == "" } {
		set sql "INSERT INTO nlogview_ircusers(nickid, userid, hostid) VALUES( $nickid, $userid, $hostid )";
		mysqlexec $db_handle $sql;
		set retval [mysqlinsertid $db_handle];
	} else {
		set retval [lindex $row 0];
	}
	return $retval;
}
