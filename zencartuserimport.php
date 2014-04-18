<?php

/*
Plugin Name: Zen Cart User Import
Plugin URI: http://www.pramhost.com
Description: User importer from zen cart to wordpress/woocommerce
Version: 1.0.0
Author: Pramnos Hosting Ltd.
Author URI: http://www.pramhost.com
License: GPLv2
*/

/*
Copyright (C) 2014 Pramnos Hosting Ltd.
Copyright (C) 2014 Yannis - Pastis Glaros

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
*/


// Load Importer API
require_once ABSPATH . 'wp-admin/includes/import.php';

if ( ! class_exists( 'WP_Importer' ) ) {
	$class_wp_importer = ABSPATH . 'wp-admin/includes/class-wp-importer.php';
	if ( file_exists( $class_wp_importer ) )
		require $class_wp_importer;
}

if ( class_exists( 'WP_Importer' ) ) {

    class ZencartUserImport extends WP_Importer {

        /**
	 * Registered callback function
	 */
	function dispatch() {
		$this->header();

		$step = empty( $_GET['step'] ) ? 0 : (int) $_GET['step'];
		switch ( $step ) {
			case 0:
                            $this->greet();
                            break;
                        case 1:
                            $this->import();
                            break;
		}



		$this->footer();
	}

        function import()
        {

            $host = esc_sql($_POST['host']);
            $user = esc_sql($_POST['user']);
            $password = esc_sql($_POST['password']);
            $database = esc_sql($_POST['database']);
            $collation = esc_sql($_POST['collation']);

            $link = mysqli_connect($host,$user,$password,$database) or die("Error " . mysqli_error($link));
            $link->query("SET NAMES `".$collation."`;");
            $query = "SELECT * FROM `customers` c "
                    . " left join `address_book` a"
                    . " on c.`customers_default_address_id` = a.`address_book_id`";

            //execute the query.

            $result = $link->query($query);

            //display information:

            while($row = mysqli_fetch_assoc($result)) {
              echo $row['customers_firstname'] . ' ' . $row['customers_lastname'] . ':';

              $email = sanitize_email($row['customers_email_address']);
              $username = $row['customers_nick'];
              if ($username == ''){
                  $username = $email;
              }
              $username = sanitize_user( $username, true );

              if( !email_exists( $email ) && !username_exists($username)) {

                    $user_data = array(
                          'user_login' => $username,
                          'role' => 'customer',
                          'user_pass' => wp_generate_password(),
                          'user_email' => $email,
                          'display_name' => $row['customers_firstname'] . ' ' . $row['customers_lastname'],
                          'first_name' => $row['customers_firstname'],
                          'last_name' => $row['customers_lastname']
                  );
                  $user_id = wp_insert_user( $user_data );

                  if (is_int($user_id)){
                      echo ' ID:' . $user_id . '<br />';

                      update_user_meta($user_id, 'billing_address_1', $row['entry_street_address']);
                      update_user_meta($user_id, 'billing_city', $row['entry_city']);
                      update_user_meta($user_id, 'billing_postcode', $row['entry_postcode']);
                      update_user_meta($user_id, 'billing_phone', $row['customers_telephone']);
                      update_user_meta($user_id, 'shipping_address_1', $row['entry_street_address']);
                      update_user_meta($user_id, 'shipping_city', $row['entry_city']);
                      update_user_meta($user_id, 'shipping_postcode', $row['entry_postcode']);
                      update_user_meta($user_id, 'shipping_phone', $row['customers_telephone']);
                      if ($row['entry_lastname'] != ''){
                        update_user_meta($user_id, 'shipping_first_name', $row['entry_firstname']);
                        update_user_meta($user_id, 'shipping_last_name', $row['entry_lastname']);
                        update_user_meta($user_id, 'billing_first_name', $row['entry_firstname']);
                        update_user_meta($user_id, 'billing_last_name', $row['entry_lastname']);
                      } else {
                        update_user_meta($user_id, 'shipping_first_name', $row['customers_firstname']);
                        update_user_meta($user_id, 'shipping_last_name', $row['customers_lastname']);
                        update_user_meta($user_id, 'billing_first_name', $row['customers_firstname']);
                        update_user_meta($user_id, 'billing_last_name', $row['customers_lastname']);
                      }
                  } else {
                      echo 'Cannot Insert user...<br />';
                  }

                 } else {
                     echo ' Already Exists<br />';
                 }

            }
        }

        /**
	 * Display introductory text and file upload form
	 */
	function greet() {
		echo '<div class="narrow">';
		?>
                <form method="post" action="admin.php?import=zcui&amp;step=1">

                    <table class="form-table">
                        <tbody>
                            <tr>
                                <td><label for="host"><?php _e('Host', 'zcui');?>:</label></td>
                                <td><input required type="text" name="host" id="host" value="localhost" /></td>
                            </tr>
                            <tr>
                                <td><label for="user"><?php _e('User', 'zcui');?>:</label></td>
                                <td><input required type="text" name="user" id="user" value="" /></td>
                            </tr>
                            <tr>
                                <td><label for="password"><?php _e('Password', 'zcui');?>:</label></td>
                                <td><input type="text" name="password" id="password" value="" /></td>
                            </tr>
                            <tr>
                                <td><label for="database"><?php _e('Database', 'zcui');?>:</label></td>
                                <td><input required type="text" name="database" id="database" value="" /></td>
                            </tr>
                            <tr>
                                <td><label for="collation"><?php _e('Collation', 'zcui');?>:</label></td>
                                <td>
                                    <select id="collation" name="collation">
                                        <option value="utf8">UTF8</option>
                                        <option value="greek">Greek</option>
                                    </select>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <p>
                        <input class="button-primary" type="submit" name="submit" value="<?php _e('Import Data', 'zcui');?>" />
                    </p>
                </form>
                <?php

		echo '</div>';
	}

        // Display import page title
	function header() {
		echo '<div class="wrap">';
		echo '<div id="icon-tools" class="icon32"></div>';
		echo '<h2>' . __( 'Import Zen Cart Users', 'zcui' ) . '</h2>';
	}

	// Close div.wrap
	function footer() {
		echo '</div>';
	}

    }
}

function zcui_init() {
	load_plugin_textdomain( 'zcui', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

        /**
	 * Zencart Importer object for registering the import callback
	 * @global ZencartUserImport $zcui
	 */
        @error_reporting(E_ALL | E_WARNING | E_PARSE | E_NOTICE | E_DEPRECATED | E_STRICT);
        @ini_set('display_errors', 'On');
	$GLOBALS['zcui'] = new ZencartUserImport();
	register_importer( 'zcui', 'Zen Cart users', __('Import <strong>users</strong> from a zen cart database.', 'zcui'), array( $GLOBALS['zcui'], 'dispatch' ) );


}
add_action( 'admin_init', 'zcui_init' );