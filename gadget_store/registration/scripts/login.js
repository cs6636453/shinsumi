let xml;

function send3() {
    document.getElementById("submit").value = "Logging you in...";
    xml = new XMLHttpRequest();
    xml.onreadystatechange = showResult3;

    let username = document.getElementById("username").value;
    let password = document.getElementById("password").value;

    let params = "username=" + encodeURIComponent(username) + "&password=" + encodeURIComponent(password);
    let url= "../scripts/login.php";

    xml.open("POST", url);
    xml.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xml.send(params);
}
function showResult3() {
    if (xml.readyState == 4 && xml.status == 200) {
        console.log(xml.responseText);
        if (xml.responseText.includes("Error")) {
            document.getElementById('error').innerHTML = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
            document.getElementById("submit").value = "Log in";
        } else {
            window.location.href = '../';
        }
    }
}