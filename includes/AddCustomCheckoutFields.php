<?php

namespace WooCWP\Includes;

defined('ABSPATH') || exit;

class AddCustomCheckoutFields
{


    public static function addCustomCheckoutFields($checkout)
    {
        echo '<div id="campos_personalizados_checkout"><h3>' . __('Informações para Configuração da Hospedagem') . '</h3>';
        // Campo para o domínio
        woocommerce_form_field('billing_domain', array(
            'type'        => 'text',
            'class'       => array('form-row-wide'),
            'label'       => __('Domínio para Hospedagem'),
            'placeholder' => __('meudominio.com.br'),
            'required'    => true,
            'description' => __('Por favor, insira o nome do domínio que você deseja usar na hospedagem (exemplo: meudominio.com.br).'),
        ), $checkout->get_value('billing_domain'));
        echo '</div>';
    }


    public static function validateCustomFieldsCheckout($data, $errors)
    {
        if (empty($_POST['billing_domain'])) {
            $errors->add('billing_domain_erro', __('O campo "Domínio para Hospedagem" é obrigatório.'));
        }
    }
}
