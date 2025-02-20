<h1>CWP-Woo</h1>
<p>
    <strong>Integração entre WooCommerce e CWP</strong>
    <br>
    Autor: Jadiael
    <br>
    Versão: 1.0.0
    <br>
    Licença: <a rel="noopener" target="_new" href="http://www.gnu.org/licenses/gpl-2.0.html">GNU GPL v2</a>
</p>
<hr>
<h2>📖 Índice</h2>
<ul>
    <li>
        <a rel="noopener" href="#introdu%C3%A7%C3%A3o">Introdução</a>
    </li>
    <li>
        <a rel="noopener" href="#funcionalidades">Funcionalidades</a>
    </li>
    <li>
        <a rel="noopener" href="#instala%C3%A7%C3%A3o">Instalação</a>
    </li>
    <li>
        <a rel="noopener" href="#configura%C3%A7%C3%A3o">Configuração</a>
    </li>
    <li>
        <a rel="noopener" href="#uso">Uso</a>
    </li>
    <li>
        <a rel="noopener" href="#desafios-encontrados-e-solu%C3%A7%C3%B5es">Desafios Encontrados e Soluções</a>
        <ul>
            <li>
                <a rel="noopener" href="#1-problemas-de-conex%C3%A3o-com-o-cwp-no-mesmo-servidor">1. Problemas de
                    Conexão com o
                    CWP no Mesmo Servidor</a>
            </li>
            <li>
                <a rel="noopener" href="#2-garantia-de-execu%C3%A7%C3%A3o-ass%C3%ADncrona-e-resposta-r%C3%A1pida">2.
                    Garantia de
                    Execução Assíncrona e Resposta Rápida</a>
            </li>
            <li>
                <a rel="noopener" href="#3-verifica%C3%A7%C3%A3o-do-status-de-cria%C3%A7%C3%A3o-de-conta">3. Verificação
                    do Status
                    de Criação de Conta</a>
            </li>
            <li>
                <a rel="noopener" href="#4-alternativa-com-api-externa-com-sistema-de-filas">4. Alternativa com API
                    Externa com
                    Sistema de Filas</a>
            </li>
            <li>
                <a rel="noopener" href="#5-seguran%C3%A7a-e-armazenamento-das-credenciais">5. Segurança e Armazenamento
                    das
                    Credenciais</a>
            </li>
        </ul>
    </li>
    <li>
        <a rel="noopener" href="#exemplos-de-uso">Exemplos de Uso</a>
    </li>
    <li>
        <a rel="noopener" href="#contribui%C3%A7%C3%A3o">Contribuição</a>
    </li>
    <li>
        <a rel="noopener" href="#licen%C3%A7a">Licença</a>
    </li>
</ul>
<hr>
<h2 id="introdu%C3%A7%C3%A3o">📝 Introdução</h2>
<p>
    O <strong>CWP-Woo</strong> é um plugin para
    WordPress que integra o <strong>WooCommerce</strong> com o <strong>CWP (CentOS Web Panel)</strong>. Ele é voltado
    para lojas que vendem
    planos de hospedagem e utilizam o CWP como painel de controle. Quando um pedido é concluído no WooCommerce, o
    plugin automaticamente cria uma conta de hospedagem no CWP e exibe o status dessa criação no painel
    administrativo do WordPress.
</p>
<hr>
<h2 id="funcionalidades">🚀 Funcionalidades</h2>
<ul>
    <li>
        Criação automática de contas no CWP após pedidos concluídos no WooCommerce.
    </li>
    <li>
        Sistema robusto de verificação do status das contas (Sucesso, Erro, Aguardando).
    </li>
    <li>Execução assíncrona para não impactar o desempenho do site.</li>
    <li>Tela administrativa com visualização de contas e seus status.</li>
    <li>
        Configuração de credenciais CWP com dados criptografados no banco de dados.
    </li>
    <li>
        Opção de utilizar uma API externa para criação de contas (recomendado para maior confiabilidade).
    </li>
    <li>Logs de criação de conta salvos para verificação futura.</li>
    <li>
        Verificação e fallback automático para métodos de requisição disponíveis (cURL via exec,
        <code>wp_remote_post</code>).
    </li>
</ul>
<hr>
<h2 id="instala%C3%A7%C3%A3o">🛠️ Instalação</h2>
<ol>
    <li>Faça o download do plugin.</li>
    <li>Extraia o conteúdo do arquivo zipado na pasta
        <code>wp-content/plugins/cwp-woo</code>.
    </li>
    <li>
        Ative o plugin pelo painel do WordPress em
        <strong>Plugins &gt; cwp-woo</strong>.
    </li>
    <li>
        Acesse o menu administrativo do plugin para configurar as credenciais da API do CWP.
    </li>
</ol>
<hr>
<h2 id="configura%C3%A7%C3%A3o">⚙️ Configuração</h2>
<ol>
    <li>
        Acesse o menu <strong>WooCWP</strong> no
        painel administrativo do WordPress.
    </li>
    <li>Preencha as seguintes informações na aba <strong>Configurações</strong>:
        <ul>
            <li>
                <strong>URL da API do CWP:</strong>
                Exemplo: <code>https://srv1.seudominio.com:2304/v1/</code>
            </li>
            <li>
                <strong>Token de Acesso:</strong> Token de autenticação gerado no CWP.
            </li>
            <li>
                <strong>IP do Servidor CWP:</strong> IP do servidor onde as contas
                serão criadas.
            </li>
            <li>
                <strong>URL da API Externa (opcional):</strong> Para uso de uma API
                intermediária com sistema de filas.
            </li>
        </ul>
    </li>
    <li>As informações são salvas de forma criptografada para garantir segurança.
    </li>
</ol>
<p>
    ⚠️ <strong>Importante:</strong> Configure uma
    variável de ambiente chamada <code>CWP_WOO_KEY</code> no seu servidor com uma
    chave segura para que o plugin utilize essa chave como prioridade na criptografia dos dados. Caso não exista,
    uma chave padrão será usada como fallback.
</p>
<hr>
<h2 id="uso">🖥️ Uso</h2>
<ul>
    <li>
        <p>
            Após configurar o plugin e realizar um pedido de hospedagem no WooCommerce:
        </p>
        <ul>
            <li>Ao marcar o pedido como <strong>Concluído</strong>, o plugin iniciará a criação da conta no CWP.</li>
            <li>
                A criação é realizada de forma assíncrona via <strong>WP-CRON</strong> para evitar bloqueios.
            </li>
            <li>O status da conta pode ser consultado no menu administrativo do plugin.</li>
        </ul>
    </li>
    <li>
        <p><strong>Status possíveis:</strong>
        </p>
        <ul>
            <li>
                🟢 <strong>Sucesso:</strong> Conta criada com sucesso no CWP.
            </li>
            <li>
                🟡 <strong>Aguardando:</strong>Processo de criação em andamento.
            </li>
            <li>
                🔴 <strong>Erro:</strong> Falha na criação da conta.
            </li>
        </ul>
    </li>
</ul>
<p>
    ⚠️ Como o processo utiliza WP-CRON, é necessário que o site receba visitas para que as tarefas sejam processadas.
    Para maior confiabilidade, recomenda-se configurar um cron job no servidor.
</p>
<hr>
<h2 id="desafios-encontrados-e-solu%C3%A7%C3%B5es">🧩 Desafios Encontrados e Soluções</h2>
<h3 id="1-problemas-de-conex%C3%A3o-com-o-cwp-no-mesmo-servidor">1. Problemas de Conexão com o CWP no Mesmo Servidor</h3>
<p>
    <strong>Desafio:</strong>
    <br>
    Se o WordPress estiver hospedado no mesmo servidor CWP em que as contas
    serão criadas, ao enviar a requisição para a criação da conta, o CWP realiza um
    <strong>reload em alguns serviços</strong>, o que <strong>mata a requisição HTTP do cliente</strong> e retorna um
    erro <code>503 Service Unavailable</code>.
</p>
<p>Mesmo assim, a conta é criada, pois a API do CWP roda sob um webserver com configurações distintas.</p>
<p><strong>Solução:</strong></p>
<ul>
    <li>
        A criação de contas foi delegada ao <strong>WP-CRON</strong>, utilizando a rotina
        <code>processSharesAfterPayment</code> em <code>includes/ProcessSharesAfterPayment.php</code>.
    </li>
    <li>
        Isso garante que as requisições sejam disparadas em momentos diferentes, evitando que o reload do CWP interrompa
        a execução.
    </li>
</ul>
<hr>
<h3 id="2-garantia-de-execu%C3%A7%C3%A3o-ass%C3%ADncrona-e-resposta-r%C3%A1pida">2. Garantia de Execução Assíncrona e Resposta Rápida</h3>
<p>
    <strong>Desafio:</strong>
    <br>
    Mesmo com o <code>wp_remote_post</code> no modo não bloqueante, poderia ocorrer <code>503</code> devido estar
    hospedado no mesmo CWP
</p>
<p><strong>Solução:</strong></p>
<ul>
    <li>
        Antes da requisição, a função <code>fastcgi_finish_request()</code> é chamada (se disponível), encerrando a
        conexão com o navegador e permitindo que o script continue rodando em segundo plano.
    </li>
    <li>Isso assegura que a requisição ao CWP não interfira nas requisições do WordPress.</li>
</ul>
<hr>
<h3 id="3-verifica%C3%A7%C3%A3o-do-status-de-cria%C3%A7%C3%A3o-de-conta">3. Verificação do Status de Criação de Conta</h3>
<p>
    <strong>Desafio:</strong>
    <br>
    Identificar com precisão se uma conta foi criada, já que a requisição pode
    falhar em receber resposta.
</p>
<p><strong>Solução:</strong></p>
<ul>
    <li>
        Se <code>exec</code> e <code>curl</code> estiverem disponíveis (Que é o cenario mais recomendado, sem o uso de
        API externa):
        <ul>
            <li>É utilizado <code>exec</code>
                com <code>curl</code> para realizar a requisição.
            </li>
            <li>
                O <strong>output do curl é salvo em um arquivo de log para conferencia do status de criação</strong>.
                <br><small>Essa é uma forma de verificar se a conta foi criada ou não.</small>
            </li>
        </ul>
    </li>
    <li>Caso <code>exec</code> ou <code>curl</code> não estejam disponíveis:<ul>
            <li>Utiliza-se <code>wp_remote_post</code> como fallback.</li>
        </ul>
    </li>
    <li>Ao listar contas:<ul>
            <li>Primeiro verifica-se se existe o status já salvo no banco de dados.</li>
            <li>
                Segundo verifica-se se existe o status no arquivo de log. (<small>Caso tenha sido criado
                    comexec/curl</small>)
            </li>
            <li>Se não houver, consulta-se diretamente a API do CWP.</li>
            <li>Os resultados são armazenados no banco de dados para consultas
                futuras.</li>
        </ul>
    </li>
</ul>
<hr>
<h3 id="4-alternativa-com-api-externa-com-sistema-de-filas">4. Alternativa com API Externa e com Sistema de Filas</h3>
<p>
    <strong>Desafio:</strong>
    <br>
    Evitar os problemas de requisição direta para o CWP, especialmente quando hospedados no mesmo ambiente.
</p>
<p><strong>Solução:</strong></p>
<ul>
    <li>O plugin permite configurar uma <strong>API externa</strong> no menu administrativo.</li>
    <li>
        Toda requisição de criação de conta passa por essa API, que pode:
        <ul>
            <li>Retornar uma resposta rápida para o WordPress.</li>
            <li>Gerenciar as requisições em uma <strong>fila de processamento</strong>.</li>
        </ul>
    </li>
    <li>
        Isso é <strong>altamente recomendado</strong>, especialmente se essa API estiver em um ambiente diferente do
        servidor CWP. Ou que não sofra com os reload feito na hora da criação de uma conta pela API do CWP.
    </li>
</ul>
<hr>
<h3 id="5-seguran%C3%A7a-e-armazenamento-das-credenciais">5. Segurança e Armazenamento das Credenciais</h3>
<p><strong>Desafio:</strong><br>Proteger as credenciais da API CWP armazenadas no banco de dados.</p>
<p><strong>Solução:</strong></p>
<ul>
    <li>Todas as credenciais são <strong>criptografadas</strong> antes de serem salvas.</li>
    <li><strong>Prioridade de
            segurança:</strong>
        <ul>
            <li>O plugin procura pela variável de ambiente <code>CWP_WOO_KEY</code>.</li>
            <li>Se não existir, utiliza uma chave de fallback (menos seguro).</li>
        </ul>
    </li>
    <li>Recomenda-se sempre definir a variável de ambiente para <strong>máxima segurança</strong>.</li>
</ul>
<hr>
<h2 id="exemplos-de-uso">🧪 Exemplos de Uso</h2>
<ol>
    <li>O cliente compra um plano de hospedagem no WooCommerce.</li>
    <li>O pedido é concluído manualmente pelo painel administrado ou pago pelo cliente.</li>
    <li>O WP-CRON do plugin agenda a criação da conta.</li>
    <li>
        O administrador pode consultar o status em <strong>WooCWP &gt; Geral</strong>:
        <ul>
            <li>🟢 Sucesso: Conta criada.</li>
            <li>🟡 Aguardando: Em processamento.</li>
            <li>🔴 Erro: Problemas na criação (consulte os logs).</li>
        </ul>
    </li>
</ol>
<hr>
<h2 id="contribui%C3%A7%C3%A3o">🤝 Contribuição</h2>
<ol>
    <li>Faça um fork do repositório.</li>
    <li>Crie uma branch com a sua feature: <code>git checkout -b feature/nova-feature</code></li>
    <li>Commit suas alterações: <code>git commit -m 'Adiciona nova feature'</code></li>
    <li>Push para a branch: <code>git push origin feature/nova-feature</code></li>
    <li>Abra um Pull Request.</li>
</ol>
<hr>
<h2 id="licen%C3%A7a">📄 Licença</h2>
<p>
    Este projeto está licenciado sob a
    <a rel="noopener" target="_new" href="http://www.gnu.org/licenses/gpl-2.0.html">GNU General Public License v2</a>.
</p>
<hr>
<h2>🛡️ Considerações Finais</h2>
<p>
    Apesar dos desafios técnicos enfrentados, o plugin funciona de forma confiável e é ideal para quem vende hospedagens
    com WooCommerce e utiliza o CWP. A configuração com uma API externa e um sistema de filas é a melhor prática para
    garantir estabilidade e desempenho.
</p>
<p>💡 <strong>Importante:</strong></p>
<ul>
    <li>
        Garanta que seu site tenha visitas regulares para que o WP-CRON seja acionado.
    </li>
    <li>Utilize uma chave de criptografia via <code>CWP_WOO_KEY</code> para maior segurança.</li>
    <li>
        Para ambientes com CWP no mesmo servidor do WordPress, considere utilizar a opção de API externa.
    </li>
</ul>
<hr>
<p>
    🔒 <em>Segurança, confiabilidade e integração simplificada.</em>
</p>
<hr>
<p>Desenvolvido por <strong>Jadiael</strong>.</p>