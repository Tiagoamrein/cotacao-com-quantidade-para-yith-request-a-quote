=== Cotação com Quantidade para YITH Request a Quote ===
Contributors: tiago-santos
Tags: woocommerce, yith request a quote, quote, quantity, product grid
Requires at least: 5.8
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Adiciona controle de quantidade e botão "Adicionar às cotações" nos grids de produtos do WooCommerce, integrando com YITH Request a Quote.

== Descrição ==

Este plugin substitui o botão padrão do WooCommerce no loop/grid de produtos simples por:

* um seletor de quantidade com - / +;
* um botão "Adicionar às cotações";
* atualização da quantidade caso o produto já esteja na lista de cotações;
* um link "Ver cotação" após a ação.

Produtos variáveis permanecem com o botão padrão, pois a variação precisa ser escolhida antes.

== Dependências ==

* WooCommerce ativo.
* YITH Request a Quote ativo.

== Instalação ==

1. Desative o snippet anterior usado no Code Snippets ou no functions.php.
2. Em WordPress > Plugins > Adicionar novo > Enviar plugin, envie o arquivo ZIP deste plugin.
3. Ative o plugin.
4. Limpe cache do site/CDN e teste em uma página de loja, categoria ou grid de produtos.

== Personalização ==

O CSS está em assets/css/frontend.css.
O botão pode ter o texto alterado com o filtro:

add_filter( 'yqql_add_to_quote_button_text', function( $text, $product ) {
    return 'Solicitar cotação';
}, 10, 2 );

== Changelog ==

= 1.0.1 =
* Nome do plugin atualizado.
* Autor definido como Tiago Santos.

= 1.0.0 =
* Primeira versão.
