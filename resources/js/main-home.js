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
        <div class="row" id="first-row"></div>
        <div class="row" id="second-row"></div>
    </div>
);

const countChildren = (data) => {
    return data.map((d) => ({
        name: d.name,
        value: d.childrens.length,
    }));
};

generateCharts(
    {
        type: "PIE",
        endpoint: "flow/sectors?sum=industry,country_id&form_id=20020001",
        title: "Sector Distribution",
        id: "sector-ddistribution",
        parentId: "first-row",
    },
    countChildren
);

generateCharts({
    type: "PIE",
    endpoint: "flow/partnerships",
    title: "Number of PPPs Percountry",
    id: "ppp-per-country",
    parentId: "first-row",
});

getCharts("home/investment-tracking", "second-row", info, "12", "blue");
