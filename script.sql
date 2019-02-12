DROP DATABASE IF EXISTS polaznik23_mvc;
CREATE DATABASE polaznik23_mvc CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
use polaznik23_mvc;

create table user(
id int not null primary key auto_increment,
firstname varchar(50) not null,
lastname varchar(50) not null,
email varchar(100) not null,
pass char(60) not null,
image varchar (100) not null
)engine=InnoDB;

create unique index ix1 on user(email);

create table post(
id int not null primary key auto_increment,
content text,
user int not null,
date datetime not null default now()
)engine=InnoDB;

create table comment(
id int not null primary key auto_increment,
user int not null,
postId int,
commentId int,
content text not null,
date datetime not null default now()
)engine=InnoDB;

create table likes(
id int not null primary key auto_increment,
user int not null,
post int not null
)engine=InnoDB;


alter table post add FOREIGN KEY (user) REFERENCES user(id);

alter table comment add FOREIGN KEY (user) REFERENCES user(id);
alter table comment add FOREIGN KEY (postId) REFERENCES post(id);
alter table comment add FOREIGN KEY (commentId) REFERENCES comment(id);

alter table likes add FOREIGN KEY (user) REFERENCES user(id);
alter table likes add FOREIGN KEY (post) REFERENCES post(id);


insert into user (id,firstname,lastname,email,pass,image) values
(null,'Tomislav','Jakopec','tjakopec@gmail.com','$2y$10$LFXuW6y.P0Zd81fwd..CK.pCd6ZcoT5DsY7rqet9jwzReaoRi7yua', 'avatar.jpg');

insert into user (firstname,lastname,email,pass,image) values
('Mara','Jakopec','mjakopec@gmail.com','$2y$10$LFXuW6y.P0Zd81fwd..CK.pCd6ZcoT5DsY7rqet9jwzReaoRi7yua','avatar.jpg');


insert into post (content,user) values ('Evo danas pada kiša opet :(',1), ('Jedem jagode.',2);
