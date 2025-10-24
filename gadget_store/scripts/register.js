let xml2;
let xml3;

function checkUsername() {
    document.getElementById('erroruse').innerHTML = 'กำลังตรวจสอบ username นี้ กรุณารอสักครู่...';
    document.getElementById('erroruse').style.color = 'gray';
    xml2 = new XMLHttpRequest();
    xml2.onreadystatechange = showResultUsername;
    let username = document.getElementById("username").value;

    let params = "username=" + encodeURIComponent(username);
    let url = "scripts/checkuser.php";
    xml2.open("POST", url);
    xml2.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xml2.send(params);
}
function showResultUsername() {
    if (xml2.readyState == 4 && xml2.status == 200) {
        console.log(xml2.responseText);
        if (xml2.responseText.includes("username_existed")) {
            document.getElementById('erroruse').innerHTML = 'พบ username นี้แล้วในระบบ';
            document.getElementById('erroruse').style.color = 'red';
        } else {
            document.getElementById('erroruse').innerHTML = 'username นี้สามารถใช้ได้';
            document.getElementById('erroruse').style.color = 'green';
        }

    }
}

function send_register() {
    document.getElementById("submit").value = "กำลังลงทะเบียนให้ท่าน โปรดรอสักครู่...";
    xml3 = new XMLHttpRequest();
    xml3.onreadystatechange = showResultFinal;

    let username = document.getElementById("username").value;
    let password = document.getElementById("password").value;
    let cf_password = document.getElementById("cf_password").value;

    if (password !== cf_password) {
        document.getElementById("errorpass").innerHTML = "รหัสผ่านไม่ตรงกัน";
        document.getElementById('error').innerHTML = 'รหัสผ่านไม่ตรงกัน';
        document.getElementById("submit").value = "Sign up";
        return;
    }

    let first_name = document.getElementById("first_name").value;
    let last_name = document.getElementById("last_name").value;
    let address = document.getElementById("address").value;
    let postal = document.getElementById("postal").value;
    let province = document.getElementById("province").value;
    let email = document.getElementById("email").value;
    let tel = document.getElementById("tel").value;

    if (!username || !password || !cf_password || !first_name || !last_name ||
        !address || !postal || !province || !email || !tel) {
        document.getElementById('error').innerHTML = 'ท่านกรอกข้อมูลไม่ครบถ้วน';
        document.getElementById("submit").value = "Sign up";
        return;
    }

    let params = "username=" + encodeURIComponent(username) + "&password=" + encodeURIComponent(password) +
                 "&cf_password=" + encodeURIComponent(cf_password) + "&first_name=" + encodeURIComponent(first_name) +
                 "&last_name=" + encodeURIComponent(last_name) + "&address=" + encodeURIComponent(address) +
                 "&postal=" + encodeURIComponent(postal) + "&province=" + encodeURIComponent(province) +
                 "&email=" + encodeURIComponent(email) + "&tel=" + encodeURIComponent(tel);

    let url= "../scripts/register.php";
    xml3.open("POST", url);
    xml3.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xml3.send(params);
}
function showResultFinal() {
    if (xml3.readyState == 4 && xml3.status == 200) {
        console.log(xml3.responseText);
        if (xml3.responseText.includes("username_existed")) {
            document.getElementById('error').innerHTML = 'พบ username นี้แล้วในระบบ';
            document.getElementById("submit").value = "Sign up";
        } else if (xml3.responseText.includes("password_mismatch")) {
            document.getElementById('error').innerHTML = 'รหัสผ่านไม่ตรงกัน';
            document.getElementById("submit").value = "Sign up";
        } else {
            alert("ลงทะเบียนสำเร็จ กำลังนำท่านกลับไปหน้าแรก");
            window.location.href = '../';
        }
    }
}


function check_password() {
    let password = document.getElementById("password").value;
    let cf_password = document.getElementById("cf_password").value;

    if (password !== cf_password) {
        document.getElementById("errorpass").innerHTML = "รหัสผ่านไม่ตรงกัน";
        document.getElementById('errorpass').style.color = 'red';
    } else {
        document.getElementById("errorpass").innerHTML = "รหัสผ่านตรงกัน";
        document.getElementById('errorpass').style.color = 'green';
    }
}