<?php
/*
Plugin Name: MR Visitor Tracker
Description: Keep track of visitor information, such as IP address, user agent, and time of visit.
Version: 1.0
Author: Munshi H M Rayhan
*/

function visitor_tracker_install() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'visitor_tracker';
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        server_time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        ip varchar(100) NOT NULL,
        page_url varchar(1000) NOT NULL,
        referer_url varchar(1000),
        user_agent varchar(255) NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}
register_activation_hook( __FILE__, 'visitor_tracker_install' );

function visitor_tracker_uninstall() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'visitor_tracker';
    $sql = "DROP TABLE IF EXISTS $table_name;";
    $wpdb->query($sql);
}
register_deactivation_hook( __FILE__, 'visitor_tracker_uninstall' );


function visitor_tracker_track() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'visitor_tracker';
    $ip = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    $time = current_time( 'mysql' );
    $page_url = $_SERVER['REQUEST_URI'];
    $referer_url = $_SERVER['HTTP_REFERER'];
    $server_time = date("Y-m-d H:i:s");
    $wpdb->insert( 
        $table_name, 
        array( 
            'time' => $time, 
            'ip' => $ip, 
            'user_agent' => $user_agent, 
            'page_url' => $page_url, 
            'referer_url' => $referer_url, 
            'server_time' => $server_time, 
        ) 
    );
}
add_action( 'wp_footer', 'visitor_tracker_track' );

function visitor_tracker_admin_menu() {
    add_menu_page(
        'Visitor Tracker',
        'Visitor Tracker',
        'manage_options',
        'visitor_tracker',
        'visitor_tracker_admin_page',
        'dashicons-admin-generic',
        99
    );
}
add_action( 'admin_menu', 'visitor_tracker_admin_menu' );

function visitor_tracker_admin_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'visitor_tracker';

    $pagenum = isset( $_GET['pagenum'] ) ? absint( $_GET['pagenum'] ) : 1;
    $limit = 10;
    $offset = ( $pagenum - 1 ) * $limit;
    $total = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name" );
    $num_of_pages = ceil( $total / $limit );

    $results = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table_name ORDER BY id DESC LIMIT %d OFFSET %d", $limit, $offset ), ARRAY_A );
    ?>
    <div class="wrap">
        <h2>Visitor Tracker</h2>
        <table class="wp-list-table widefat fixed striped posts">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Time</th>
                    <th>IP</th>
                    <th>User Agent</th>
                    <th>Page URL</th>
                    <th>Referer URL</th>
                    <th>Server Time</th>
                </tr>
            </thead>
            <tbody>
                <?php
                foreach ($results as $result) {
                    ?>
                    <tr>
                        <td><?php echo $result['id']; ?></td>
                        <td><?php echo $result['time']; ?></td>
                        <td><?php echo $result['ip']; ?></td>
                        <td><?php echo $result['user_agent']; ?></td>
                        <td><?php echo $result['page_url']; ?></td>
                        <td><?php echo $result['referer_url']; ?></td>
                        <td><?php echo $result['server_time']; ?></td>
                    </tr>
                    <?php
                }
                ?>
            </tbody>
        </table>
        <?php
        $page_links = paginate_links( array(
            'base' => add_query_arg( 'pagenum', '%#%' ),
            'format' => '',
            'prev_text' => __( '&laquo;', 'text-domain' ),
            'next_text' => __( '&raquo;', 'text-domain' ),
            'total' => $num_of_pages,
            'current' => $pagenum
        ) );

        if ( $page_links ) {
            echo '<div class="tablenav"><div class="tablenav-pages" style="margin: 1em 0">' . $page_links . '</div></div>';
        }
        ?>
    </div>
    <?php
}

