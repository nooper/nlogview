package require mysqltcl

set dbhost "HOSTNAME"
set dbuser "USERNAME"
set dbpass "PASSWORD"
set dbname "DATABASE NAME"

global statics;

proc dbconnect { } {
	global db_handle dbhost dbuser dbpass dbname statics
	if { ![info exists statics(dbconnection)] } {
		set db_handle [mysqlconnect -host $dbhost -user $dbuser -password $dbpass -db $dbname];
		set statics(dbconnection) 1
	}
}
dbconnect

bind pubm - * event_pub
proc event_pub { nick uh handle chan thetext } {
	insert_event $nick $uh $chan 1;
}

bind ctcp - ACTION event_act
proc event_act { nick uh handle dest keyword thetext } {
	insert_event $nick $uh $dest 1;
}

bind join - * event_join
proc event_join { nick uh handle chan } {
	insert_event $nick $uh $chan 2;
}

bind part - * event_part
proc event_part { nick uh handle chan msg } {
	global statics
	insert_event $nick $uh $chan 3;
	unset statics($nick$uh);
}

bind sign - * event_quit
proc event_quit { nick uh handle chan reason } {
	global statics
	insert_event $nick $uh $chan 4;
	unset statics($nick$uh);
}

bind nick - * event_nickchange
proc event_nickchange { nick uh handle chan newnick } {
	global statics
	insert_event $nick $uh $chan 5;
	insert_event $newnick $uh $chan 6;
	unset statics($nick$uh);
}

bind kick - * event_kick
proc event_kick { nick uh handle chan target reason } {
	global statics
	insert_event $nick $uh $chan 7;
	unset statics($nick$uh);
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
	global db_handle statics
	if { [info exists statics($name)] } {
		set retval $statics($name);
	} else {
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
		set statics($name) $retval;
	}
	return $retval;
}

proc getServerID { } {
	global db_handle server serveraddress statics;
	if { [info exists statics(serverid)] } {
		set retval $statics(serverid)
	} else {
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
		set statics(serverid) $retval;
	}
	return $retval;
}

proc getLogID { } {
	global db_handle botname statics
	if { [info exists statics(logid)] } {
		set retval $statics(logid)
	} else {
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
		set statics(logid) $retval;
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
	global db_handle statics;
	if { [info exists statics($nick$uh)] } {
		set retval $statics($nick$uh);
	} else {
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
		set statics($nick$uh) $retval;
	}
	return $retval;
}
