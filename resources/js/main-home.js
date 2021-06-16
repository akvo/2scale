const axios = window.axios;
import { getMaps, getCharts } from "./charts.js";
import _ from "lodash";

const info = {
    head: "Header Lorem Ipsum",
    content: "Lorem Ipsum Dolor Sit Amet for Footer",
};

axios.get("/api/flow/rnr-gender").then((res) => {
    const data = res.data;
    console.log(_.find(data[0], _.matchesProperty("categories")));
});

getMaps("maps", "home/map/partnership");

/* First Row */
$("main").append("<div class='row' id='first-row'></div>");
/* Second Row */
$("main").append("<hr/><div class='row' id='second-row'></div>");

getCharts("home/sector-distribution", "first-row", info, "6", "blue");
getCharts(
    "home/partnership-per-country",
    "first-row",
    info,
    "6",
    "morpheus-den"
);

getCharts("home/investment-tracking", "second-row", info, "12", "blue");
