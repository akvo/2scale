import Bar from "./Bar";
import Maps from "./Maps";
import Pie from "./Pie";
import TreeMap from "./TreeMap";
import SanKey from "./SanKey";
import Radar from "./Radar";
import BarStack from "./BarStack";
import BarGroup from "./BarGroup";
import axios from "axios";
import createElement from "../app";

export const generateOptions = (type, dataset, extra = {}) => {
    switch (type) {
        case "MAPS":
            return Maps(dataset, extra);
        case "PIE":
            return Pie(dataset, extra);
        case "DOUGHNUT":
            return Pie(dataset, extra, true);
        case "TREEMAP":
            return TreeMap(dataset, extra);
        case "SANKEY":
            return SanKey(dataset, extra);
        case "RADAR":
            return Radar(dataset, extra);
        case "BARSTACK":
            return BarStack(dataset, extra);
        case "BARGROUP":
            return BarGroup(dataset, extra);
        default:
            return Bar(dataset, extra);
    }
};

const generateCharts = (
    { endpoint, type, title, id, parentId, md, height },
    transform = false
) => {
    const html = (
        <div class={`col-md-${md ? md : "6"}`}>
            <div class="card">
                <div class="card-header">{title}</div>
                <div class="card-body">
                    <div
                        class="d-flex justify-content-center"
                        id={`loader-${id}`}
                    >
                        <div
                            class="spinner-border text-primary loader-spinner"
                            role="status"
                        >
                            <span class="sr-only">Loading...</span>
                        </div>
                    </div>
                    <div
                        id={id}
                        style={`height:${height ? height : 450}px`}
                    ></div>
                </div>
            </div>
        </div>
    );
    document.getElementById(parentId).appendChild(html);
    const element = document.getElementById(id);
    const myChart = echarts.init(element);
    axios
        .get(`/api/${endpoint}`)
        .then((res) => {
            setTimeout(function () {
                document.getElementById(`loader-${id}`).remove();
                let option = res.data;
                if (transform) {
                    option = transform(option);
                    console.log(option);
                }
                if (
                    !transform &&
                    (type === "BARSTACK" || type === "BARGROUP")
                ) {
                    let collections = [];
                    option.map((x) => {
                        x.childrens?.map((c) => {
                            collections.push({
                                name: c.name,
                                group: x.name,
                                stack: x.stack,
                                value: c.value,
                            });
                        });
                    });
                    option = collections;
                }
                option = generateOptions(type, option);
                myChart.setOption(option);
            }, 1000);
        })
        .catch((e) => {
            document.getElementById(`loader-${id}`).remove();
            myChart.setOption({
                title: { text: "No Data available for this request" },
            });
        });
    return true;
};

export default generateCharts;
