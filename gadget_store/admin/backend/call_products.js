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