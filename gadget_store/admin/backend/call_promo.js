let xmlPro;

function xmlProReq() {
    xmlPro = new XMLHttpRequest();
    xmlPro.onreadystatechange = xmlProSet;
    let url = "backend/inner/promodash.php";
    xmlPro.open("GET", url);
    xmlPro.send();
}

function xmlProSet() {
    if (xmlPro.readyState === 4 && xmlPro.status === 200) {
        const result = xmlPro.responseText
        document.getElementById("total_sale_price").innerHTML = result;
    }
}

xmlProReq();

let xmlProList;

function xmlProListReq() {
    xmlProList = new XMLHttpRequest();
    xmlProList.onreadystatechange = xmlProListSet;
    query = document.getElementById("findProduct").value;
    let url = "backend/inner/promofind.php?query="+query;
    xmlProList.open("GET", url);
    xmlProList.send();
}

function xmlProListSet() {
    if (xmlProList.readyState === 4 && xmlProList.status === 200) {
        const result = xmlProList.responseText;
        let myTableHTML = `<tr>
                <th>ชื่อ</th>
                <th>ประเภท</th>
                <th>ช่วงเวลา</th>
                <th>สถานะ</th>
                <th>จัดการ</th>
            </tr>`;
        document.getElementById("myTableList").innerHTML = myTableHTML;
        document.getElementById("myTableList").innerHTML += result;
    }
}

xmlProListReq();

function add() {
    window.location.href = "backend/promo/add.php";
}

function edit(i) {
    window.location.href = "backend/promo/edit.php?id="+i;
}

// Function to handle deletion with confirmation
function del(i, productName) { // Pass product name for better confirmation
    // Use simple string replacement for basic escaping of quotes in the name
    const safeProductName = productName.replace(/'/g, "\\'").replace(/"/g, '\\"');

    // Display a confirmation dialog
    if (confirm(`Are you sure you want to delete the product: ${safeProductName} (ID: ${i})?`)) {
        // If the user clicks "OK", redirect to the delete script
        window.location.href = "backend/promo/del.php?id=" + i;
    }
    // If the user clicks "Cancel", do nothing
}