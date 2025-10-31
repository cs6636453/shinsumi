let xml_bagQuery;

let bag = [
    "กระเป๋าผ้า",
    "กระเป๋า" // อื่นๆ
]

function bag_index(i) {
    document.getElementById("bag_category_select").innerHTML = "<img src='assets/images/loading.gif' width='30px' height='30px' alt='loading'>"
    let bagId = "bag_" + i;
    xml_bagQuery = new XMLHttpRequest();
    xml_bagQuery.onreadystatechange = getBagResult;
    let url = "scripts/category.php";
    let params = "query=" + encodeURIComponent(bag[i]);
    xml_bagQuery.open("POST", url);
    xml_bagQuery.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xml_bagQuery.send(params);
    for (let j = 0; j < bag.length; j++) {
        let bagIdF = "bag_" + j;
        document.getElementById(bagIdF).classList.remove("bag_category_active");
    }
    document.getElementById(bagId).classList.add("bag_category_active");
}

function getBagResult() {
    if (xml_bagQuery.readyState == 4 && xml_bagQuery.status == 200) {
        document.getElementById("bag_category_select").innerHTML = xml_bagQuery.responseText;
    }
}

let xml_caseQuery;

let case_ = [
    "iPhone",
    "Samsung",
    "เคส" // อื่นๆ
]

function case_index(i) {
    document.getElementById("case_category_select").innerHTML = "<img src='assets/images/loading.gif' width='30px' height='30px' alt='loading'>"
    let caseId = "case_" + i;
    xml_caseQuery = new XMLHttpRequest();
    xml_caseQuery.onreadystatechange = getCaseResult;
    let url = "scripts/category.php";
    let params = "query=" + encodeURIComponent(case_[i]);
    xml_caseQuery.open("POST", url);
    xml_caseQuery.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xml_caseQuery.send(params);
    for (let j = 0; j < case_.length; j++) {
        let caseIdF = "case_" + j;
        document.getElementById(caseIdF).classList.remove("case_category_active");
    }
    document.getElementById(caseId).classList.add("case_category_active");
}

function getCaseResult() {
    if (xml_caseQuery.readyState == 4 && xml_caseQuery.status == 200) {
        document.getElementById("case_category_select").innerHTML = xml_caseQuery.responseText;
    }
}

document.addEventListener("DOMContentLoaded", function() {
    bag_index(0);
    case_index(0);
});