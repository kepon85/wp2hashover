# wp2hashover-next

wp2hashover is a small script that allows you to migrate comments from a wordpress site to hashover-next

* https://github.com/jacobwb/hashover-next
* http://wordpress.org/
* https://github.com/jacobwb/hashover-wp-plugin (wordpress plugin to display hashover on your wordpress)

## Required 

- hashover-next (2.0) 
- hashover config in mysql mod
  - Add one comment in hashover for create table
- Identique mysql credential for hashover db and wordpress db
- wordpress permalink is /%postname%/ in wp-admin/options-permalink.php  (dev other for the future...)

## Depend 

- PHP PDO
- PHP DateTime

## Install

* Your wordpress is in /var/www/wordpress
* Your hashover-next is in /var/www/wordpress/hashover
* Copy wp2hashover file to /var/www/wordpress/wp2hashover

Copy config.php.example to config.php

Edit your config.php

Lancch wp2hashover in cli or in your browser. In cli style : 

```
root@srvweb:/var/www/david-dev.mercereau.info/web/wp2hashover# php wp2hashover.php 

## wp2hashover ##

In emailpoubelle-php-script-libre-demail-jetable, by tranxene50 at 2012-08-08T01:04:27+0200 with id 1 : Ok
In emailpoubelle-php-script-libre-demail-jetable, by David at 2012-08-08T07:55:11+0200 with id 1-1 : Ok
In emailpoubelle-php-script-libre-demail-jetable, by one piece episode at 2012-08-27T19:35:02+0200 with id 2 : Ok
In emailpoubelle-php-script-libre-demail-jetable, by David at 2012-08-28T06:53:05+0200 with id 2-1 : Ok
In xsshfs-v0-5-interface-graphique-pour-xsshfs-perlglade, by lebipbip at 2012-11-14T18:40:27+0100 with id 1 : Ok
In xsshfs-v0-5-interface-graphique-pour-xsshfs-perlglade, by David at 2012-11-15T07:22:10+0100 with id 1-1 : Ok
In bloquer-le-tracking-des-reseaux-sociaux, by LordPhoenix at 2012-11-15T15:27:14+0100 with id 1 : Ok
In bloquer-le-tracking-des-reseaux-sociaux, by Jyve at 2012-11-15T16:00:12+0100 with id 2 : Ok
In bloquer-le-tracking-des-reseaux-sociaux, by totopouet at 2012-11-15T16:31:33+0100 with id 3 : Ok
In bloquer-le-tracking-des-reseaux-sociaux, by Ricard at 2012-11-15T16:34:30+0100 with id 4 : Ok
In bloquer-le-tracking-des-reseaux-sociaux, by antistress at 2012-11-15T18:08:11+0100 with id 5 : Ok
In bloquer-le-tracking-des-reseaux-sociaux, by John77800 at 2012-11-15T19:01:02+0100 with id 6 : Ok
In bloquer-le-tracking-des-reseaux-sociaux, by PostBlue at 2012-11-15T19:37:58+0100 with id 7 : Ok
In bloquer-le-tracking-des-reseaux-sociaux, by Chimrod at 2012-11-16T08:56:57+0100 with id 8 : Ok
In bloquer-le-tracking-des-reseaux-sociaux, by Ricard at 2012-11-16T09:20:28+0100 with id 9 : Ok
In bloquer-le-tracking-des-reseaux-sociaux, by David at 2012-11-16T10:13:08+0100 with id 9-1 
```

## Note

* login_id no supported