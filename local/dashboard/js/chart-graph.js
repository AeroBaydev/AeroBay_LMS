
const xValues5 = ["Italy", "France", "Spain", "USA", "Argentina"];
const yValues5 = [55, 49, 44, 24, 15];
const barColors5 = [
    "#b91d47",
    "#00aba9",
    "#2b5797",
    "#e8c3b9",
    "#1e7145"
];

new Chart("myChart5", {
    type: "doughnut",
    data: {
        labels: xValues5,
        datasets: [{
            backgroundColor: barColors5,
            data: yValues5
        }]
    },
    options: {
        title: {
            display: true,
            text: "World Wide Wine Production 2018"
        }
    }
});


const xValues3 = ["Italy", "France", "Spain", "USA", "Argentina"];
const yValues3 = [55, 49, 44, 24, 15];
const barColors3 = [
    "#b91d47",
    "#00aba9",
    "#2b5797",
    "#e8c3b9",
    "#1e7145"
];

new Chart("myChart3", {
    type: "doughnut",
    data: {
        labels: xValues3,
        datasets: [{
            backgroundColor: barColors3,
            data: yValues3
        }]
    },
    options: {
        title: {
            display: true,
            text: "World Wide Wine Production 2018"
        }
    }
});


const xValues1 = ["Italy", "France", "Spain", "USA", "Argentina"];
const yValues1 = [55, 49, 44, 24, 15];
const barColors1 = ["#FBC24A", "#FBC24A", "#FBC24A", "#FBC24A", "#FBC24A"];

new Chart("myChart1", {
    type: "bar",
    data: {
        labels: xValues1,
        datasets: [{
            backgroundColor: barColors1,
            data: yValues1
        }]
    },
    options: {
        legend: { display: false },
        title: {
            display: true,
            text: "World Wine Production 2018"
        }
    }
});


const xValues2 = ["Italy", "France", "Spain", "USA", "Argentina"];
const yValues2 = [55, 49, 44, 24, 15];
const barColors2 = ["#FBC24A", "#FBC24A", "#FBC24A", "#FBC24A", "#FBC24A"];

new Chart("myChart2", {
    type: "bar",
    data: {
        labels: xValues1,
        datasets: [{
            backgroundColor: barColors2,
            data: yValues2
        }]
    },
    options: {
        legend: { display: false },
        title: {
            display: true,
            text: "World Wine Production 2018"
        }
    }
});


    const xValues4 = ["Italy", "France", "Spain", "USA", "Argentina"];
    const yValues4 = [55, 49, 44, 24, 15];
    const barColors4 = ["#FBC24A", "#FBC24A", "#FBC24A", "#FBC24A", "#FBC24A"];

    new Chart("myChart4", {
        type: "bar",
        data: {
            labels: xValues4,
            datasets: [{
                backgroundColor: barColors4,
                data: yValues4
            }]
        },
        options: {
            legend: { display: false },
            title: {
                display: true,
                text: "World Wine Production 2018"
            }
        }
    });


google.charts.load('current', { 'packages': ['corechart'] });
google.charts.setOnLoadCallback(drawChart);

function drawChart() {

    // Set Data
    const data = google.visualization.arrayToDataTable([
        ['Contry', 'Mhl'],
        ['Italy', 55],
        ['France', 49],
        ['Spain', 44],
        ['USA', 24],
        ['Argentina', 15]
    ]);

    // Set Options
    const options = {
        title: 'World Wide Wine Production'
    };

    // Draw
    const chart = new google.visualization.BarChart(document.getElementById('myChart6'));
    chart.draw(data, options);

}
