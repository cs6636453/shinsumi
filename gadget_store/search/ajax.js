let queryXML;

function queryXMLfunc() {
    queryXML = new XMLHttpRequest();
    queryXML.onreadystatechange = showQueryXMLfunc;
    const urlParams = new URLSearchParams(window.location.search);
    const searchTerm = urlParams.get('search');
    document.getElementById("search_param").value = searchTerm;
    let params = "search=" + encodeURIComponent(searchTerm);
    let url = "query.php";
    queryXML.open("POST", url);
    queryXML.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    queryXML.send(params);
}
function showQueryXMLfunc() {
    if (queryXML.readyState == 4 && queryXML.status == 200) {
        document.getElementById("queryResult").innerHTML = queryXML.responseText;
    }
}

queryXMLfunc();