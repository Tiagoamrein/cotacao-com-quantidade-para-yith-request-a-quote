<?php
/**
 * Plugin Name: Cotação com Quantidade para YITH Request a Quote
 *  * Description: Adiciona seletor de quantidade e botão "Adicionar às cotações" nos grids de produtos do WooCommerce usando YITH Request a Quote.
 * Version:     1.0.1
 * Author:      Tiago Santos
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: yith-quote-quantity-loop
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 6.0
 * WC tested up to: 9.0
 */
add_action(
	'before_woocommerce_init',
	static function() {
		if ( class_exists( '\\Automattic\\WooCommerce\\Utilities\\FeaturesUtil' ) ) {

			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
				'custom_order_tables',
				__FILE__,
				true
			);

			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
				'cart_checkout_blocks',
				__FILE__,
				true
			);
		}
	}
);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class YQQL_YITH_Quote_Quantity_Loop {

	const VERSION = '1.0.1';
	const SLUG    = 'yith-quote-quantity-loop';

	/**
	 * Instância única.
	 *
	 * @var YQQL_YITH_Quote_Quantity_Loop|null
	 */
	private static $instance = null;

	/**
	 * Retorna a instância principal do plugin.
	 *
	 * @return YQQL_YITH_Quote_Quantity_Loop
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Construtor privado.
	 */
	private function __construct() {
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ), 20 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );

		add_filter( 'woocommerce_loop_add_to_cart_link', array( $this, 'render_loop_quote_button' ), 99999, 3 );

		add_action( 'wp_ajax_yqql_add_to_quote', array( $this, 'ajax_add_to_quote' ) );
		add_action( 'wp_ajax_nopriv_yqql_add_to_quote', array( $this, 'ajax_add_to_quote' ) );

		add_action( 'admin_notices', array( $this, 'dependency_notice' ) );
	}

	/**
	 * Carrega as traduções do plugin.
	 *
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'yith-quote-quantity-loop',
			false,
			dirname( plugin_basename( __FILE__ ) ) . '/languages'
		);
	}

	/**
	 * Verifica as dependências necessárias.
	 *
	 * @return bool
	 */
	private function dependencies_available() {
		return class_exists( 'WooCommerce' ) && function_exists( 'YITH_Request_Quote' );
	}

	/**
	 * Exibe aviso no admin quando WooCommerce/YITH não estão ativos.
	 *
	 * @return void
	 */
	public function dependency_notice() {
		if ( ! current_user_can( 'activate_plugins' ) || $this->dependencies_available() ) {
			return;
		}

		printf(
			'<div class="notice notice-warning"><p>%s</p></div>',
			esc_html__( 'Cotação com Quantidade para YITH Request a Quote precisa do WooCommerce e do YITH Request a Quote ativos para funcionar.', 'yith-quote-quantity-loop' )
		);
	}

	/**
	 * Carrega CSS e JavaScript no front-end.
	 *
	 * Os assets são pequenos e carregados em todo o front-end para suportar grids
	 * WooCommerce inseridos por shortcode, Elementor, widgets e home page.
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		if ( is_admin() || ! $this->dependencies_available() ) {
			return;
		}

		$base_url = plugin_dir_url( __FILE__ );

		wp_enqueue_style(
			'yqql-frontend',
			$base_url . 'assets/css/frontend.css',
			array(),
			self::VERSION
		);

		wp_enqueue_script(
			'yqql-frontend',
			$base_url . 'assets/js/frontend.js',
			array( 'jquery' ),
			self::VERSION,
			true
		);

		wp_localize_script(
			'yqql-frontend',
			'yqqlData',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'i18n'    => array(
					'adding'       => __( 'Adicionando...', 'yith-quote-quantity-loop' ),
					'genericError' => __( 'Não foi possível adicionar este produto à cotação.', 'yith-quote-quantity-loop' ),
					'networkError' => __( 'Erro de conexão. Tente novamente.', 'yith-quote-quantity-loop' ),
					'viewQuote'    => __( 'Ver cotação', 'yith-quote-quantity-loop' ),
					'updateQuote'  => __( 'Atualizar cotação', 'yith-quote-quantity-loop' ),
				),
			)
		);
	}

	/**
	 * Troca o botão padrão do WooCommerce no loop por seletor + botão de cotação.
	 *
	 * Produtos variáveis e demais tipos mantêm o botão padrão, pois precisam da
	 * seleção da variação antes de ir para a cotação.
	 *
	 * @param string     $html    Botão HTML original.
	 * @param WC_Product $product Produto do loop.
	 * @param array      $args    Argumentos do WooCommerce.
	 * @return string
	 */
	public function render_loop_quote_button( $html, $product, $args ) {
		if ( ! $this->dependencies_available() || ! $product instanceof WC_Product ) {
			return $html;
		}

		if ( ! $product->is_type( 'simple' ) ) {
			return $html;
		}

		$product_id = $product->get_id();
		$min        = max( 1, (int) $product->get_min_purchase_quantity() );
		$max        = (int) $product->get_max_purchase_quantity();

		if ( $product->is_sold_individually() ) {
			$min = 1;
			$max = 1;
		}

		if ( $max > 0 && $max < $min ) {
			$max = $min;
		}

		$max_attribute  = $max > 0 ? ' max="' . esc_attr( $max ) . '"' : '';
		$minus_disabled = ' disabled="disabled"';
		$plus_disabled  = ( $max > 0 && $min >= $max ) ? ' disabled="disabled"' : '';
		$readonly       = ( $max > 0 && $min === $max ) ? ' readonly="readonly"' : '';
		$button_label   = apply_filters(
			'yqql_add_to_quote_button_text',
			__( 'Adicionar às cotações', 'yith-quote-quantity-loop' ),
			$product
		);

		ob_start();
		?>
		<div
			class="yqql-loop"
			data-product-id="<?php echo esc_attr( $product_id ); ?>"
			data-nonce="<?php echo esc_attr( wp_create_nonce( 'yqql_add_to_quote' ) ); ?>"
			data-min="<?php echo esc_attr( $min ); ?>"
			data-max="<?php echo esc_attr( $max > 0 ? $max : 0 ); ?>"
		>
			<div class="yqql-quantity" role="group" aria-label="<?php esc_attr_e( 'Quantidade', 'yith-quote-quantity-loop' ); ?>">
				<span class="yqql-quantity__label"><?php esc_html_e( 'Quantidade:', 'yith-quote-quantity-loop' ); ?></span>

				<button
					type="button"
					class="yqql-quantity__button yqql-quantity__button--minus"
					aria-label="<?php esc_attr_e( 'Diminuir quantidade', 'yith-quote-quantity-loop' ); ?>"
					<?php echo $minus_disabled; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				>
					−
				</button>

				<input
					type="number"
					class="yqql-quantity__input"
					value="<?php echo esc_attr( $min ); ?>"
					min="<?php echo esc_attr( $min ); ?>"
					<?php echo $max_attribute; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					step="1"
					inputmode="numeric"
					aria-label="<?php esc_attr_e( 'Quantidade', 'yith-quote-quantity-loop' ); ?>"
					<?php echo $readonly; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				/>

				<button
					type="button"
					class="yqql-quantity__button yqql-quantity__button--plus"
					aria-label="<?php esc_attr_e( 'Aumentar quantidade', 'yith-quote-quantity-loop' ); ?>"
					<?php echo $plus_disabled; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				>
					+
				</button>
			</div>

			<button type="button" class="button yqql-add-to-quote">
				<?php echo esc_html( $button_label ); ?>
			</button>

			<div class="yqql-status" role="status" aria-live="polite"></div>
		</div>
		<?php

		return (string) ob_get_clean();
	}

	/**
	 * Adiciona ou atualiza um produto na lista YITH via AJAX.
	 *
	 * @return void
	 */
	public function ajax_add_to_quote() {
		$product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
		$nonce      = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';

		if ( ! $product_id || ! wp_verify_nonce( $nonce, 'yqql_add_to_quote' ) ) {
			wp_send_json_error(
				array( 'message' => __( 'Não foi possível validar a solicitação.', 'yith-quote-quantity-loop' ) ),
				403
			);
		}

		if ( ! $this->dependencies_available() ) {
			wp_send_json_error(
				array( 'message' => __( 'WooCommerce ou YITH Request a Quote não estão ativos.', 'yith-quote-quantity-loop' ) ),
				500
			);
		}

		$product = wc_get_product( $product_id );

		if ( ! $product || ! $product->is_type( 'simple' ) ) {
			wp_send_json_error(
				array( 'message' => __( 'Este produto precisa ser selecionado pela página individual.', 'yith-quote-quantity-loop' ) ),
				400
			);
		}

		$quantity = isset( $_POST['quantity'] ) ? absint( $_POST['quantity'] ) : 1;
		$min      = max( 1, (int) $product->get_min_purchase_quantity() );
		$max      = (int) $product->get_max_purchase_quantity();

		if ( $product->is_sold_individually() ) {
			$min = 1;
			$max = 1;
		}

		$quantity = max( $min, $quantity );

		if ( $max > 0 ) {
			$quantity = min( $quantity, $max );
		}

		$quote = YITH_Request_Quote();

		if ( ! is_object( $quote ) || ! method_exists( $quote, 'add_item' ) ) {
			wp_send_json_error(
				array( 'message' => __( 'Não foi possível acessar a lista de cotações do YITH.', 'yith-quote-quantity-loop' ) ),
				500
			);
		}

		$items = array();

		if ( method_exists( $quote, 'get_raq_return' ) ) {
			$items = (array) $quote->get_raq_return();
		}

		$item_key = md5( (string) $product_id );
		$updated  = false;

		if ( isset( $items[ $item_key ] ) && method_exists( $quote, 'update_item' ) ) {
			$updated = (bool) $quote->update_item( $item_key, 'quantity', $quantity );
		}

		if ( ! $updated ) {
			$result = $quote->add_item(
				array(
					'product_id' => $product_id,
					'quantity'   => $quantity,
				)
			);

			if ( false === $result || null === $result ) {
				wp_send_json_error(
					array( 'message' => __( 'Não foi possível adicionar este item à cotação.', 'yith-quote-quantity-loop' ) ),
					400
				);
			}
		}

		$quote_url = '';

		if ( method_exists( $quote, 'get_raq_page_url' ) ) {
			$quote_url = $quote->get_raq_page_url();
		}

		if ( empty( $quote_url ) ) {
			$quote_page_id = absint( get_option( 'ywraq_page_id' ) );
			$quote_url     = $quote_page_id ? get_permalink( $quote_page_id ) : home_url( '/' );
		}

		wp_send_json_success(
			array(
				'message'   => $updated
					? sprintf( __( 'Quantidade atualizada para %d.', 'yith-quote-quantity-loop' ), $quantity )
					: sprintf( __( '%d unidade(s) adicionada(s) à cotação.', 'yith-quote-quantity-loop' ), $quantity ),
				'quote_url' => esc_url_raw( $quote_url ),
			)
		);
	}
}

/**
 * Inicializa o plugin.
 *
 * @return YQQL_YITH_Quote_Quantity_Loop
 */
function yqql() {
	return YQQL_YITH_Quote_Quantity_Loop::instance();
}

yqql();
