import {
    Chart,
    LineController,
    BarController,
    LineElement,
    BarElement,
    PointElement,
    CategoryScale,
    LinearScale,
    Filler,
    Legend,
    Tooltip,
} from 'chart.js';

Chart.register(
    LineController,
    BarController,
    LineElement,
    BarElement,
    PointElement,
    CategoryScale,
    LinearScale,
    Filler,
    Legend,
    Tooltip,
);

const lineOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
        y: { beginAtZero: true, ticks: { font: { size: 9 } }, grid: { color: '#f3f4f6' } },
        x: { ticks: { font: { size: 9 } }, grid: { display: false } },
    },
    elements: { line: { tension: 0.3 }, point: { radius: 2 } },
};

const barOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
        y: { beginAtZero: true, ticks: { font: { size: 9 } }, grid: { display: false } },
        x: { ticks: { font: { size: 9 } }, grid: { display: false } },
    },
};

function lineChart(elementId, labels, data, color, extraOptions = {}) {
    const el = document.getElementById(elementId);
    if (!el) return;

    new Chart(el, {
        type: 'line',
        data: {
            labels,
            datasets: [{
                data,
                borderColor: `rgb(${color})`,
                backgroundColor: `rgba(${color}, 0.1)`,
                fill: true,
            }],
        },
        options: { ...lineOptions, ...extraOptions },
    });
}

function barChart(elementId, entries, color) {
    const el = document.getElementById(elementId);
    if (!el) return;

    new Chart(el, {
        type: 'bar',
        data: {
            labels: entries.map((e) => e.name),
            datasets: [{ data: entries.map((e) => e.value), backgroundColor: `rgba(${color}, 0.8)` }],
        },
        options: barOptions,
    });
}

// First name of the entity, for compact bar labels.
function completedByEntity(stats, entities) {
    return Object.entries(stats).map(([id, entityStats]) => ({
        name: entities.find((e) => e.id === parseInt(id))?.name?.split(' ')[0] || '?',
        value: entityStats.completed || 0,
    })).sort((a, b) => b.value - a.value);
}

function init() {
    const dataEl = document.getElementById('billing-chart-data');
    if (!dataEl) return;

    const { trend, teacherStats, studentStats, teachers, students } = JSON.parse(dataEl.textContent);

    lineChart('lessonsTrendChart', trend.labels, trend.totals, '99, 102, 241');
    lineChart('completionTrendChart', trend.labels, trend.completionRates, '34, 197, 94', {
        scales: { ...lineOptions.scales, y: { ...lineOptions.scales.y, max: 100 } },
    });
    lineChart('cancellationsTrendChart', trend.labels, trend.cancellations, '239, 68, 68');

    barChart('teacherWorkloadChart', completedByEntity(teacherStats, teachers), '99, 102, 241');
    barChart('studentActivityChart', completedByEntity(studentStats, students).slice(0, 8), '236, 72, 153');
}

document.readyState === 'loading'
    ? document.addEventListener('DOMContentLoaded', init)
    : init();
