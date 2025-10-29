let xmrReq;
function getDataFromPostal() {
    let option = document.createElement("option");
    const postal = document.getElementById('postal').value;
    if (postal.length !== 5) {
        option.innerHTML = "กรุณากรอกรหัสไปรษณีย์"
        document.getElementById('province').innerHTML = "";
        document.getElementById('province').appendChild(option);
        return;
    }
    option.innerHTML = "กำลังค้นหาข้อมูล..."
    document.getElementById('province').innerHTML = "";
    document.getElementById('province').appendChild(option);
    console.log("Postal code detected")
    xmrReq = new XMLHttpRequest();
    xmrReq.onreadystatechange = convertPostal;
    const url = 'https://raw.githubusercontent.com/kongvut/thai-province-data/refs/heads/master/api/latest/sub_district_with_district_and_province.json';
    xmrReq.open('GET', url);
    xmrReq.send();
}

function convertPostal() {
    if (xmrReq.readyState == 4 && xmrReq.status == 200) {
        const objectData = JSON.parse(this.responseText);
        const postal = document.getElementById('postal').value;
        let province = document.getElementById('province');
        let option = document.createElement("option");
        let foundMatch = false;
        document.getElementById('province').innerHTML = "";
        for (let i = 0; i < objectData.length; i++) {
            let zip_code = objectData[i].zip_code;
            let option2 = document.createElement("option");
            if (zip_code.toString().includes(postal)) {
                foundMatch = true;
                option2.innerHTML = objectData[i].district.province.name_th + " > " + objectData[i].district.name_th + " > " + objectData[i].name_th;
                option2.value = objectData[i].district.province.name_th + ", " +
                    objectData[i].district.name_th + ", " + objectData[i].name_th;
                province.appendChild(option2);
            }
        }
        if (!foundMatch) {
            option.innerHTML = "ไม่พบข้อมูล";
            province.innerHTML = option;
            document.getElementById('province').appendChild(option);
        }
    }
}