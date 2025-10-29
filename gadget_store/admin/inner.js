let avg = ["day", "week", "month", "year"];

function avgClick(i) {
    for (let j = 0; j < avg.length; j++) {
        document.getElementById(avg[j]).classList.remove("active");
    }
    document.getElementById(avg[i]).classList.add("active");
}