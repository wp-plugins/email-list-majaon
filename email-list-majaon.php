<?php
/*
Plugin Name: Email List Majaon
Plugin URI: http://majaon.com/wordpress.html
Description: Show list of email, saved in DB, by using contact form. 
Version: 0.1
Author: Bjorn
Author URI: http://majaon.com
*/

/* Copyright YEAR PLUGIN_AUTHOR_NAME (email : PLUGIN AUTHOR EMAIL)
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.
This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
*/

/**
 * Activation / deactivation hooks.
 */

if(function_exists('register_activation_hook'))
	register_activation_hook(__FILE__, 'email_list_majaon_set_options');
if(function_exists('register_deactivation_hook'))
	register_deactivation_hook(__FILE__, 'email_list_majaon_unset_options');

add_action('admin_menu', 'email_list_majaon_admin_page');
add_action('init', 'init_textdomain_localization'); //translation things


$email_list_majaon_table = email_list_majaon_get_table_handle();



function init_textdomain_localization() {
    if (function_exists('load_plugin_textdomain')) {
		load_plugin_textdomain('email-list-majaon', false, dirname(plugin_basename( __FILE__ )).'/languages');
    }
}


function email_list_majaon_get_table_handle() {
    global $wpdb; // класс wordpress для работы с БД
    return $wpdb->prefix . "email_list_majaon"; // создаём имя таблицы настроек плагина
}


function email_list_majaon_set_options(){
    global $wpdb;
    add_option('email_list_majaon_email_account', ''); // будет ли плагин по умолчанию обрабатывать заголовки записей. 0 - нет
 
    $email_list_majaon_table = email_list_majaon_get_table_handle(); // вызов функции повторяется, т. к. данные действия происходят на этапе установки плагина, когда вызов в теле еще не может быть осуществлён
    $charset_collate = ''; // кодировка БД
    if ( version_compare(mysql_get_server_info(), '4.1.0', '>=') )
            $charset_collate = "DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci"; // устанавливаем уникод
    if($wpdb->get_var("SHOW TABLES LIKE '$email_list_majaon_table'") != $email_list_majaon_table) { // если таблица настроек плагина еще не создана - создаём
        $sql = "CREATE TABLE `" . $email_list_majaon_table . "` (
            `id` INT NOT NULL AUTO_INCREMENT,
            `name` VARCHAR(255) NOT NULL default '',
            `email` VARCHAR(255) NOT NULL default '',
			`phone` VARCHAR(255) NOT NULL default '',
            `subscribe` VARCHAR(3) NOT NULL default '',
            UNIQUE KEY id (id)
        )$charset_collate";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php'); // обращение к функциям wordpress для
        dbDelta($sql); // работы с БД. создаём новую таблицу
	}
	
}

function email_list_majaon_unset_options () {
	global $wpdb, $email_list_majaon_table;
    delete_option('email_list_majaon');
    $sql = "DROP TABLE $email_list_majaon_table";
    $wpdb->query($sql);
	
}

function email_list_majaon_admin_page() {
    add_options_page('E-mails List Majaon', 'E-mail Majaon', 8, __FILE__, 'email_list_majaon_options_page');
}

function email_list_majaon_options_page() {      //Функция создания и обработки страницы настроек плагина
    global $wpdb, $email_list_majaon_table;
    $cmd = $_POST['cmd'];               //Обработка пользовательского ввода
    $email = $_POST['email'];
    $subscribe = $_POST['subscribe'];

    if ($cmd == "change_subscribe") {
            $sql = "UPDATE $email_list_majaon_table SET subscribe = '$subscribe' WHERE email = '$email' ";
            $wpdb->query($sql);
	?>
        <div class="updated"><p><strong> <?php echo _e('User updated','email-list-majaon'); ?></strong></p></div> 
    <?php
	    }
    if ($cmd == "delete") {
            $sql = "DELETE from $email_list_majaon_table WHERE email = '$email' ";
            $wpdb->query($sql);
	?>
        <div class="updated"><p><strong> <?php echo _e('User removed','email-list-majaon'); ?></strong></p></div> 
    <?php
	    }

?>
    <div class="wrap">
    <h2><?php echo _e('E-mails list Majaon','email-list-majaon'); ?></h2>
 
    <h3><?php echo _e('E-mails list','email-list-majaon'); ?></h3> 

    
    <table class="form-table">
    <tr>
    <th colspan=2 scope="row"> <!-- area for email list -->
		<?php 		
		$sql = "SELECT * from $email_list_majaon_table";
		$emails_list = $wpdb->get_results($sql);
		echo "<table>";
		echo "<tr>";
		echo "<td cellspacing='5'>"._e('Name','email-list-majaon')."</td>";	
		echo "<td cellspacing='5'>"._e('E-mail','email-list-majaon')."</td>";	
		echo "<td cellspacing='5'>"._e('Phone','email-list-majaon')."</td>";	
		echo "<td cellspacing='5'></td>";	
		echo "<td cellspacing='5'>"._e('Action','email-list-majaon')."</td>";	
		echo "</tr>";
		foreach ($emails_list as $res) {
			$checked = "";
			if($res->subscribe == "yes"){
				$checked = "checked";
				$button_name = 'unsubscribe';
				$subscribe = '';
			}else{
				$button_name = 'subscribe';
				$subscribe = 'yes';
			}
			
			echo "<tr>";
			echo "<td>".$res->name."</td><td>".$res->email."</td><td>".$res->phone."</td>";
			echo "<td>";
			echo "<form method='post' action=".$_SERVER['REQUEST_URI'].">";
			//echo "<input type='checkbox' name='subscribe' $checked value='yes'/>";
			echo "<input type='hidden' name='subscribe' value='$subscribe'>";
			echo "<input type='hidden' name='cmd' value='change_subscribe'>";
			echo "<input type='hidden' name='email' value='".$res->email."'>";
			echo "<input type='submit' name='Submit' value='$button_name' />";
			echo "</form></td>";
			echo "<td>";
			echo "<form method='post' action=".$_SERVER['REQUEST_URI'].">";
			echo "<input type='hidden' name='cmd' value='delete'>";
			echo "<input type='hidden' name='email' value='".$res->email."'>";
			echo "<input type='submit' name='Submit' value='delete' />";
			echo "</form></td>";
			echo "</tr>";
		}
		echo "</table>";
		?>
        <br/>
    </th>
    </tr>
    </table>
 
 <!--   Information about plugin -->
    <h3><?php echo _e('Plugin developed','email-list-majaon'); ?></h3>
    <table class="form-table">
    <tr><th>
    <ul>
   <li><?php echo _e('By: <a href="http://majaon.com/" target="_blank">Majaon 2010 majaon.com</a>','email-list-majaon'); ?></li>
    </ul>
   </th></tr></table>
 
    </div>
<?php
}

function add_email_to_db($name, $email, $phone, $subscribe){
		global $wpdb, $email_list_majaon_table;
		$sql = "SELECT email from $email_list_majaon_table WHERE email = '$email'";
		$current_email = $wpdb->get_var($sql);
		
		if($current_email != $email){
			$sql = "INSERT INTO $email_list_majaon_table (id,name,email,phone,subscribe) VALUES (NULL, '$name', '$email', '$phone', '$subscribe');";		
            $wpdb->query($sql);
		}
		return '';
}

?>
