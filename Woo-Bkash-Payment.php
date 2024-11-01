<?php

/*

Plugin Name: Bkash Payment Woocommerce
Plugin URI: httts://wordpress.org/plugins/Bkash-Payment-Woocommerce
Description: very easy to use woocommerce bkash payment gateway
Version: 1.0
Author: nayon
Author URI: http://nayonbd.com

*/





add_filter( 'woocommerce_payment_gateways', 'Bpg_add_your_gateway_class' );

function Bpg_add_your_gateway_class( $methods ) {
	$methods[] = 'WC_Gateway_Your_Gateway'; 
	return $methods;
}


add_action( 'plugins_loaded', 'Bpg_init_your_gateway_class' );

function Bpg_init_your_gateway_class(){

	load_plugin_textdomain('Bpg_bkash_textdomain', false, dirname( __FILE__).'/lang');

	class WC_Gateway_Your_Gateway extends WC_Payment_Gateway{

		public function __construct(){

			$this->id = 'nayon_bkash';
			$this->title = 'Bkash Payment Gateway';
			$this->description = 'it is easy payment method in Ecommerce site';
			$this->method_title = 'bkash';
			$this->method_description = 'bkash Payment Gateway Options';
			$this->icon = PLUGINS_URL('images/bkash-logo.png',__FILE__);
			$this->has_fields=true;


			$this->init_form_fields();
			$this->init_settings();

			
			$this->number_type = $this->get_option('number_type');
			$this->bkash_number = $this->get_option('bkash_number');
			

			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
			}





		public function init_form_fields(){

			$this->form_fields = array(
				'enabled' => array(
					'title' => __( 'Enable/Disable', 'woocommerce' ),
					'type' => 'checkbox',
					'label' => __( 'Enable bkash Payment', 'woocommerce' ),
					'default' => 'yes'
				),
				'title' => array(
					'title' => __( 'Title', 'woocommerce' ),
					'type' => 'text',
					'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
					'default' => __( 'Cheque Payment', 'woocommerce' ),
					'desc_tip'      => true,
				),
				'description' => array(
					'title' => __( 'Customer Message', 'woocommerce' ),
					'type' => 'textarea',
					'default' => ''
				),
				'bkash_number' => array(
					'title' => __( 'bkash number', 'woocommerce' ),
					'type' => 'text',
					'default' => ''
				),
				'number_type'=>array(
					'title'=>'select',
					'type'=>'select',
					'options'=>array(
						'personal'=>'personal',
						'agent'=>'agent'
					)
				)
			);
		}

		function process_payment( $order_id ) {
			global $woocommerce;
			$order = new WC_Order( $order_id );

			// Mark as on-hold (we're awaiting the cheque)
			$order->update_status('on-hold', __( 'Awaiting cheque payment', 'woocommerce' ));

			// Reduce stock levels
			$order->reduce_order_stock();

			// Remove cart
			$woocommerce->cart->empty_cart();

			// Return thankyou redirect
			return array(
				'result' => 'success',
				'redirect' => $this->get_return_url( $order )
			);
		}

		public function payment_fields(){

				echo wpautop( wptexturize( $this->description ) );
				echo wpautop( wptexturize( "bKash ".$this->number_type." Number : ".$this->bkash_number ) );
			?>
			<p>
				<label for="bkash_number">bKash Number</label>
				<input type="number" name="bkash_number" id="bkash_number" placeholder="017XXXXXXXX">
			</p>
			<p>
				<label for="bkash_amount">bKash amount</label>
				<input type="number" name="bkash_amount" id="bkash_amount" placeholder="10*****">
			</p>
			<p>
				<label for="bkash_transaction_id">bKash Transaction ID</label>
				<input type="number" name="bkash_transaction_id" id="bkash_transaction_id" placeholder="84756756749">
			</p>

			<?php
		}

	}



}
add_action('woocommerce_checkout_update_order_meta', 'Bpg_additional_bkash_fields_update');
function Bpg_additional_bkash_fields_update( $order_id ){



		$number = isset($_POST['bkash_number']) ? $_POST['bkash_number'] : '';
		$amount = isset($_POST['bkash_amount']) ? $_POST['bkash_amount'] : '';
		$transaction = isset( $_POST['bkash_transaction_id'] ) ? $_POST['bkash_transaction_id'] : '';

		update_post_meta($order_id, 'bkash_number', $number);
		update_post_meta($order_id, 'bkash_amount', $amount);
		update_post_meta($order_id, 'bkash_transaction', $transaction);

	

}







	
	add_action('woocommerce_admin_order_data_after_billing_address', 'Bpg_additional_order_meta_data');

function Bpg_additional_order_meta_data( $order ){

	$order_id = $order->id;

	$number = get_post_meta($order_id, 'bkash_number', true);
	$amount = get_post_meta($order_id, 'bkash_amount', true);
	$transaction = get_post_meta($order_id, 'bkash_transaction', true);

	?>
		<table border="1" class="shop_table customer_details">
			<tbody>
				<tr>
					<th>bKash Number:</th>
					<td><?php echo $number; ?></td>
				</tr>
			
				<tr>
					<th>Amount:</th>
					<td><?php echo $amount; ?></td>
				</tr>
				<tr>
					<th>Transaction ID:</th>
					<td><?php echo $transaction; ?></td>
				</tr>

			
			</tbody>
		</table>
	<?php 
}

add_action('woocommerce_order_details_after_customer_details', 'Bpg_bkash_additional_info_by_fields');

function Bpg_bkash_additional_info_by_fields( $order ){

	$order_id = $order->id;

		
	$number = get_post_meta($order_id, 'bkash_number', true);
	$amount = get_post_meta($order_id, 'bkash_amount', true);
	$transaction = get_post_meta($order_id, 'bkash_transaction', true);

	?>
		<tr>
			<th>bKash Number:</th>
			<td><?php echo $number; ?></td>
		</tr>
		<tr>
			<th>bKash Amount:</th>
			<td><?php echo $amount; ?></td>
		</tr>
		<tr>
			<th>bKash Transaction ID:</th>
			<td><?php echo $transaction; ?></td>
		</tr>

	<?php 
	
}