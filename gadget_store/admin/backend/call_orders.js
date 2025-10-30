let xmlOrd;

function xmlOrdReq() {
    xmlOrd = new XMLHttpRequest();
    xmlOrd.onreadystatechange = xmlOrdSet;
    let url = "backend/inner/ordash.php";
    xmlOrd.open("GET", url);
    xmlOrd.send();
}

function xmlOrdSet() {
    if (xmlOrd.readyState === 4 && xmlOrd.status === 200) {
        console.log(xmlOrd.responseText);
        const result = xmlOrd.responseText.split(',');
        console.log(result);
        document.getElementById("total_sale_price").innerHTML = result[0];
        document.getElementById("total_orders").innerHTML = result[1];
        document.getElementById("newcus").innerHTML = result[2];
    }
}

xmlOrdReq();

let xmlProList;

function xmlProListReq() {
    xmlProList = new XMLHttpRequest();
    xmlProList.onreadystatechange = xmlProListSet;
    query = document.getElementById("findProduct").value;
    let url = "backend/inner/ordfind.php?query="+query;
    xmlProList.open("GET", url);
    xmlProList.send();
}

function xmlProListSet() {
    if (xmlProList.readyState === 4 && xmlProList.status === 200) {
        const result = xmlProList.responseText;
        let myTableHTML = `<tr>
                                    <th>เลขคำสั่งซื้อ</th>
                                    <th>ลูกค้า</th>
                                    <th>วัน/เวลา</th>
                                    <th>สถานะ</th>
                                    <th>จำนวนสินค้า</th>
                                    <th>ยอดรวม</th>
                                  </tr>`;
        document.getElementById("myTableList").innerHTML = myTableHTML;
        document.getElementById("myTableList").innerHTML += result;
    }
}

xmlProListReq();