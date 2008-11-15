drop table if exists nlogview_activity;
drop table if exists nlogview_logs;
drop table if exists nlogview_ircusers;
drop table if exists nlogview_channels;
drop table if exists nlogview_servers;
drop table if exists nlogview_hosts;
drop table if exists nlogview_idents;
drop table if exists nlogview_nicks;
drop table if exists nlogview_static;


create table nlogview_servers
(
serverid int unsigned auto_increment primary key,
name varchar(255) not null,
address varchar(255)
) engine=innodb;

create table nlogview_channels
(
channelid int unsigned auto_increment primary key,
serverid int unsigned,
name varchar(255) not null,
index (serverid),
foreign key (serverid) references nlogview_servers(serverid)
) engine=innodb;

create table nlogview_nicks
(
nickid int unsigned auto_increment primary key,
name varchar(255) not null,
index (name)
) engine=innodb;

create table nlogview_idents
(
userid int unsigned auto_increment primary key,
name varchar(255) not null,
index (name)
) engine=innodb;

create table nlogview_hosts
(
hostid int unsigned auto_increment primary key,
name varchar(255) not null,
index (name)
) engine=innodb;

create table nlogview_ircusers
(
ircuserid int unsigned auto_increment primary key,
nickid int unsigned,
userid int unsigned,
hostid int unsigned,
unique (nickid, userid, hostid),
index (nickid),
index (userid),
index (hostid),
foreign key (userid) references nlogview_idents(userid),
foreign key (nickid) references nlogview_nicks(nickid),
foreign key (hostid) references nlogview_hosts(hostid)
) engine=innodb;

create table nlogview_logs
(
logid int unsigned auto_increment primary key,
name varchar(255) not null,
source varchar(255) not null,
submittime timestamp default current_timestamp
) engine=innodb;

create table nlogview_activity
(
activityid int unsigned auto_increment primary key,
channelid int unsigned not null,
ircuserid int unsigned not null,
logid int unsigned not null,
activitytype tinyint unsigned not null,
activitytime datetime not null,
index (channelid),
index (ircuserid),
index (logid),
foreign key (channelid) references nlogview_channels(channelid),
foreign key (ircuserid) references nlogview_ircusers(ircuserid),
foreign key (logid) references nlogview_logs(logid)
) engine=innodb;

create table nlogview_static
(
keyname varchar(255) primary key,
value text
) engine=myisam;

delimiter //
create procedure explore(IN tmptable varchar(255))
begin
declare bitmask int default 7;
declare nickbit int default 1;
declare identbit int default 1;
declare hostbit int default 1;
set @nicksql = concat('insert into ', tmptable, ' select distinct i.* from nlogview_ircusers i inner join ' , tmptable, ' e on i.userid = e.userid and 
i.hostid = e.hostid where i.ircuserid not in (select ircuserid from ', tmptable, ')');
prepare nickquery from @nicksql;
set @identsql = concat('insert into ', tmptable, ' select distinct i.* from nlogview_ircusers i inner join ' , tmptable, ' e on i.nickid = e.nickid and 
i.hostid = e.hostid where i.ircuserid not in (select ircuserid from ', tmptable, ')');
prepare identquery from @identsql;
set @hostsql = concat('insert into ', tmptable, ' select distinct i.* from nlogview_ircusers i inner join ' , tmptable, ' e on i.userid = e.userid and 
i.nickid = e.nickid where i.ircuserid not in (select ircuserid from ', tmptable, ')');
prepare hostquery from @hostsql;
repeat
if nickbit = 1 then
set nickbit = 0;
execute nickquery;
if row_count() > 0 then
set identbit = 1;
set hostbit = 1;
end if;
end if;
if identbit = 1 then
set identbit = 0;
execute identquery;
if row_count() > 0 then
set nickbit = 1;
set hostbit = 1;
end if;
end if;
if hostbit = 1 then
set hostbit = 0;
execute hostquery;
if row_count() > 0 then
set identbit = 1;
set nickbit = 1;
end if;
end if;
set bitmask = nickbit + identbit + hostbit;
until bitmask = 0
end repeat;
end//
delimiter ;

