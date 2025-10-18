var xmlHttp;

function send() {
    xmlHttp = new XMLHttpRequest();
    xmlHttp.onreadystatechange = showResult;

    var url= "scripts/login_check.php";

    xmlHttp.open("GET", url);
    xmlHttp.send();
}
function showResult() {
    console.log(xmlHttp.responseText);

    if (xmlHttp.readyState == 4 && xmlHttp.status == 200) {
        document.getElementById("login_btn").innerHTML = xmlHttp.responseText;
    }
}

send();