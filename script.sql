DROP DATABASE IF EXISTS social_network;
CREATE DATABASE social_network CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
use social_network;

create table post(
id int not null primary key auto_increment,
content text,
image text
)engine=InnoDB;

insert into post (content) values ('Evo danas pada kiša opet :('), ('Jedem jagode.');