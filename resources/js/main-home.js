import createElement from "./app";
import generateCharts from "./chart-util";
import { getMaps, getCharts } from "./charts";
const axios = window.axios;

const info = {
    head: "Header Lorem Ipsum",
    content: "Lorem Ipsum Dolor Sit Amet for Footer",
};

// getMaps("maps", "home/map/partnership");

/* First Row */
$("main").append(
    <div>
        <div class="row" id="test-row"></div>
        <div class="row" id="first-row"></div>
        <div class="row" id="second-row"></div>
    </div>
);

generateCharts({
    type: "PIE",
    endpoint: "flow/sectors?sum=country_id&form_id=20020001",
    title: "Number of PPPs Percountry",
    id: "ppp-per-country",
    parentId: "test-row",
});
getCharts("home/sector-distribution", "first-row", info, "6", "blue");
getCharts(
    "home/partnership-per-country",
    "first-row",
    info,
    "6",
    "morpheus-den"
);
getCharts("home/investment-tracking", "second-row", info, "12", "blue");
