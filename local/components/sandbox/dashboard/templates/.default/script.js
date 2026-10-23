document.addEventListener('DOMContentLoaded', function() {
    if (typeof Chart === 'undefined') {
        console.error('Chart.js не подключён');
        return;
    }

    var rawData = BX.message('DASHBOARD_DATA');
    if (!rawData) {
        console.error('Данные дашборда не найдены');
        return;
    }
    var dashboardData = JSON.parse(BX.message('DASHBOARD_DATA'));

    // Воронка продаж
    var funnelLabels = Object.keys(dashboardData.funnel);
    var funnelValues = Object.values(dashboardData.funnel);

    new Chart(document.getElementById('funnelChart'), {
        type: 'bar',
        data: {
            labels: funnelLabels,
            datasets: [{
                label: 'Количество сделок',
                data: funnelValues,
                backgroundColor: '#3b82f6',
                borderRadius: 6,
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { x: { beginAtZero: true } }
        }
    });

    // Динамика по месяцам
    var monthLabels = Object.keys(dashboardData.months);
    var monthValues = Object.values(dashboardData.months);

    new Chart(document.getElementById('monthChart'), {
        type: 'line',
        data: {
            labels: monthLabels,
            datasets: [{
                label: 'Сумма сделок',
                data: monthValues,
                borderColor: '#10b981',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                fill: true,
                tension: 0.3,
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true } }
        }
    });
});