<?php
namespace HashOver;

if (!is_file('config.php')) {
    exit('Copy config.php.example to config.php and edit !');
}

// Include config
include('config.php');

// Do some standard HashOver setup work
require ($hashover_folder.'backend/standard-setup.php');
// Setup class autoloader
setup_autoloader ();
$crypto = new Crypto ();

// For Cli mod jump
if(php_sapi_name() == "cli") {
    $jump="\n";
} else {
    $jump="<br />\n";
}

echo "## wp2hashover ##".$jump.$jump;

use PDO;
use DateTime;

// Generates a slug for HashOver to use based on config and post type.
function get_thread_slug($rowdata) {
	global $hashover_thread_syntax_posts, $hashover_thread_syntax_pages;
	$date = strtotime($rowdata['post_date']);
	$searches = array(
		':year',
		':month',
		':i_month',
		':day',
		':i_day',
		':hour',
		':minute',
		':title',
		':post_title',
		':id',
	);
	$replaces = array(
		date('Y', $date),
		date('m', $date),
		date('n', $date),
		date('d', $date),
		date('j', $date),
		date('H', $date),
		date('i', $date),
		$rowdata['post_name'],
		$rowdata['post_name'],
		$rowdata['ID'],
	);
	if ($rowdata['post_type'] != 'page') {
		return str_replace($searches, $replaces, $hashover_thread_syntax_posts);
	} else {
		return str_replace($searches, $replaces, $hashover_thread_syntax_pages);
	}
}

// Connect WP
$wpDbConnect = new PDO('mysql:host='.$db_host.';dbname='.$wp_db.';charset=utf8',$db_user,$db_password);
// Connect hashover
$hashoverDbConnect = new PDO('mysql:host='.$db_host.';dbname='.$hashover_db.';charset=utf8',$db_user,$db_password);

$req = $wpDbConnect->prepare('	SELECT ID,post_date,post_title,post_name,post_type,comment_author,comment_author_IP,comment_author_email,comment_author_url,comment_date,comment_content,comment_parent 
				FROM '.$wp_table_prefix.'comments, '.$wp_table_prefix.'posts
				WHERE '.$wp_table_prefix.'posts.ID = comment_post_ID
				AND post_status = "publish"
				AND comment_approved = 1
				ORDER BY `'.$wp_table_prefix.'comments`.`comment_date`  ASC');
$req->execute();
if($req->rowCount() > 0) {
   foreach($req as $row) {
	$comment_parent=null;
	//~ var_dump($row);
	// Prepare data for hashover DB
	$data['domain']=$hashover_domain;
	$data['thread']=get_thread_slug($row);
	$data['body']=$row['comment_content'];
	$data['status']=null;
	$data['date']=date(DateTime::ISO8601, strtotime($row['comment_date']));
	if ($hashover_admin_wp_authorFrom == $row['comment_author']) {
	    $data['name']=$hashover_admin_wp_authorTo;
	    $data['website']=$hashover_admin_wp_website;
	    $emailCrypt=$crypto->encrypt($hashover_admin_wp_email);
	    $data['email']=$emailCrypt['encrypted'];
	    $data['encryption']=$emailCrypt['keys'];
	    $data['email_hash']=md5(mb_strtolower($hashover_admin_wp_email));
	} else {
	    $data['name']=$row['comment_author'];
	    $data['website']=$row['comment_author_url'];
	    $emailCrypt=$crypto->encrypt($row['comment_author_email']);
	    $data['email']=$emailCrypt['encrypted'];
	    $data['encryption']=$emailCrypt['keys'];
	    $data['email_hash']=md5(mb_strtolower($row['comment_author_email']));
	}
	$data['password']=null;
	$data['login_id']=null;
	$data['notifications']=$hashover_notifications;
	$data['ipaddr']=$row['comment_author_IP'];
	$data['likes']=$hashover_likes;
	$data['dislikes']=$hashover_dislikes;
	// Parent / Id check...
	// If no comment parent
	if ($row['comment_parent'] == 0){
	    $req = $hashoverDbConnect->prepare('SELECT comment
						FROM comments
						WHERE domain = "'.$data['domain'].'"
						AND thread = "'.$data['thread'].'"
						AND (comment REGEXP "^[0-9]+$")
						ORDER BY comment DESC
						LIMIT 1');
	    $req->execute();
	    $row2=$req->fetch();
	    if($req->rowCount() == 0) {
		// First comment
		$comment_parent=1;
	    } else {
		// Chose next id
		$comment_parent=$row2['comment']+1;
	    }
	} else {
	    // If have parent 
	    //~ var_dump($row['comment_parent']);
	    // Search wordpress data with comment_parent
	    $req3 = $wpDbConnect->prepare('  SELECT post_name,comment_author,comment_date
					    FROM '.$wp_table_prefix.'comments, '.$wp_table_prefix.'posts
					    WHERE '.$wp_table_prefix.'posts.ID = comment_post_ID
					    AND comment_ID = '.$row['comment_parent']);
	    $req3->execute();
	    $wp_parent_data=$req3->fetch();
	    // Search hashover parent data (ID)
	    $req = $hashoverDbConnect->prepare('SELECT comment
						FROM comments
						WHERE domain = "'.$hashover_domain.'"
						AND thread = "'.$wp_parent_data['post_name'].'"
						AND name = "'.$wp_parent_data['comment_author'].'"
						AND date = "'.date(DateTime::ISO8601, strtotime($wp_parent_data['comment_date'])).'"
						LIMIT 1');
	    $req->execute();
	    //~ var_dump($req);
	    $hashover_parent_data=$req->fetch();
	    //~ var_dump($hashover_parent_data);
	    // List hashover parent data ID (if multiple child)
	    $req = $hashoverDbConnect->prepare('SELECT comment
						FROM comments
						WHERE domain = "'.$data['domain'].'"
						AND thread = "'.$data['thread'].'"
						AND (comment REGEXP "^'.$hashover_parent_data['comment'].'-[0-9]+$")
						ORDER BY comment DESC
						LIMIT 1');
	    $req->execute();
	    
	    //~ var_dump($req);
	    $row2=$req->fetch();
	    //~ var_dump($row2);
	    if($req->rowCount() == 0) {
		// First child
		$comment_parent=$hashover_parent_data['comment'].'-1';
	    } else {
		// No fist child
		//~ var_dump($row2['comment']);
		//~ // Chose next id
		$id_parent_explode=explode('-', $row2['comment']);
		$id_construction='';
		for ($i = 0; $i < count($id_parent_explode); $i++) {
		    if ($i == 0) {
			$id_construction = $id_parent_explode[$i];
		    } else {
			if ($i == count($id_parent_explode)-1) {
			    $next=$id_parent_explode[$i]+1;
			    $id_construction = $id_construction.'-'.$next;
			}else {
			    $id_construction = $id_construction.'-'.$id_parent_explode[$i];
			}
		    }
		}
		$comment_parent=$id_construction;
	    }
	    //~ var_dump($id_construction);
	}
	$data['comment']=$comment_parent;	// Id à definir... $row['comment_parent']
	//~ var_dump('ID attribué : '.$data['comment']);
	echo 'In '.$data['thread'].', by '.$data['name'].' at '.$data['date'].' with id '.$comment_parent.' : ';
	// No doublon (check)
	$req = $hashoverDbConnect->prepare('	SELECT name
						FROM comments
						WHERE domain = "'.$data['domain'].'"
						AND thread = "'.$data['thread'].'"
						AND name = "'.$data['name'].'"
						AND date = "'.$data['date'].'"');
	$req->execute();
	if($req->rowCount() == 0) {
	    // Insert in hashover DB 
	    $insertcmd = $hashoverDbConnect->prepare("INSERT INTO `comments` (domain, thread, comment, body, status, date, name, password, login_id, email, encryption, email_hash, notifications, website, ipaddr, likes, dislikes) 
							VALUES (:domain, :thread, :comment, :body, :status, :date, :name, :password, :login_id, :email, :encryption, :email_hash, :notifications, :website, :ipaddr, :likes, :dislikes)");
	    $insertcmd->execute($data);
	    echo 'Ok';
	} else {
	    echo '**Already registered**';
	}
	echo $jump; 
   }
} else {
   echo "No wordpress post found".$jump;
}

// ################ page-info
$reqPosts = $wpDbConnect->prepare('SELECT post_title,post_name
				    FROM '.$wp_table_prefix.'posts, '.$wp_table_prefix.'comments
				    WHERE '.$wp_table_prefix.'posts.ID = comment_post_ID
				    AND post_status = "publish"
				    AND comment_approved = 1');
$reqPosts->execute();
if($reqPosts->rowCount() > 0) {
    foreach($reqPosts as $row) {
	// Check doublon 
	$reqPostNameDoublon = $hashoverDbConnect->prepare('SELECT thread
				    FROM `page-info`
				    WHERE thread = "'.$row['post_name'].'"');
	$reqPostNameDoublon->execute();
	echo 'Add '.$row['post_name'].' page : ';
	// If not exist
	if($reqPostNameDoublon->rowCount() == 0) {
	    $data['domain']=$hashover_domain;
	    $data['thread']=$row['post_name'];
	    $data['url']=$wp_siteurl.$row['post_name'].'/';
	    $data['title']=$row['post_title'];
	    $insertcmd = $hashoverDbConnect->prepare("INSERT INTO `page-info` (domain, thread, url, title) 
						    VALUES (:domain, :thread, :url, :title)");
						    
	    $insertcmd->execute($data);
	    //~ var_dump($insertcmd->debugDumpParams());
	    //~ var_dump($data);
	    echo 'Ok';
	} else {
	    echo '**Already registered**';
	}
	echo $jump; 
   }
} else {
   echo "No wordpress post found".$jump;
}

exit();

?>
