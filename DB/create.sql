drop table nlogview_activity;
drop table nlogview_logs;
drop table nlogview_ircusers;
drop table nlogview_channels;
drop table nlogview_servers;
drop table nlogview_hosts;
drop table nlogview_users;
drop table nlogview_nicks;


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

create table nlogview_users
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
foreign key (userid) references nlogview_users(userid),
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
value int
) engine=innodb;
