<?php
/**
 * @package Simple_CSV
 * @version 1.0.0
 */
/*
Plugin Name: Simple CSV
Plugin URI: http://cbfreeman.com/downloads/simple-csv/
Description: Export Posts to a csv file.
Version: 1.0.0
Author: Craig Freeman
Author URI: http://cbfreeman.com
License: GPL2
Text Domain: exporter-max
*/
/*  Copyright 2015  Craig Freeman  (mail@cbfreeman.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

load_plugin_textdomain( 'exporter-max', false, basename( dirname( __FILE__ ) ) . '/languages' );

/**
 * Main plugin class
 *
 * @since 0.1
 **/
class Simple_CSV {

	/**
	 * Class contructor
	 *
	 * @since 0.1
	 **/
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_admin_pages' ) );
		add_action( 'init', array( $this, 'generate_csv' ) );
		add_filter( 'pp_eu_exclude_data', array( $this, 'exclude_data' ) );
	}

	/**
	 * Add administration menus
	 *
	 * @since 0.1
	 **/
	public function add_admin_pages() {
		add_users_page( __( 'Simple CSV', 'exporter-max' ), __( 'Simple CSV', 'exporter-max' ), 'list_users', 'exporter-max', array( $this, 'users_page' ) );
	}

	/**
	 * Process content of CSV file
	 *
	 * @since 0.1
	 **/
	public function generate_csv() {
		if ( isset( $_POST['_wpnonce-exporter-max-post-page_export'] ) ) {
			check_admin_referer( 'exporter-max-post-page_export', '_wpnonce-exporter-max-post-page_export' );

			
     if(isset($_POST['cat']))
     $cat = sanitize_text_field($_POST['cat']);
     if(isset($_POST['start_date']))
     $start_date = sanitize_text_field($_POST['start_date']);
     if(isset($_POST['end_date']))
     $end_date = sanitize_text_field($_POST['end_date']);

			
			$sitename = sanitize_key( get_bloginfo( 'name' ) );
			if ( ! empty( $sitename ) )
				$sitename .= '.';
			$filename = $sitename . date( 'Y-m-d-H-i-s' ) . '.csv';

			header( 'Content-Description: File Transfer' );
			header( 'Content-Disposition: attachment; filename=' . $filename );
			header( 'Content-Type: text/csv; charset=' . get_option( 'blog_charset' ), true );

              // create a file pointer connected to the output stream
      $output = fopen('php://output', 'w');

      // output the column headings
      fputcsv($output, array('ID', 'post_author', 'post_title','post_content','post_name','post_type','guid','post_status',''));

			global $wpdb;

      // fetch the data
     $term = $wpdb->get_var("SELECT term_taxonomy_id FROM {$wpdb->prefix}term_taxonomy WHERE term_id='$cat' and taxonomy='category'");

      $rows = $wpdb->get_results("SELECT e.ID,e.post_author,e.post_title,e.post_content,e.post_name,e.post_type,e.guid,e.post_status FROM $wpdb->posts e
                     LEFT JOIN $wpdb->term_relationships u ON u.object_id=e.ID  WHERE u.term_taxonomy_id='$term' and post_status='publish' and post_date >= '$start_date'
                     AND post_date <= '$end_date' ");
   
    foreach($rows as $rows){
    $value1 = $rows->ID;
    $value2 = $rows->post_author;
    $value3 = $rows->post_title;
    $value4 = $rows->post_content;
    $value5 = $rows->post_name;
    $value6  = $rows->post_type;
    $value7 = $rows->guid;
    $value8 = $rows->post_status;
    
     // output the column data
      fputcsv($output, array($value1, $value2, $value3, $value4, $value5, $value6, $value7, $value8,''));
    }

			exit;
		}
	}

	/**
	 * Content of the settings page
	 *
	 * @since 0.1
	 **/
	public function users_page() {
		if ( ! current_user_can( 'list_users' ) )
			wp_die( __( 'You do not have sufficient permissions to access this page.', 'exporter-max' ) );
?>

<div class="wrap">
	<h2><?php _e( 'Export Posts to CSV', 'exporter-max' ); ?></h2>
	<?php
	if ( isset( $_GET['error'] ) ) {
		echo '<div class="updated"><p><strong>' . __( 'No posts found.', 'exporter-max' ) . '</strong></p></div>';
	}
	?>
	<form method="post" action="" enctype="multipart/form-data">
		<?php wp_nonce_field( 'exporter-max-post-page_export', '_wpnonce-exporter-max-post-page_export' ); ?>
		<table class="form-table">
			<tr valign="top">
				<th scope="row"><label for"pp_eu_posts_cat"><?php _e( 'Category', 'exporter-max' ); ?></label></th>
				<td>
				 <select name="cat">
 <?php
$args = array(
  'orderby' => 'name',
  'order' => 'ASC'
  );
$categories = get_categories($args);
  foreach($categories as $category) {
    echo '<option value="' . $category->term_id . '" >' . $category->name.'</option> ';
   }
?>
</select>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><label><?php _e( 'Date range', 'exporter-max' ); ?></label></th>
				<td>
					<select name="start_date" id="pp_eu_users_start_date">
						<option value="0"><?php _e( 'Start Date', 'exporter-max' ); ?></option>
						<?php $this->export_date_options(); ?>
					</select>
					<select name="end_date" id="pp_eu_users_end_date">
						<option value="0"><?php _e( 'End Date', 'exporter-max' ); ?></option>
						<?php $this->export_date_options(); ?>
					</select>
				</td>
			</tr>
		</table>
		<p class="submit">
			<input type="hidden" name="_wp_http_referer" value="<?php echo $_SERVER['REQUEST_URI'] ?>" />
			<input type="submit" class="button-primary" value="<?php _e( 'Export Posts', 'exporter-max' ); ?>" />
		</p>
	</form>
	<br>
	<hr>
	<br>
	<!-- Support -->
	<div id="exporter_max_support">
		<h3><?php _e('Support & bug report', 'exporter-max'); ?></h3>
		<p><?php printf(__('If you have any idea to improve this plugin or any bug to report, please email me at : <a href="%1$s">%2$s</a>', 'exporter-max'), 'mailto:exporter-max@cbfreeman.com?subject=[exporter-max-plugin]', 'exporter-max@cbfreeman.com'); ?></p>
			<?php $donation_link = 'https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=BAZNKCE6Q78PJ'; ?>
		<p><?php printf(__('You like this plugin ? You use it in a business context ? Please, consider a <a href="%s" target="_blank" rel="external">donation</a>.', 'exporter-max'), $donation_link ); ?></p>
	</div>
	</div>
<?php
	}

	private function export_date_options() {
		global $wpdb, $wp_locale;

		$months = $wpdb->get_results( "
			SELECT DISTINCT YEAR( post_date ) AS year, MONTH( post_date ) AS month
			FROM $wpdb->posts
			ORDER BY post_date DESC
		" );

		$month_count = count( $months );
		if ( !$month_count || ( 1 == $month_count && 0 == $months[0]->month ) )
			return;

		foreach ( $months as $date ) {
			if ( 0 == $date->year )
				continue;

			$month = zeroise( $date->month, 2 );
			echo '<option value="' . $date->year . '-' . $month . '">' . $wp_locale->get_month( $month ) . ' ' . $date->year . '</option>';
		}
	}
}

new Simple_CSV;