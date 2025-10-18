var xmlHttp;

function send() {
    document.getElementById("submit").value = "Logging you in...";
    xmlHttp = new XMLHttpRequest();
    xmlHttp.onreadystatechange = showResult;

    var username = document.getElementById("username").value;
    var password = document.getElementById("password").value;

    var url= "../scripts/login.php?username="+username+"&password="+password;

    xmlHttp.open("GET", url);
    xmlHttp.send();
}
function showResult() {
    if (xmlHttp.readyState == 4 && xmlHttp.status == 200) {
        console.log(xmlHttp.responseText);
        if (xmlHttp.responseText.includes("Error")) {
            document.getElementById('error').innerHTML = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
            document.getElementById("submit").value = "Log in";
        } else {
            window.location.href = '../';
        }
    }
}