<?php
/**
 * Weclome Page Class
 *
 * @package     PDD
 * @subpackage  Admin/Welcome
 * @copyright   Copyright (c) 2014, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.4
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * PDD_Welcome Class
 *
 * A general class for About and Credits page.
 *
 * @since 1.4
 */
class PDD_Welcome {

	/**
	 * @var string The capability users should have to view the page
	 */
	public $minimum_capability = 'manage_options';

	/**
	 * Get things started
	 *
	 * @since 1.4
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'admin_menus') );
		add_action( 'admin_head', array( $this, 'admin_head' ) );
		add_action( 'admin_init', array( $this, 'welcome'    ) );
	}

	/**
	 * Register the Dashboard Pages which are later hidden but these pages
	 * are used to render the Welcome and Credits pages.
	 *
	 * @access public
	 * @since 1.4
	 * @return void
	 */
	public function admin_menus() {
		// About Page
		add_dashboard_page(
			__( 'Welcome to Pojo Digital Donations', 'pdd' ),
			__( 'Welcome to Pojo Digital Donations', 'pdd' ),
			$this->minimum_capability,
			'pdd-about',
			array( $this, 'about_screen' )
		);

		// Changelog Page
		add_dashboard_page(
			__( 'Pojo Digital Donations Changelog', 'pdd' ),
			__( 'Pojo Digital Donations Changelog', 'pdd' ),
			$this->minimum_capability,
			'pdd-changelog',
			array( $this, 'changelog_screen' )
		);

		// Getting Started Page
		add_dashboard_page(
			__( 'Getting started with Pojo Digital Donations', 'pdd' ),
			__( 'Getting started with Pojo Digital Donations', 'pdd' ),
			$this->minimum_capability,
			'pdd-getting-started',
			array( $this, 'getting_started_screen' )
		);

		// Credits Page
		add_dashboard_page(
			__( 'The people that build Pojo Digital Donations', 'pdd' ),
			__( 'The people that build Pojo Digital Donations', 'pdd' ),
			$this->minimum_capability,
			'pdd-credits',
			array( $this, 'credits_screen' )
		);
	}

	/**
	 * Hide Individual Dashboard Pages
	 *
	 * @access public
	 * @since 1.4
	 * @return void
	 */
	public function admin_head() {
		remove_submenu_page( 'index.php', 'pdd-about' );
		remove_submenu_page( 'index.php', 'pdd-changelog' );
		remove_submenu_page( 'index.php', 'pdd-getting-started' );
		remove_submenu_page( 'index.php', 'pdd-credits' );

		// Badge for welcome page
		$badge_url = PDD_PLUGIN_URL . 'assets/images/pdd-badge.png';
		?>
		<style type="text/css" media="screen">
		/*<![CDATA[*/
		.pdd-badge {
			padding-top: 150px;
			height: 52px;
			width: 185px;
			color: #666;
			font-weight: bold;
			font-size: 14px;
			text-align: center;
			text-shadow: 0 1px 0 rgba(255, 255, 255, 0.8);
			margin: 0 -5px;
			background: url('<?php echo $badge_url; ?>') no-repeat;
		}

		.about-wrap .pdd-badge {
			position: absolute;
			top: 0;
			right: 0;
		}

		.pdd-welcome-screenshots {
			float: right;
			margin-left: 10px!important;
		}

		.about-wrap .feature-section {
			margin-top: 20px;
		}

		/*]]>*/
		</style>
		<?php
	}

	/**
	 * Navigation tabs
	 *
	 * @access public
	 * @since 1.9
	 * @return void
	 */
	public function tabs() {
		$selected = isset( $_GET['page'] ) ? $_GET['page'] : 'pdd-about';
		?>
		<h2 class="nav-tab-wrapper">
			<a class="nav-tab <?php echo $selected == 'pdd-about' ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'pdd-about' ), 'index.php' ) ) ); ?>">
				<?php _e( "What's New", 'pdd' ); ?>
			</a>
			<a class="nav-tab <?php echo $selected == 'pdd-getting-started' ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'pdd-getting-started' ), 'index.php' ) ) ); ?>">
				<?php _e( 'Getting Started', 'pdd' ); ?>
			</a>
			<a class="nav-tab <?php echo $selected == 'pdd-credits' ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'pdd-credits' ), 'index.php' ) ) ); ?>">
				<?php _e( 'Credits', 'pdd' ); ?>
			</a>
		</h2>
		<?php
	}

	/**
	 * Render About Screen
	 *
	 * @access public
	 * @since 1.4
	 * @return void
	 */
	public function about_screen() {
		list( $display_version ) = explode( '-', PDD_VERSION );
		?>
		<div class="wrap about-wrap">
			<h1><?php printf( __( 'Welcome to Pojo Digital Donations %s', 'pdd' ), $display_version ); ?></h1>
			<div class="about-text"><?php printf( __( 'Thank you for updating to the latest version! Pojo Digital Donations %s is ready to make your online store faster, safer, and better!', 'pdd' ), $display_version ); ?></div>
			<div class="pdd-badge"><?php printf( __( 'Version %s', 'pdd' ), $display_version ); ?></div>

			<?php $this->tabs(); ?>

			<div class="changelog">
				<h3><?php _e( 'Sequential Order Numbers', 'pdd' );?></h3>

				<div class="feature-section">

					<img src="<?php echo PDD_PLUGIN_URL . 'assets/images/screenshots/20-sequential.png'; ?>" class="pdd-welcome-screenshots"/>

					<h4><?php _e( 'Prefix, Postfix, and Starting Number', 'pdd' );?></h4>
					<p><?php printf( __( 'Sequential order numbers are now supported out of the box. Simply go to <a href="%s">Settings &rarr; Misc</a> to enable them. The starting number, prefix, and postfix for order numbers can all be easily configured.', 'pdd' ), admin_url( 'edit.php?post_type=download&page=pdd-settings&tab=misc' ) ); ?></p>

					<h4><?php _e( 'Upgrade Routine', 'pdd' );?></h4>
					<p><?php _e( 'Sequential order numbers are important for some and even mandatory for others, so we want to ensure that all users can make use of them. For this reason, we have provided a one-click upgrade routine that will update all previous purchase records with sequential order numbers matching your settings.', 'pdd' );?></p>

				</div>
			</div>

			<div class="changelog">
				<h3><?php _e( 'Improved Checkout Experience', 'pdd' );?></h3>

				<div class="feature-section">

					<img src="<?php echo PDD_PLUGIN_URL . 'assets/images/screenshots/20-register-login.png'; ?>" class="pdd-welcome-screenshots"/>

					<h4><?php _e( 'Registration / Login Forms', 'pdd' );?></h4>
					<p><?php _e( 'The registration and login form options for the checkout form have been further refined in version 2.0. They now include granular control that let you determine exactly which forms are displayed. You can have just a login form, just a registration form, both forms, or neither. The choice is yours.', 'pdd' );?></p>

					<h4><?php _e( 'Live Item Quantity Updates', 'pdd' );?></h4>
					<p><?php _e( 'Cart item quantities are now updated on the fly when customers adjust them, meaning customers no longer need to click Update Cart and wait for the page to reload to see their new purchase total. This creates a more fluid and rapid checkout experience.', 'pdd' );?></p>

					<h4><?php _e( '100% Discounts and Credit Cards', 'pdd' );?></h4>
					<p><?php _e( 'Many users choose to offer 100% discount codes to customers, perhaps as part of a promotion or giveaway. As of version 2.0, 100% discount codes now work perfectly even when using a credit card processing payment gateway.', 'pdd' );?></p>

					<img src="<?php echo PDD_PLUGIN_URL . 'assets/images/screenshots/20-discount.png'; ?>" class="pdd-welcome-screenshots"/>

					<h4><?php _e( 'Redeeming Discount Code', 'pdd' );?></h4>
					<p><?php _e( 'The discount code redemption proceess during checkout is now smoother and more intuitive. Simpler checkout processes for customers means more successful sales for you.', 'pdd' );?></p>
					<p><?php _e( 'In version 2.0, customers are given the opportunity to redeem their discount code before selecting their payment method, if the site has AJAX processing disabled.', 'pdd' );?></p>


				</div>
			</div>

			<div class="changelog">
				<h3><?php _e( 'Payment History Enhancements', 'pdd' );?></h3>

				<div class="feature-section">

					<h4><?php _e( 'Copy Download Link(s)','pdd' );?></h4>
					<p><?php _e( 'The Purchased Downloads section of the order details screen now includes an option to copy secure download links for any file purchased, letting you easily copy-and-paste new file download links for customers.', 'pdd' );?></p>

					<h4><?php _e( 'Transaction ID Searching', 'pdd' ); ?></h4>
					<p><?php _e( 'Version 2.0 now supports searching for payment records by the transaction ID from the payment processor. Have you refunded a purchase in PayPal and now need to locate it in your store\'s history? Now it is even easier.', 'pdd' ); ?></p>

					<img src="<?php echo PDD_PLUGIN_URL . 'assets/images/screenshots/20-unlimited-downloads.png'; ?>" class="pdd-welcome-screenshots"/>

					<h4><?php _e( 'Unlimited File Downloads', 'pdd' ); ?></h4>
					<p><?php _e( 'Several versions ago, PDD supported giving specific customers unlimited file downloads for a particular purchase. This option was accidentially removed but has now been brought back from the sad, sad grave. It is now a happy feature. You can use this option to bypass the standard file download limits imposed on purchases.', 'pdd' ); ?></p>

				</div>
			</div>

			<div class="changelog">
				<h3><?php _e( 'Additional Updates', 'pdd' );?></h3>

				<div class="feature-section col three-col">
					<div>
						<h4><?php _e( 'API Keys', 'pdd' );?></h4>
						<p><?php _e( 'PDD has a complete REST API for interfacing with 3rd party systems, such as mobile devices. Granting users access to the API data was always a bit cumbersome, so in version 2.0 we have introduced a completely new API Keys table under the Tools page.', 'pdd' );?></p>

						<h4><?php _e( 'Tools Page', 'pdd' );?></h4>
						<p><?php _e( 'As more and more tools have been developed to assist with managing your store, the Tools page got a bit cluttered. We have now taken the time to introduce a proper tabbed interface to keep things neat and tidy.', 'pdd' );?></p>
					</div>

					<div>
						<h4><?php _e( 'Banned Emails', 'pdd' );?></h4>
						<p><?php _e( 'Along with the new Tools page, we have introduced a new tool that allows you to blacklist specific email addresses. Emails placed on this list will not be allowed to make purchases. This is useful for combatting fraud.' ,'pdd' );?></p>

						<h4><?php _e( 'Shortcode: [pdd_register]', 'pdd' );?></h4>
						<p><?php _e( 'Many users have asked for an option to give potential customers a way to register an account on the site without being required to go through the checkout screen. The new [pdd_register] shortcode lets you place a stand-alone registration form on any page.', 'pdd' );?></p>
					</div>

					<div class="last-feature">
						<h4><?php _e( 'Export Earnings / Sales Over Time', 'pdd' );?></h4>
						<p><?php _e( 'The export options have been improved in version 2.0. You can now export a CSV file of earnings and sales over time. Want to have a CSV that shows earnings and sale counts for the last six months? Now you can.', 'pdd' );?></p>

						<h4><?php _e( 'Improved Discount Edit Screen', 'pdd' ); ?></h4>
						<p><?php _e( 'We try and live up to our name and make all aspects of running your store easy. Unfortunately, the options available when creating discount codes have never been <em>easy</em>. Version 2.0 introduces several refinements to the discount edit screen that make it dramatically more intuitive.', 'pdd' );?></p>
					</div>
				</div>
			</div>

			<div class="return-to-dashboard">
				<a href="<?php echo esc_url( admin_url( add_query_arg( array( 'post_type' => 'download', 'page' => 'pdd-settings' ), 'edit.php' ) ) ); ?>"><?php _e( 'Go to Pojo Digital Donations Settings', 'pdd' ); ?></a> &middot;
				<a href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'pdd-changelog' ), 'index.php' ) ) ); ?>"><?php _e( 'View the Full Changelog', 'pdd' ); ?></a>
			</div>
		</div>
		<?php
	}

	/**
	 * Render Changelog Screen
	 *
	 * @access public
	 * @since 2.0.3
	 * @return void
	 */
	public function changelog_screen() {
		list( $display_version ) = explode( '-', PDD_VERSION );
		?>
		<div class="wrap about-wrap">
			<h1><?php _e( 'Pojo Digital Donations Changelog', 'pdd' ); ?></h1>
			<div class="about-text"><?php printf( __( 'Thank you for updating to the latest version! Pojo Digital Donations %s is ready to make your online store faster, safer, and better!', 'pdd' ), $display_version ); ?></div>
			<div class="pdd-badge"><?php printf( __( 'Version %s', 'pdd' ), $display_version ); ?></div>

			<?php $this->tabs(); ?>

			<div class="changelog">
				<h3><?php _e( 'Full Changelog', 'pdd' );?></h3>

				<div class="feature-section">
					<?php echo $this->parse_readme(); ?>
				</div>
			</div>

			<div class="return-to-dashboard">
				<a href="<?php echo esc_url( admin_url( add_query_arg( array( 'post_type' => 'download', 'page' => 'pdd-settings' ), 'edit.php' ) ) ); ?>"><?php _e( 'Go to Pojo Digital Donations Settings', 'pdd' ); ?></a>
			</div>
		</div>
		<?php
	}

	/**
	 * Render Getting Started Screen
	 *
	 * @access public
	 * @since 1.9
	 * @return void
	 */
	public function getting_started_screen() {
		list( $display_version ) = explode( '-', PDD_VERSION );
		?>
		<div class="wrap about-wrap">
			<h1><?php printf( __( 'Welcome to Pojo Digital Donations %s', 'pdd' ), $display_version ); ?></h1>
			<div class="about-text"><?php printf( __( 'Thank you for updating to the latest version! Pojo Digital Donations %s is ready to make your online store faster, safer and better!', 'pdd' ), $display_version ); ?></div>
			<div class="pdd-badge"><?php printf( __( 'Version %s', 'pdd' ), $display_version ); ?></div>

			<?php $this->tabs(); ?>

			<p class="about-description"><?php _e( 'Use the tips below to get started using Pojo Digital Donations. You will be up and running in no time!', 'pdd' ); ?></p>

			<div class="changelog">
				<h3><?php _e( 'Creating Your First Download Product', 'pdd' );?></h3>

				<div class="feature-section">

					<img src="<?php echo PDD_PLUGIN_URL . 'assets/images/screenshots/edit-download.png'; ?>" class="pdd-welcome-screenshots"/>

					<h4><?php printf( __( '<a href="%s">%s &rarr; Add New</a>', 'pdd' ), admin_url( 'post-new.php?post_type=download' ), pdd_get_label_plural() ); ?></h4>
					<p><?php printf( __( 'The %s menu is your access point for all aspects of your Pojo Digital Donations product creation and setup. To create your first product, simply click Add New and then fill out the product details.', 'pdd' ), pdd_get_label_plural() ); ?></p>

					<h4><?php _e( 'Product Price', 'pdd' );?></h4>
					<p><?php _e( 'Products can have simple prices or variable prices if you wish to have more than one price point for a product. For a single price, simply enter the price. For multiple price points, click <em>Enable variable pricing</em> and enter the options.', 'pdd' );?></p>

					<h4><?php _e( 'Download Files', 'pdd' );?></h4>
					<p><?php _e( 'Uploading the downloadable files is simple. Click <em>Upload File</em> in the Download Files section and choose your download file. To add more than one file, simply click the <em>Add New</em> button.', 'pdd' );?></p>

				</div>
			</div>

			<div class="changelog">
				<h3><?php _e( 'Display a Product Grid', 'pdd' );?></h3>

				<div class="feature-section">

					<img src="<?php echo PDD_PLUGIN_URL . 'assets/images/screenshots/grid.png'; ?>" class="pdd-welcome-screenshots"/>

					<h4><?php _e( 'Flexible Product Grids','pdd' );?></h4>
					<p><?php _e( 'The [downloads] shortcode will display a product grid that works with any theme, no matter the size. It is even responsive!', 'pdd' );?></p>

					<h4><?php _e( 'Change the Number of Columns', 'pdd' );?></h4>
					<p><?php _e( 'You can easily change the number of columns by adding the columns="x" parameter:', 'pdd' );?></p>
					<p><pre>[downloads columns="4"]</pre></p>

					<h4><?php _e( 'Additional Display Options', 'pdd' ); ?></h4>
					<p><?php printf( __( 'The product grids can be customized in any way you wish and there is <a href="%s">extensive documentation</a> to assist you.', 'pdd' ), 'http://easydigitaldownloads.com/documentation' ); ?></p>
				</div>
			</div>

			<div class="changelog">
				<h3><?php _e( 'Purchase Buttons Anywhere', 'pdd' );?></h3>

				<div class="feature-section">

					<img src="<?php echo PDD_PLUGIN_URL . 'assets/images/screenshots/purchase-link.png'; ?>" class="pdd-welcome-screenshots"/>

					<h4><?php _e( 'The <em>[purchase_link]</em> Shortcode','pdd' );?></h4>
					<p><?php _e( 'With easily accessible shortcodes to display purchase buttons, you can add a Buy Now or Add to Cart button for any product anywhere on your site in seconds.', 'pdd' );?></p>

					<h4><?php _e( 'Buy Now Buttons', 'pdd' );?></h4>
					<p><?php _e( 'Purchase buttons can behave as either Add to Cart or Buy Now buttons. With Buy Now buttons customers are taken straight to PayPal, giving them the most frictionless purchasing experience possible.', 'pdd' );?></p>

				</div>
			</div>

			<div class="changelog">
				<h3><?php _e( 'Need Help?', 'pdd' );?></h3>

				<div class="feature-section">

					<h4><?php _e( 'Phenomenal Support','pdd' );?></h4>
					<p><?php _e( 'We do our best to provide the best support we can. If you encounter a problem or have a question, post a question in the <a href="https://easydigitaldownloads.com/support">support forums</a>.', 'pdd' );?></p>

					<h4><?php _e( 'Need Even Faster Support?', 'pdd' );?></h4>
					<p><?php _e( 'Our <a href="https://easydigitaldownloads.com/support/pricing/">Priority Support forums</a> are there for customers that need faster and/or more in-depth assistance.', 'pdd' );?></p>

				</div>
			</div>

			<div class="changelog">
				<h3><?php _e( 'Stay Up to Date', 'pdd' );?></h3>

				<div class="feature-section">

					<h4><?php _e( 'Get Notified of Extension Releases','pdd' );?></h4>
					<p><?php _e( 'New extensions that make Pojo Digital Donations even more powerful are released nearly every single week. Subscribe to the newsletter to stay up to date with our latest releases. <a href="http://eepurl.com/kaerz" target="_blank">Signup now</a> to ensure you do not miss a release!', 'pdd' );?></p>

					<h4><?php _e( 'Get Alerted About New Tutorials', 'pdd' );?></h4>
					<p><?php _e( '<a href="http://eepurl.com/kaerz" target="_blank">Signup now</a> to hear about the latest tutorial releases that explain how to take Pojo Digital Donations further.', 'pdd' );?></p>

				</div>
			</div>

			<div class="changelog">
				<h3><?php _e( 'Extensions for Everything', 'pdd' );?></h3>

				<div class="feature-section">

					<h4><?php _e( 'Over 250 Extensions','pdd' );?></h4>
					<p><?php _e( 'Add-on plugins are available that greatly extend the default functionality of Pojo Digital Donations. There are extensions for payment processors, such as Stripe and PayPal, extensions for newsletter integrations, and many, many more.', 'pdd' );?></p>

					<h4><?php _e( 'Visit the Extension Store', 'pdd' );?></h4>
					<p><?php _e( '<a href="https://easydigitaldownloads.com/extensions" target="_blank">The Extensions store</a> has a list of all available extensions, including convenient category filters so you can find exactly what you are looking for.', 'pdd' );?></p>

				</div>
			</div>

		</div>
		<?php
	}

	/**
	 * Render Credits Screen
	 *
	 * @access public
	 * @since 1.4
	 * @return void
	 */
	public function credits_screen() {
		list( $display_version ) = explode( '-', PDD_VERSION );
		?>
		<div class="wrap about-wrap">
			<h1><?php printf( __( 'Welcome to Pojo Digital Donations %s', 'pdd' ), $display_version ); ?></h1>
			<div class="about-text"><?php printf( __( 'Thank you for updating to the latest version! Pojo Digital Donations %s is ready to make your online store faster, safer and better!', 'pdd' ), $display_version ); ?></div>
			<div class="pdd-badge"><?php printf( __( 'Version %s', 'pdd' ), $display_version ); ?></div>

			<?php $this->tabs(); ?>

			<p class="about-description"><?php _e( 'Pojo Digital Donations is created by a worldwide team of developers who aim to provide the #1 eCommerce platform for selling digital goods through WordPress.', 'pdd' ); ?></p>

			<?php echo $this->contributors(); ?>
		</div>
		<?php
	}


	/**
	 * Parse the PDD readme.txt file
	 *
	 * @since 2.0.3
	 * @return string $readme HTML formatted readme file
	 */
	public function parse_readme() {
		$file = file_exists( PDD_PLUGIN_DIR . 'readme.txt' ) ? PDD_PLUGIN_DIR . 'readme.txt' : null;

		if ( ! $file ) {
			$readme = '<p>' . __( 'No valid changlog was found.', 'pdd' ) . '</p>';
		} else {
			$readme = file_get_contents( $file );
			$readme = nl2br( esc_html( $readme ) );
			$readme = explode( '== Changelog ==', $readme );
			$readme = end( $readme );

			$readme = preg_replace( '/`(.*?)`/', '<code>\\1</code>', $readme );
			$readme = preg_replace( '/[\040]\*\*(.*?)\*\*/', ' <strong>\\1</strong>', $readme );
			$readme = preg_replace( '/[\040]\*(.*?)\*/', ' <em>\\1</em>', $readme );
			$readme = preg_replace( '/= (.*?) =/', '<h4>\\1</h4>', $readme );
			$readme = preg_replace( '/\[(.*?)\]\((.*?)\)/', '<a href="\\2">\\1</a>', $readme );
		}

		return $readme;
	}


	/**
	 * Render Contributors List
	 *
	 * @since 1.4
	 * @uses PDD_Welcome::get_contributors()
	 * @return string $contributor_list HTML formatted list of all the contributors for PDD
	 */
	public function contributors() {
		$contributors = $this->get_contributors();

		if ( empty( $contributors ) )
			return '';

		$contributor_list = '<ul class="wp-people-group">';

		foreach ( $contributors as $contributor ) {
			$contributor_list .= '<li class="wp-person">';
			$contributor_list .= sprintf( '<a href="%s" title="%s">',
				esc_url( 'https://github.com/' . $contributor->login ),
				esc_html( sprintf( __( 'View %s', 'pdd' ), $contributor->login ) )
			);
			$contributor_list .= sprintf( '<img src="%s" width="64" height="64" class="gravatar" alt="%s" />', esc_url( $contributor->avatar_url ), esc_html( $contributor->login ) );
			$contributor_list .= '</a>';
			$contributor_list .= sprintf( '<a class="web" href="%s">%s</a>', esc_url( 'https://github.com/' . $contributor->login ), esc_html( $contributor->login ) );
			$contributor_list .= '</a>';
			$contributor_list .= '</li>';
		}

		$contributor_list .= '</ul>';

		return $contributor_list;
	}

	/**
	 * Retreive list of contributors from GitHub.
	 *
	 * @access public
	 * @since 1.4
	 * @return array $contributors List of contributors
	 */
	public function get_contributors() {
		$contributors = get_transient( 'pdd_contributors' );

		if ( false !== $contributors )
			return $contributors;

		$response = wp_remote_get( 'https://api.github.com/repos/pojome/pojo-digital-donations/contributors', array( 'sslverify' => false ) );

		if ( is_wp_error( $response ) || 200 != wp_remote_retrieve_response_code( $response ) )
			return array();

		$contributors = json_decode( wp_remote_retrieve_body( $response ) );

		if ( ! is_array( $contributors ) )
			return array();

		set_transient( 'pdd_contributors', $contributors, 3600 );

		return $contributors;
	}

	/**
	 * Sends user to the Welcome page on first activation of PDD as well as each
	 * time PDD is upgraded to a new version
	 *
	 * @access public
	 * @since 1.4
	 * @global $pdd_options Array of all the PDD Options
	 * @return void
	 */
	public function welcome() {
		global $pdd_options;

		// Bail if no activation redirect
		if ( ! get_transient( '_pdd_activation_redirect' ) )
			return;

		// Delete the redirect transient
		delete_transient( '_pdd_activation_redirect' );

		// Bail if activating from network, or bulk
		if ( is_network_admin() || isset( $_GET['activate-multi'] ) )
			return;

		$upgrade = get_option( 'pdd_version_upgraded_from' );

		if( ! $upgrade ) { // First time install
			wp_safe_redirect( admin_url( 'index.php?page=pdd-getting-started' ) ); exit;
		} else { // Update
			wp_safe_redirect( admin_url( 'index.php?page=pdd-about' ) ); exit;
		}
	}
}
new PDD_Welcome();
