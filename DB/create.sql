drop table if exists irc_activity;
drop table if exists irc_logs;
drop table if exists irc_ircusers;
drop table if exists irc_channels;
drop table if exists irc_servers;
drop table if exists irc_hosts;
drop table if exists irc_idents;
drop table if exists irc_nicks;
drop table if exists nlogview_static;
drop table if exists irc_ircuser_relation;


create table irc_servers
(
serverid int unsigned auto_increment primary key,
name varchar(255) not null,
address varchar(255)
) engine=innodb;

create table irc_channels
(
channelid int unsigned auto_increment primary key,
serverid int unsigned,
name varchar(255) not null,
index (serverid),
foreign key (serverid) references irc_servers(serverid)
) engine=innodb;

create table irc_nicks
(
nickid int unsigned auto_increment primary key,
name varchar(255) not null,
index (name)
) engine=innodb;

create table irc_idents
(
userid int unsigned auto_increment primary key,
name varchar(255) not null,
index (name)
) engine=innodb;

create table irc_hosts
(
hostid int unsigned auto_increment primary key,
name varchar(255) not null,
index (name)
) engine=innodb;

create table irc_ircusers
(
ircuserid int unsigned auto_increment primary key,
nickid int unsigned,
userid int unsigned,
hostid int unsigned,
unique (nickid, userid, hostid),
index (nickid),
index (userid),
index (hostid),
foreign key (userid) references irc_idents(userid),
foreign key (nickid) references irc_nicks(nickid),
foreign key (hostid) references irc_hosts(hostid)
) engine=innodb;

create table irc_logs
(
logid int unsigned auto_increment primary key,
name varchar(255) not null,
source varchar(255) not null,
submittime timestamp default current_timestamp
) engine=innodb;

create table irc_activity
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
foreign key (channelid) references irc_channels(channelid),
foreign key (ircuserid) references irc_ircusers(ircuserid),
foreign key (logid) references irc_logs(logid)
) engine=innodb;

create table nlogview_static
(
keyname varchar(255) primary key,
value text
) engine=myisam;

create table irc_ircuser_relation(
relationid int(10) unsigned not null auto_increment primary key,
fromircuser int(10) unsigned not null,
toircuser int(10) unsigned not null,
channelid int(10) unsigned not null,
relation int(10) unsigned not null,
index(fromircuser, toircuser, relation)
);





delimiter //
create procedure explore(IN tmptable varchar(255))
begin
declare bitmask int default 7;
declare nickbit int default 1;
declare identbit int default 1;
declare hostbit int default 1;
set @nicksql = concat('insert into ', tmptable, ' select distinct i.* from irc_ircusers i inner join ' , tmptable, ' e on i.userid = e.userid and 
i.hostid = e.hostid where i.ircuserid not in (select ircuserid from ', tmptable, ')');
prepare nickquery from @nicksql;
set @identsql = concat('insert into ', tmptable, ' select distinct i.* from irc_ircusers i inner join ' , tmptable, ' e on i.nickid = e.nickid and 
i.hostid = e.hostid where i.ircuserid not in (select ircuserid from ', tmptable, ')');
prepare identquery from @identsql;
set @hostsql = concat('insert into ', tmptable, ' select distinct i.* from irc_ircusers i inner join ' , tmptable, ' e on i.userid = e.userid and 
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

