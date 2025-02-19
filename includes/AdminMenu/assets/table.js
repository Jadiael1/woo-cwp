fetchAndRenderOrders(1, 10);

async function fetchAndRenderOrders(page = 1, perPage = 10) {
    try {
        const headers = new Headers();
        headers.append('Authorization', cwpWooData.token);
        headers.append('Accept', 'application/json; charset=utf-8');
        headers.append('Content-Type', 'application/json; charset=utf-8');
        const response = await fetch(`${cwpWooData.url}?page=${page}&per_page=${perPage}`, {
            headers: headers,
        });
        if (!response.ok) throw new Error("Erro ao buscar os pedidos.");

        const data = await response.json();
        renderStatistics(data);
        renderTable(data.data);
        renderPagination(data);
    } catch (error) {
        console.error("Erro ao obter pedidos:", error);
        document.getElementById("orders-container").innerHTML = `<p class="text-red-600">Falha ao carregar os pedidos.</p>`;
    }
}

function renderStatistics({ total_orders, total_accounts, total_success, total_erro, total_awaiting }) {
    document.getElementById("stats-container").innerHTML = `
        <div class="bg-gray-50 p-4 rounded-lg"><p class="text-sm text-gray-600">Total de Pedidos</p><p class="text-2xl font-semibold text-gray-800">${total_orders}</p></div>
        <div class="bg-gray-50 p-4 rounded-lg"><p class="text-sm text-gray-600">Total de Contas</p><p class="text-2xl font-semibold text-gray-800">${total_accounts}</p></div>
        <div class="bg-gray-50 p-4 rounded-lg"><p class="text-sm text-gray-600">Criações com Sucesso</p><p class="text-2xl font-semibold text-green-600">${total_success}</p></div>
        <div class="bg-gray-50 p-4 rounded-lg"><p class="text-sm text-gray-600">Criações com Erro</p><p class="text-2xl font-semibold text-red-600">${total_erro}</p></div>
        <div class="bg-gray-50 p-4 rounded-lg"><p class="text-sm text-gray-600">Criações em Aguardo</p><p class="text-2xl font-semibold text-yellow-600">${total_awaiting}</p></div>
    `;
}

function renderTable(orders) {
    const tbody = document.getElementById("orders-table-body");
    tbody.innerHTML = "";

    orders.forEach(({ order_id, cwp_logins, cwp_emails }) => {
        cwp_logins.forEach(({ login, status }, index) => {
            const email = cwp_emails[index] || "-";
            const statusClass = getStatusClass(status);
            tbody.innerHTML += `
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${order_id}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${login}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${email}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${statusClass}">
                            ${status}
                        </span>
                    </td>
                </tr>
            `;
        });
    });
}

function getStatusClass(status) {
    const statusClasses = {
        "Sucesso": "bg-green-100 text-green-800",
        "Erro": "bg-red-100 text-red-800",
        "Aguardando": "bg-yellow-100 text-yellow-800"
    };
    return statusClasses[status] || "bg-gray-100 text-gray-800";
}

function renderPagination({ current_page, total_pages, next_page, previous_page, per_page }) {
    const paginationContainer = document.getElementById("pagination-container");
    paginationContainer.innerHTML = `
        <div class="text-sm text-gray-700">
            Mostrando página <span class="font-medium">${current_page}</span> de <span class="font-medium">${total_pages}</span>
        </div>
        <nav class="relative z-0 inline-flex rounded-md shadow-sm">
            <button ${previous_page ? `onclick="fetchAndRenderOrders(${current_page - 1}, ${per_page})"` : "disabled"} 
                class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 ${previous_page ? "hover:bg-gray-50" : "opacity-50 cursor-not-allowed"}">
                Anterior
            </button>
            <button ${next_page ? `onclick="fetchAndRenderOrders(${current_page + 1}, ${per_page})"` : "disabled"} 
                class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 ${next_page ? "hover:bg-gray-50" : "opacity-50 cursor-not-allowed"}">
                Próximo
            </button>
        </nav>
    `;
}
