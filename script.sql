DROP DATABASE IF EXISTS social_network;
CREATE DATABASE polaznik CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
use social_network;

create table post(
id int not null primary key auto_increment,
dateCreated timestamp not null default current_timestamp,
content text,
image text
)engine=InnoDB;


create table comment(
id int not null primary key auto_increment,
postId int not null,
dateCreated timestamp not null default current_timestamp,
content text,
foreign key (postId) references post(id)
)engine=InnoDB;

insert into post (content, image) values ('Evo danas pada kiša opet :(', 'Rain.jpg'), ('Jedem jagode.', 'Strawberry.jpg');