=== Cotação com Quantidade para YITH Request a Quote ===
Contributors: tiago-santos
Tags: woocommerce, yith request a quote, quote, quantity, product grid
Requires at least: 5.8
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.0.0

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

Antes de instalar este plugin, desative e remova qualquer snippet anterior que tenha sido adicionado pelo Code Snippets ou pelo arquivo functions.php do tema. Isso evita que os botões de quantidade apareçam duplicados no grid de produtos.
No painel do WordPress, acesse Plugins > Adicionar novo > Enviar plugin.
Selecione o arquivo ZIP do plugin Cotação com Quantidade para YITH Request a Quote e clique em Instalar agora.
Após a instalação, clique em Ativar plugin.
Limpe o cache do site, do servidor e da CDN, caso estejam ativos.
Acesse uma página de loja, categoria ou grid de produtos e confirme se o seletor de quantidade − / + e o botão Adicionar às cotações estão sendo exibidos corretamente.
Faça um teste selecionando uma quantidade maior que 1 e confirme, na página de cotação do YITH, se o produto foi incluído com a quantidade escolhida.

== Personalização ==

O CSS está em assets/css/frontend.css.
O botão pode ter o texto alterado com o filtro:

add_filter( 'yqql_add_to_quote_button_text', function( $text, $product ) {
    return 'Solicitar cotação';
}, 10, 2 );
