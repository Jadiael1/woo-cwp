<?php

namespace WooCWP\Includes;

defined('ABSPATH') || exit;

class AddCustomCheckoutFields
{


    public static function addCustomCheckoutFields($checkout)
    {
        // Obtém o carrinho
        $cart = WC()->cart->get_cart();
        $hasPlanItems = false;

        echo '<div id="campos_personalizados_checkout"><h3>' . __('Informações para Configuração das Hospedagens') . '</h3>';

        foreach ($cart as $cart_item_key => $cart_item) {
            $product = $cart_item['data'];
            $product_name = $product->get_name();

            // Verifica se o produto termina com -plan
            if (substr($product_name, -4) === 'Plan') {
                $hasPlanItems = true;
                $quantity = $cart_item['quantity'];

                // Adiciona campos baseados na quantidade do item
                for ($i = 0; $i < $quantity; $i++) {
                    $field_id = 'billing_domain_' . $cart_item_key . '_' . $i;

                    echo '<div class="domain-field-container">';
                    echo '<h4>' . sprintf(
                        __('Domínio para %s (item %d de %d)'),
                        $product_name,
                        $i + 1,
                        $quantity
                    ) . '</h4>';

                    woocommerce_form_field($field_id, array(
                        'type'        => 'text',
                        'class'       => array('form-row-wide', 'plan-domain-field'),
                        'label'       => __('Domínio para Hospedagem'),
                        'placeholder' => __('meudominio.com.br'),
                        'required'    => true,
                        'description' => __('Por favor, insira o nome do domínio que você deseja usar na hospedagem (exemplo: meudominio.com.br).'),
                    ), $checkout->get_value($field_id));
                    echo '</div>';
                }
            }
        }

        if (!$hasPlanItems) {
            echo '<p>' . __('Nenhum plano de hospedagem encontrado no carrinho.') . '</p>';
        }

        echo '</div>';
    }


    public static function validateCustomFieldsCheckout($data, $errors)
    {
        $cart = WC()->cart->get_cart();

        foreach ($cart as $cart_item_key => $cart_item) {
            $product = $cart_item['data'];
            $product_name = $product->get_name();

            if (substr($product_name, -4) === 'Plan') {
                $quantity = $cart_item['quantity'];

                for ($i = 0; $i < $quantity; $i++) {
                    $field_id = 'billing_domain_' . $cart_item_key . '_' . $i;

                    if (empty($_POST[$field_id])) {
                        $errors->add(
                            $field_id . '_error',
                            sprintf(
                                __('O campo "Domínio para %s (item %d de %d)" é obrigatório.'),
                                $product_name,
                                $i + 1,
                                $quantity
                            )
                        );
                    }
                }
            }
        }
    }

    public static function checkoutUpdateOrderMeta($order_id)
    {
        $domains_data = array();
        $cart = WC()->cart->get_cart();

        foreach ($cart as $cart_item_key => $cart_item) {
            $product = $cart_item['data'];
            $product_id = $product->get_id();
            $product_name = $product->get_name();

            if (substr($product_name, -4) === 'Plan') {
                $quantity = $cart_item['quantity'];
                $product_domains = array();

                for ($i = 0; $i < $quantity; $i++) {
                    $field_id = 'billing_domain_' . $cart_item_key . '_' . $i;

                    if (!empty($_POST[$field_id])) {
                        $product_domains[] = array(
                            'product_id' => $product_id,
                            'product_name' => $product_name,
                            'domain' => sanitize_text_field($_POST[$field_id]),
                            'item_number' => $i + 1
                        );
                    }
                }

                if (!empty($product_domains)) {
                    $domains_data[$cart_item_key] = $product_domains;
                }
            }
        }

        if (!empty($domains_data)) {
            update_post_meta($order_id, '_billing_domains', serialize($domains_data));
        }
    }
}
