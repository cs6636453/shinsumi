let xmlPro;

function xmlProReq() {
    xmlPro = new XMLHttpRequest();
    xmlPro.onreadystatechange = xmlProSet;
    let url = "backend/inner/prodash.php";
    xmlPro.open("GET", url);
    xmlPro.send();
}

function xmlProSet() {
    if (xmlPro.readyState === 4 && xmlPro.status === 200) {
        const result = xmlPro.responseText.split(',');
        document.getElementById("total_sale_price").innerHTML = result[0];
        document.getElementById("total_orders").innerHTML = result[1];
        document.getElementById("newcus").innerHTML = result[2];
    }
}

xmlProReq();

let xmlProList;

function xmlProListReq() {
    xmlProList = new XMLHttpRequest();
    xmlProList.onreadystatechange = xmlProListSet;
    query = document.getElementById("findProduct").value;
    let url = "backend/inner/profind.php?query="+query;
    xmlProList.open("GET", url);
    xmlProList.send();
}

function xmlProListSet() {
    if (xmlProList.readyState === 4 && xmlProList.status === 200) {
        const result = xmlProList.responseText;
        let myTableHTML = `<tr>
                                    <th>ชื่อสินค้า</th>
                                    <th>ราคาสินค้า</th>
                                    <th>หมวดหมู่</th>
                                    <th></th>
                                    <th></th>
                                  </tr>`;
        document.getElementById("myTableList").innerHTML = myTableHTML;
        document.getElementById("myTableList").innerHTML += result;
    }
}

xmlProListReq();

function add() {
    window.location.href = "backend/prod/add.php";
}

function edit(i) {
    window.location.href = "backend/prod/edit.php?id="+i;
}

// Function to handle deletion with confirmation
function del(i, productName) { // Pass product name for better confirmation
    // Use simple string replacement for basic escaping of quotes in the name
    const safeProductName = productName.replace(/'/g, "\\'").replace(/"/g, '\\"');

    // Display a confirmation dialog
    if (confirm(`Are you sure you want to delete the product: ${safeProductName} (ID: ${i})?`)) {
        // If the user clicks "OK", redirect to the delete script
        window.location.href = "backend/prod/del.php?id=" + i;
    }
    // If the user clicks "Cancel", do nothing
}