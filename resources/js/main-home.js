import createElement from "./app";
import generateCharts from "./chart-util";
import { getMaps, getCharts } from "./charts";
const axios = window.axios;

// getMaps("maps", "home/map/partnership");

/* First Row */
$("main").append(
    <div>
        <div class="row" id="maps"></div>
        <div class="graphic">
            <img src="/images/2scale-infographic.svg" class="img img-fluid" />
        </div>
        <div class="row" id="first-row">
            <div
                class="col-md-12"
                style="text-align: center; font-size:24px; margin-top: 30px; margin-bottom: 30px;"
            >
                2SCALE partners with business champions to leverage food
                nutrition and security:
            </div>
        </div>
        <hr />
        <div class="row" id="second-row"></div>
        <footer class="footer">
            <div class="col-md-12">
                <div class="text-center">
                    <a href="https://2scale.org">2SCALE</a> |{" "}
                    <i class="fa fa-envelope"></i> info@2scale.org
                </div>
            </div>
        </footer>
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
        type: "DOUGHNUT",
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

getMaps("maps", "home/map");
getCharts("home/investment-tracking", "second-row", "12", null, 375);
