var xmlHttp;

function checkUsername() {
    document.getElementById('erroruse').innerHTML = 'กำลังตรวจสอบ username นี้ กรุณารอสักครู่...';
    document.getElementById('erroruse').style.color = 'gray';
    xmlHttp = new XMLHttpRequest();
    xmlHttp.onreadystatechange = showResult2;
    var username = document.getElementById("username").value;

    var url = "../scripts/checkuser.php?username=" + username;
    xmlHttp.open("GET", url);
    xmlHttp.send();
}
function showResult2() {
    if (xmlHttp.readyState == 4 && xmlHttp.status == 200) {
        console.log(xmlHttp.responseText);
        if (xmlHttp.responseText.includes("username_existed")) {
            document.getElementById('erroruse').innerHTML = 'พบ username นี้แล้วในระบบ';
            document.getElementById('erroruse').style.color = 'red';
        } else {
            document.getElementById('erroruse').innerHTML = 'username นี้สามารถใช้ได้';
            document.getElementById('erroruse').style.color = 'green';
        }

    }
}

function send() {
    document.getElementById("submit").value = "กำลังลงทะเบียนให้ท่าน โปรดรอสักครู่...";
    xmlHttp = new XMLHttpRequest();
    xmlHttp.onreadystatechange = showResult;

    var username = document.getElementById("username").value;
    var password = document.getElementById("password").value;
    var cf_password = document.getElementById("cf_password").value;

    if (password !== cf_password) {
        document.getElementById("errorpass").innerHTML = "รหัสผ่านไม่ตรงกัน";
        document.getElementById('error').innerHTML = 'รหัสผ่านไม่ตรงกัน';
        document.getElementById("submit").value = "Sign up";
        return;
    }

    var first_name = document.getElementById("first_name").value;
    var last_name = document.getElementById("last_name").value;
    var address = document.getElementById("address").value;
    var postal = document.getElementById("postal").value;
    var province = document.getElementById("province").value;
    var email = document.getElementById("email").value;
    var tel = document.getElementById("tel").value;

    console.log(username + " " + password + " " + cf_password + " " + first_name + " "
    + last_name + " " + address + " " + postal + " " + province + " " + email + " " + tel);

    if (!username || !password || !cf_password || !first_name || !last_name ||
        !address || !postal || !province || !email || !tel) {
        document.getElementById('error').innerHTML = 'ท่านกรอกข้อมูลไม่ครบถ้วน';
        document.getElementById("submit").value = "Sign up";
        return;
    }

    var url= "../scripts/register.php?username="+username+
             "&password="+password+"&cf_password="+cf_password+
             "&first_name="+first_name+"&last_name="+last_name+
             "&address="+address+"&postal="+postal+
             "&province="+province+"&email="+email+"&tel="+tel;

    xmlHttp.open("GET", url);
    xmlHttp.send();
}
function showResult() {
    if (xmlHttp.readyState == 4 && xmlHttp.status == 200) {
        console.log(xmlHttp.responseText);
        if (xmlHttp.responseText.includes("username_existed")) {
            document.getElementById('error').innerHTML = 'พบ username นี้แล้วในระบบ';
            document.getElementById("submit").value = "Sign up";
        } else if (xmlHttp.responseText.includes("password_mismatch")) {
            document.getElementById('error').innerHTML = 'รหัสผ่านไม่ตรงกัน';
            document.getElementById("submit").value = "Sign up";
        } else {
            alert("ลงทะเบียนสำเร็จ กำลังนำท่านกลับไปหน้าแรก");
            window.location.href = '../';
        }
    }
}
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
    xmlHttp = new XMLHttpRequest();
    xmlHttp.onreadystatechange = convertPostal;
    const url = 'https://raw.githubusercontent.com/kongvut/thai-province-data/refs/heads/master/api/latest/sub_district_with_district_and_province.json';
    xmlHttp.open('GET', url);
    xmlHttp.send();
}

function convertPostal() {
    if (xmlHttp.readyState == 4 && xmlHttp.status == 200) {
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

function check_password() {
    var password = document.getElementById("password").value;
    var cf_password = document.getElementById("cf_password").value;

    if (password !== cf_password) {
        document.getElementById("errorpass").innerHTML = "รหัสผ่านไม่ตรงกัน";
        document.getElementById('errorpass').style.color = 'red';
    } else {
        document.getElementById("errorpass").innerHTML = "รหัสผ่านตรงกัน";
        document.getElementById('errorpass').style.color = 'green';
    }
}