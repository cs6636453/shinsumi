// Global variables
let avg = ["day", "week", "month", "year"];
let query = 'day';
let xmlQuery; // For summary numbers

// --- Chart Instances (Initialized once) ---
let saleChart = null; // Line chart
let orderStatusChart = null; // Doughnut chart
const defaultBarColors = [
    "#FFA500", // pending (Matches index 0 in PHP's $all_statuses_ordered)
    "#4682B4", // packing (Matches index 1)
    "#1E90FF", // shipping (Matches index 2)
    "#28A745", // completed (Matches index 3)
    "#DC3545", // failed (Matches index 4)
    "#6C757D", // cancelled (Matches index 5)
    "#B042F5"  // refunded (Matches index 6)
];

// --- Initialization Function (Called on DOMContentLoaded) ---
function initializeDashboard() {
    console.log("Initializing dashboard...");

    // Initialize Line Chart (Sales Trend)
    const ctxLine = document.getElementById("saleGraph")?.getContext('2d');
    if (ctxLine) {
        saleChart = new Chart(ctxLine, {
            type: "line",
            data: {
                labels: [],
                datasets: [{
                    fill: false,
                    lineTension: 0.1,
                    backgroundColor: "rgba(75,192,192,0.4)",
                    borderColor: "rgba(75,192,192,1)",
                    borderCapStyle: 'butt',
                    borderDash: [],
                    borderDashOffset: 0.0,
                    borderJoinStyle: 'miter',
                    pointBorderColor: "rgba(75,192,192,1)",
                    pointBackgroundColor: "#fff",
                    pointBorderWidth: 1,
                    pointHoverRadius: 5,
                    pointHoverBackgroundColor: "rgba(75,192,192,1)",
                    pointHoverBorderColor: "rgba(220,220,220,1)",
                    pointHoverBorderWidth: 2,
                    pointRadius: 3,
                    pointHitRadius: 10,
                    data: []
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: { legend: { display: false } },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '฿' + value.toLocaleString('en-US');
                            }
                        }
                    }
                }
            }
        });
        console.log("Sales line chart initialized.");
    } else {
        console.error("Canvas element with ID 'saleGraph' not found.");
    }

    // Initialize Doughnut Chart (Order Status)
    const ctxDoughnut = document.getElementById("shippingGraph")?.getContext('2d');
    if (ctxDoughnut) {
        orderStatusChart = new Chart(ctxDoughnut, {
            type: "doughnut",
            data: {
                labels: [],
                datasets: [{
                    backgroundColor: defaultBarColors,
                    data: []
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom',
                        labels: {
                            boxWidth: 15,
                            padding: 20,
                            usePointStyle: true,
                            pointStyle: 'circle',
                            generateLabels: function(chart) {
                                const data = chart.data;
                                if (data.labels.length && data.datasets.length) {
                                    const { labels: { pointStyle } } = chart.legend.options;
                                    return data.labels.map((label, i) => {
                                        const ds = data.datasets[0];
                                        const value = ds.data[i];
                                        const backgroundColor = ds.backgroundColor[i % ds.backgroundColor.length];
                                        return {
                                            text: `${label}`,
                                            fillStyle: backgroundColor,
                                            pointStyle: pointStyle || 'circle',
                                            hidden: !chart.getDataVisibility(i),
                                            index: i
                                        };
                                    });
                                }
                                return [];
                            }
                        }
                    },
                    title: { display: false },
                    layout: { padding: 0 }
                }
            }
        });
        console.log("Order status doughnut chart initialized.");
    } else {
        console.error("Canvas element with ID 'shippingGraph' not found.");
    }

    // Trigger initial data load for the default period ('day')
    // This call handles all initial fetches needed
    avgClick(0);
}

// --- [NEW FUNCTION] Set All Modules to Loading State ---
function showLoadingStates() {
    console.log("Setting loading states for all modules...");

    // 1. Summary Numbers
    // Using "..." or a small spinner class is a good, simple indicator
    const salesEl = document.getElementById("total_sale_price");
    const ordersEl = document.getElementById("total_orders");
    const newcusEl = document.getElementById("newcus");
    if (salesEl) salesEl.innerHTML = "...";
    if (ordersEl) ordersEl.innerHTML = "...";
    if (newcusEl) newcusEl.innerHTML = "...";

    // 2. Sales Line Chart
    // Clearing data will make the chart animate to empty, indicating loading
    if (saleChart) {
        saleChart.data.labels = [];
        saleChart.data.datasets[0].data = [];
        saleChart.update();
    }

    // 3. Order Status Doughnut Chart
    if (orderStatusChart) {
        orderStatusChart.data.labels = [];
        orderStatusChart.data.datasets[0].data = [];
        orderStatusChart.update();
    }

    // 4. Best Selling Products
    const best_sales_container = document.getElementById("best_sales");
    if (best_sales_container) {
        best_sales_container.innerHTML = '<div class="best_detail" style="text-align: center;"><p>Loading...</p></div>';
    }

    // 5. Last Orders Table
    const tableBody = document.getElementById("myTableList");
    if (tableBody) {
        // Keep header row if it's static in HTML, otherwise, this is fine
        // Based on your code, you regenerate the header, so this is correct.
        tableBody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding: 20px;">Loading orders...</td></tr>';
    }
}


// --- [MODIFIED] Period Change Handler ---
function avgClick(i) {
    console.log(`avgClick called with index: ${i}`);
    // Update active button style
    for (let j = 0; j < avg.length; j++) {
        document.getElementById(avg[j])?.classList.remove("active"); // Use optional chaining
    }
    const clickedElement = document.getElementById(avg[i]);
    if (clickedElement) {
        clickedElement.classList.add("active");
        query = avg[i];
        console.log(`Current period set to: ${query}`);

        // --- ADDED ---
        // Set all components to a loading state *before* fetching new data
        showLoadingStates();
        // --- END ADDED ---

        // Call all fetch functions with the new period
        fetchChartData(query);
        fetchOrderStatusData(query);
        fetchProductSalesStock(query);
        fetchLastOrders(query);
        fetchSummaryNumbers(query); // Call the summary AJAX function
    } else {
        console.error(`Button element with ID '${avg[i]}' not found.`);
    }
}

// --- AJAX Function for Summary Numbers ---
function fetchSummaryNumbers(period) {
    console.log("Fetching summary numbers for period:", period);
    xmlQuery = new XMLHttpRequest(); // Reusing global variable
    xmlQuery.onreadystatechange = querySumResult;
    // *** Verify this path ***
    let url = "backend/inner/number.php?query=" + period;
    console.log("Requesting URL:", url);
    xmlQuery.open("GET", url, true);
    xmlQuery.send();
}

function querySumResult() {
    if (xmlQuery.readyState == 4 && xmlQuery.status == 200) {
        console.log("Summary numbers AJAX successful. Response:", xmlQuery.responseText);
        const results = xmlQuery.responseText.split(',');
        if (results.length === 3) {
            // total_sale_price, total_orders, newcus
            const salesEl = document.getElementById("total_sale_price");
            const ordersEl = document.getElementById("total_orders");
            const newcusEl = document.getElementById("newcus");

            // Update elements safely, format sales price
            if (salesEl) salesEl.innerHTML = parseFloat(results[0]).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            else console.error("Element 'total_sale_price' not found");

            if (ordersEl) ordersEl.innerHTML = results[1];
            else console.error("Element 'total_orders' not found");

            if (newcusEl) newcusEl.innerHTML = results[2];
            else console.error("Element 'newcus' not found");

        } else {
            console.error("Summary numbers query format error. Expected 3 values, got:", results.length, "Response:", xmlQuery.responseText);
            // Optionally set error message in UI
            const salesEl = document.getElementById("total_sale_price");
            if (salesEl) salesEl.innerHTML = "Error";
        }
    } else if (xmlQuery.readyState == 4) {
        if(xmlQuery.status !== 200) {
            console.error("Summary numbers AJAX request failed. Status:", xmlQuery.status);
            console.error("Received response:", xmlQuery.responseText);
            // Optionally set error message in UI
            const salesEl = document.getElementById("total_sale_price");
            if (salesEl) salesEl.innerHTML = "Error";
        }
    }
}


// --- AJAX Function for Line Chart (Sales Trend) ---
function fetchChartData(period) {
    if (!saleChart) {
        console.warn("Sales chart not initialized. Skipping fetchChartData.");
        return;
    }
    console.log("Fetching sales trend data for period:", period);
    let xhttp = new XMLHttpRequest();
    xhttp.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {
            console.log("Sales trend AJAX successful. Response:", this.responseText);
            try {
                const data = JSON.parse(this.responseText);
                if (data && data.dateX && data.priceY) {
                    console.log("Parsed sales trend data:", data);
                    saleChart.data.labels = data.dateX;
                    saleChart.data.datasets[0].data = data.priceY;
                    saleChart.update();
                    console.log("Sales line chart updated successfully.");
                } else {
                    console.error("Invalid JSON structure for sales trend:", data);
                    saleChart.data.labels = ["Error"]; saleChart.data.datasets[0].data = [0]; saleChart.update();
                }
            } catch (e) {
                console.error("Error parsing JSON for sales trend:", e, "Response:", this.responseText);
                saleChart.data.labels = ["Error"]; saleChart.data.datasets[0].data = [0]; saleChart.update();
            }
        } else if (this.readyState == 4) {
            if(this.status !== 200) {
                console.error("Sales trend AJAX request failed. Status:", this.status, "Response:", this.responseText);
                saleChart.data.labels = ["AJAX Error"]; saleChart.data.datasets[0].data = [0]; saleChart.update();
            }
        }
    };
    // *** Verify this path ***
    const url = "backend/inner/graph.php?query=" + period;
    console.log("Requesting URL:", url);
    xhttp.open("GET", url, true);
    xhttp.send();
}

// --- AJAX Function for Doughnut Chart (Order Status) ---
function fetchOrderStatusData(period) {
    if (!orderStatusChart) {
        console.warn("Order status chart not initialized. Skipping fetchOrderStatusData.");
        return;
    }
    console.log("Fetching order status data for period:", period);
    let xhttp = new XMLHttpRequest();
    xhttp.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {
            console.log("Order status AJAX successful. Response:", this.responseText);
            try {
                const data = JSON.parse(this.responseText);
                if (data && data.statusListX && data.orderListY) {
                    console.log("Parsed order status data:", data);
                    orderStatusChart.data.labels = data.statusListX;
                    orderStatusChart.data.datasets[0].data = data.orderListY;
                    orderStatusChart.data.datasets[0].backgroundColor = defaultBarColors.slice(0, data.statusListX.length);
                    orderStatusChart.update();
                    console.log("Order status chart updated successfully.");
                } else {
                    console.error("Invalid JSON structure for order status:", data);
                    orderStatusChart.data.labels = ["Error"]; orderStatusChart.data.datasets[0].data = [0]; orderStatusChart.update();
                }
            } catch (e) {
                console.error("Error parsing JSON for order status:", e, "Response:", this.responseText);
                orderStatusChart.data.labels = ["Error"]; orderStatusChart.data.datasets[0].data = [0]; orderStatusChart.update();
            }
        } else if (this.readyState == 4) {
            if (this.status !== 200) {
                console.error("Order status AJAX request failed. Status:", this.status, "Response:", this.responseText);
                orderStatusChart.data.labels = ["AJAX Error"]; orderStatusChart.data.datasets[0].data = [0]; orderStatusChart.update();
            }
        }
    };
    // *** Verify this path ***
    const url = "backend/inner/pie.php?query=" + period;
    console.log("Requesting URL:", url);
    xhttp.open("GET", url, true);
    xhttp.send();
}

// --- [MODIFIED] AJAX Function for Best Selling Products List ---
function fetchProductSalesStock(period) {
    console.log("Fetching product sales/stock data for period:", period);
    const best_sales_container = document.getElementById("best_sales");
    if (!best_sales_container) {
        console.error("Element with ID 'best_sales' not found. Cannot display product sales.");
        return;
    }
    // --- REMOVED ---
    // The loading state is now set in showLoadingStates()
    // best_sales_container.innerHTML = '<div class="best_detail"><p>Loading...</p></div>';
    // --- END REMOVED ---

    let xhttp = new XMLHttpRequest();
    xhttp.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {
            console.log("Product Sales AJAX successful. Response:", this.responseText);
            best_sales_container.innerHTML = ''; // Clear loading/previous content
            try {
                const data = JSON.parse(this.responseText);
                if (data && data.productNames && data.quantitiesSold && data.currentStocks) {
                    console.log("Parsed product sales data:", data);
                    const { productNames, quantitiesSold, currentStocks } = data;

                    if (productNames.length === 0) {
                        best_sales_container.innerHTML = '<div class="best_detail" style="text-align: center; color: #777;"><p>No product sales found for this period.</p></div>';
                        return;
                    }

                    for (let i = 0; i < productNames.length; i++) {
                        let productHTML = `
                            <div class="best_detail">
                                <h6>${productNames[i]}</h6>
                                <span>
                                    <p>ขายแล้ว: ${quantitiesSold[i]}</p>
                                    <p class="red">คงเหลือ: ${currentStocks[i]}</p>
                                </span>
                                <hr/>
                            </div>
                        `;
                        best_sales_container.innerHTML += productHTML;
                    }
                    console.log("Best sales list updated.");
                } else {
                    console.error("Invalid JSON structure for product sales:", data);
                    best_sales_container.innerHTML = '<div class="best_detail" style="color:red; text-align: center;"><p>Error loading product sales data.</p></div>';
                }
            } catch (e) {
                console.error("Error parsing JSON for product sales:", e, "Response:", this.responseText);
                best_sales_container.innerHTML = '<div class="best_detail" style="color:red; text-align: center;"><p>Error parsing product sales data.</p></div>';
            }
        } else if (this.readyState == 4) {
            if (this.status !== 200) {
                console.error("Product sales AJAX request failed. Status:", this.status, "Response:", this.responseText);
                best_sales_container.innerHTML = '<div class="best_detail" style="color:red; text-align: center;"><p>AJAX request failed for product sales.</p></div>';
            }
        }
    };
    // *** Verify this path ***
    const url = "backend/inner/sales.php?query=" + period;
    console.log("Requesting URL:", url);
    xhttp.open("GET", url, true);
    xhttp.send();
}

// --- [MODIFIED] AJAX Function for Last Orders Table ---
function fetchLastOrders(period) {
    console.log("Fetching last orders for period:", period);
    const tableBody = document.getElementById("myTableList");
    if (!tableBody) {
        console.error("Element with ID 'myTableList' not found. Cannot display last orders.");
        return;
    }
    // --- REMOVED ---
    // The loading state is now set in showLoadingStates()
    // tableBody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding: 20px;">Loading orders...</td></tr>';
    // --- END REMOVED ---


    let xhttp = new XMLHttpRequest();
    xhttp.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {
            console.log("Last orders AJAX successful. Response:", this.responseText);
            tableBody.innerHTML = ''; // Clear loading/previous content
            try {
                const orders = JSON.parse(this.responseText);
                if (Array.isArray(orders)) {
                    console.log("Parsed last orders data:", orders);

                    if (orders.length === 0) {
                        tableBody.innerHTML = '<tr><td colspan="5" style="text-align:center; color: #777; padding: 20px;">No orders found for this period.</td></tr>';
                        return;
                    }

                    let rowHTML1 = `
                    <tr>
                <th>รหัส</th>
                <th>ลูกค้า</th>
                <th>ยอดเงิน</th>
                <th>สถานะ</th>
                <th>เวลา</th>
            </tr>
                                            `;
                    tableBody.innerHTML = rowHTML1;

                    orders.forEach(order => {
                        let rowHTML = `
                            <tr>
                                <td><a href="detail.php?id=${order.id}">#${order.id}</a></td>
                                <td>${order.name}</td>
                                <td class="price">฿ ${order.total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                                <td class="${order.status}">${order.status}</td>
                                <td class="time">${order.timeAgo}</td>
                            </tr>
                        `;
                        tableBody.innerHTML += rowHTML;
                    });
                    console.log("Last orders table updated.");

                } else {
                    console.error("Invalid JSON structure for last orders (expected array):", orders);
                    tableBody.innerHTML = '<tr><td colspan="5" style="text-align:center; color: red; padding: 20px;">Error loading orders data.</td></tr>';
                }
            } catch (e) {
                console.error("Error parsing JSON for last orders:", e, "Response:", this.responseText);
                tableBody.innerHTML = '<tr><td colspan="5" style="text-align:center; color: red; padding: 20px;">Error parsing order data.</td></tr>';
            }
        } else if (this.readyState == 4) {
            if (this.status !== 200) {
                console.error("Last orders AJAX request failed. Status:", this.status, "Response:", this.responseText);
                tableBody.innerHTML = '<tr><td colspan="5" style="text-align:center; color: red; padding: 20px;">AJAX request failed for orders.</td></tr>';
            }
        }
    };
    // *** Verify this path ***
    const url = "backend/inner/orders.php?query=" + period;
    console.log("Requesting URL:", url);
    xhttp.open("GET", url, true);
    xhttp.send();
}

// --- Initial Data Load Trigger ---
document.addEventListener('DOMContentLoaded', initializeDashboard);