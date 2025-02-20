# CWP-Woo

**Integração entre WooCommerce e CWP**  
Autor: Jadiael  
Versão: 1.0.0  
Licença: [GNU GPL v3](http://www.gnu.org/licenses/gpl-3.0.html)

---

## 📖 Índice

* [Introdução](#📝-introdução)
* [Funcionalidades](#🚀-funcionalidades)
* [Instalação](#🛠️-instalação)
* [Configuração](#⚙️-configuração)
* [Uso](#🖥️-uso)
* [Desafios Encontrados e Soluções](#🧩-desafios-encontrados-e-soluções)
  * [1. Problemas de Conexão com o CWP no Mesmo Servidor](#1-problemas-de-conexão-com-o-cwp-no-mesmo-servidor)
  * [2. Garantia de Execução Assíncrona e Resposta Rápida](#2-garantia-de-execução-assíncrona-e-resposta-rápida)
  * [3. Verificação do Status de Criação de Conta](#3-verificação-do-status-de-criação-de-conta)
  * [4. Alternativa com API Externa com Sistema de Filas](#4-alternativa-com-api-externa-e-com-sistema-de-filas)
  * [5. Segurança e Armazenamento das Credenciais](#5-segurança-e-armazenamento-das-credenciais)
* [Exemplos de Uso](#🧪-exemplos-de-uso)
* [Contribuição](#🤝-contribuição)
* [Licença](#📄-licença)
* [Considerações Finais](#🛡️-considerações-finais)

---

## 📝 Introdução

O **CWP-Woo** é um plugin para WordPress que integra o **WooCommerce** com o **CWP (CentOS Web Panel)**. Ele é voltado para lojas que vendem planos de hospedagem e utilizam o CWP como painel de controle. Quando um pedido é concluído no WooCommerce, o plugin automaticamente cria uma conta de hospedagem no CWP e exibe o status dessa criação no painel administrativo do WordPress.

---

## 🚀 Funcionalidades

* Criação automática de contas no CWP após pedidos concluídos no WooCommerce.
* Sistema robusto de verificação do status das contas (Sucesso, Erro, Aguardando).
* Execução assíncrona para não impactar o desempenho do site.
* Tela administrativa com visualização de contas e seus status.
* Configuração de credenciais CWP com dados criptografados no banco de dados.
* Opção de utilizar uma API externa para criação de contas (recomendado para maior confiabilidade).
* Logs de criação de conta salvos para verificação futura.
* Verificação e fallback automático para métodos de requisição disponíveis (cURL via exec, `wp_remote_post`).

---

## 🛠️ Instalação

1. Faça o download do plugin.
2. Extraia o conteúdo do arquivo zipado na pasta `wp-content/plugins/cwp-woo`.
3. Ative o plugin pelo painel do WordPress em **Plugins > cwp-woo**.
4. Acesse o menu administrativo do plugin para configurar as credenciais da API do CWP.
---

## ⚙️ Configuração

1. Acesse o menu **WooCWP** no painel administrativo do WordPress.
2. Preencha as seguintes informações na aba **Configurações**:
   * **URL da API do CWP:** Exemplo: `https://srv1.seudominio.com:2304/v1/`
   * **Token de Acesso:** Token de autenticação gerado no CWP.
   * **IP do Servidor CWP:** IP do servidor onde as contas serão criadas.
   * **URL da API Externa (opcional):** Para uso de uma API intermediária com sistema de filas.
3. As informações são salvas de forma criptografada para garantir segurança.

⚠️ **Importante:** Configure uma variável de ambiente chamada `CWP_WOO_KEY` no seu servidor com uma chave segura para que o plugin utilize essa chave como prioridade na criptografia dos dados. Caso não exista, uma chave padrão será usada como fallback.

---

## 🖥️ Uso

* Após configurar o plugin e realizar um pedido de hospedagem no WooCommerce:
  * Ao marcar o pedido como **Concluído**, o plugin iniciará a criação da conta no CWP.
  * A criação é realizada de forma assíncrona via **WP-CRON** para evitar bloqueios.
  * O status da conta pode ser consultado no menu administrativo do plugin.

* **Status possíveis:**
  * 🟢 **Sucesso:** Conta criada com sucesso no CWP.
  * 🟡 **Aguardando:** Processo de criação em andamento.
  * 🔴 **Erro:** Falha na criação da conta.

⚠️ Como o processo utiliza WP-CRON, é necessário que o site receba visitas para que as tarefas sejam processadas. Para maior confiabilidade, recomenda-se configurar um cron job no servidor.

---

## 🧩 Desafios Encontrados e Soluções

### 1. Problemas de Conexão com o CWP no Mesmo Servidor

**Desafio:**  
Se o WordPress estiver hospedado no servidor CWP em que as contas serão criadas, ao enviar a requisição para a criação da conta, o CWP realiza um **reload em alguns serviços**, o que **mata a requisição HTTP do plugin** e retorna um erro `503 Service Unavailable`.

Mesmo assim, a conta é criada no CWP, pois a API do CWP roda sob um webserver com configurações distintas.

**Solução:**

* A criação de contas foi delegada ao **WP-CRON**, utilizando a rotina `processSharesAfterPayment` em `includes/ProcessSharesAfterPayment.php`.
* Isso garante que as requisições sejam disparadas em momentos diferentes, evitando que o reload do CWP interrompa a execução.

---

### 2. Garantia de Execução Assíncrona e Resposta Rápida

**Desafio:**  
Mesmo com o `wp_remote_post` no modo não bloqueante, poderia ocorrer `503` devido estar hospedado no mesmo CWP

**Solução:**

* Antes da requisição, a função `fastcgi_finish_request()` é chamada (se disponível), encerrando a conexão com o navegador e permitindo que o script continue rodando em segundo plano.
* Isso assegura que a requisição ao CWP não interfira nas requisições do WordPress.

---

### 3. Verificação do Status de Criação de Conta

**Desafio:**  
Identificar com precisão se uma conta foi criada, já que a requisição pode falhar em receber resposta.

**Solução:**

* Se `exec` e `curl` estiverem disponíveis (Que é o cenario mais recomendado, sem o uso de API externa):
  * É utilizado `exec` com `curl` para realizar a requisição.
  * O **output do curl é salvo em um arquivo de log para conferencia do status de criação**.  
    <small>Essa é uma das formas usadas de verificar se a conta foi criada ou não.</small>
* Caso `exec` ou `curl` não estejam disponíveis:
  * Utiliza-se `wp_remote_post` como fallback.
* Ao listar contas:
  * Primeiro verifica-se se existe o status já salvo no banco de dados.
  * Segundo verifica-se se existe o status no arquivo de log. (<small>Caso tenha sido criado com exec/curl</small>)
  * Se não houver, consulta-se diretamente a API do CWP.
  * Os resultados são armazenados no banco de dados para consultas futuras.

---

### 4. Alternativa com API Externa e com Sistema de Filas

**Desafio:**  
Evitar os problemas de requisição direta para o CWP, especialmente quando seu wordpress é hospedado pelo mesmo CWP.

**Solução:**

* O plugin permite configurar uma **API externa** no menu administrativo.
* Toda requisição de criação de conta passa por essa API, que pode:
  * Retornar uma resposta rápida para o WordPress.
  * Gerenciar as requisições em uma **fila de processamento**.
* Isso é **altamente recomendado**, especialmente se essa API estiver em um ambiente diferente do servidor CWP. Ou que não sofra com os reload feito na hora da criação de uma conta pela API do CWP.

---

### 5. Segurança e Armazenamento das Credenciais

**Desafio:**  
Proteger as credenciais da API CWP armazenadas no banco de dados.

**Solução:**

* Todas as credenciais são **criptografadas** antes de serem salvas.
* **Prioridade de segurança:**
  * O plugin procura pela variável de ambiente `CWP_WOO_KEY`.
  * Se não existir, utiliza uma chave de fallback (menos seguro).
* Recomenda-se sempre definir a variável de ambiente para **máxima segurança**.

---

## 🧪 Exemplos de Uso

1. O cliente compra um plano de hospedagem no WooCommerce.
2. O pedido é concluído manualmente pelo painel administrado ou pago pelo cliente.
3. O WP-CRON do plugin agenda a criação da conta.
4. O administrador pode consultar o status em **CWPWoo > Geral**:
   * 🟢 Sucesso: Conta criada.
   * 🟡 Aguardando: Em processamento.
   * 🔴 Erro: Problemas na criação (consulte os logs).

---

## 🤝 Contribuição

1. Faça um fork do repositório.
2. Crie uma branch com a sua feature: `git checkout -b feature/nova-feature`
3. Commit suas alterações: `git commit -m 'Adiciona nova feature'`
4. Push para a branch: `git push origin feature/nova-feature`
5. Abra um Pull Request.

---

## 📄 Licença

Este projeto está licenciado sob a [GNU General Public License v3](http://www.gnu.org/licenses/gpl-3.0.html).

---

## 🛡️ Considerações Finais

Apesar dos desafios técnicos enfrentados, o plugin funciona de forma confiável e é ideal para quem vende hospedagens com WooCommerce e utiliza o CWP. Se seu servidor onde seu Wordpress estiver hospedado não disponibilizar da função exec ou do curl do sistema, A configuração com uma API externa e um sistema de filas é a melhor prática para garantir estabilidade e desempenho.
<br>Embora a chance é alta que tudo funcione bem, mesmo sem a disponibilidade da função exec e do curl do sistema. Mais em casos que não tiver esses 2, e tiver tendo problemas, recomendo optar por uso da API externa. 

💡 **Importante:**

* Garanta que seu site tenha visitas regulares para que o WP-CRON seja acionado.
* Utilize uma chave de criptografia via `CWP_WOO_KEY` para maior segurança.

---

🔒 *Segurança, confiabilidade e integração simplificada.*

---

Desenvolvido por **Jadiael**.