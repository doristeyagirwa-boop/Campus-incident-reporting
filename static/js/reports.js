const categoryCtx = document.getElementById('categoryChart').getContext('2d');
new Chart(categoryCtx, {
    type: 'pie',
    data: {
        labels: reportData.categoryLabels,
        datasets: [{
            data: reportData.categoryData,
            backgroundColor: ['#0d6efd', '#198754', '#dc3545', '#ffc107', '#0dcaf0']
        }]
    }
});

const monthCtx = document.getElementById('monthChart').getContext('2d');
new Chart(monthCtx, {
    type: 'line',
    data: {
        labels: reportData.monthLabels,
        datasets: [{
            label: 'Incidents',
            data: reportData.monthData,
            borderColor: '#198754',
            backgroundColor: 'rgba(25, 135, 84, 0.2)',
            fill: true,
            tension: 0.4
        }]
    }
});

const resolutionCtx = document.getElementById('resolutionChart').getContext('2d');
new Chart(resolutionCtx, {
    type: 'doughnut',
    data: {
        labels: ['Resolved', 'Unresolved'],
        datasets: [{
            data: [reportData.resolved, reportData.unresolved],
            backgroundColor: ['#198754', '#dc3545']
        }]
    }
});
