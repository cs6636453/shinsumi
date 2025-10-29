const dateX = ["01/11", "02/11", "03/11", "04/11", "05/11", "06/11", "07,11"];
const priceY = [12000, 15000, 12000, 18000, 20000, 18000, 24000];

const ctx = document.getElementById("saleGraph");

new Chart(ctx, {
    type: "line",
    data: {
        labels: dateX,
        datasets: [{
            fill: false,
            lineTension: 0,
            backgroundColor: "rgba(0,0,255,1.0)",
            borderColor: "skyblue",
            data: priceY
        }]
    },
    options: {
        plugins: {
            legend: {display:false},
        }
    }
});

const statusListX = ["pending",
                             "packing",
                             "shipping",
                             "completed",
                             "failed",
                             "cancelled",
                             "refunded"];

const orderListY = [5, 2, 1, 100, 5, 0, 0];

const ctx2 = document.getElementById("shippingGraph");

const barColors = [
    "#ffcc00",
    "#ffcc00",
    "#0dcfff",
    "#36c14d",
    "#e43535",
    "#e43535",
    "#e43535"
];

new Chart(ctx2, {
    type: "doughnut",
    data: {
        labels: statusListX,
        datasets: [{
            backgroundColor: barColors,
            data: orderListY
        }]
    },
    options: {
        plugins: {
            legend: {display:true},

        }
    }
});
