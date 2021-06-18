import createElement from "./app";
import { generateOptions } from "./chart-util";
import { CountUp } from "countup.js";
const axios = window.axios;

let counts = [];
let charts = [];

const dimensions = (x, idx) => {
    return x.map((d, i) => {
        let series = [];
        const id = `uii-chart-${i}-${idx}`;
        if (d.values.length > 0) {
            d.values.map((v) => {
                let restTarget = v.target_value - v.actual_value;
                series.push({
                    group: v.name,
                    value: restTarget < 0 ? 0 : restTarget,
                    name: "target value",
                });
                series.push({
                    group: v.name,
                    value: v.actual_value,
                    name: "actual value",
                });
                return v.name;
            });
            charts.push({
                id: id,
                data: series,
                type: "BARSTACK",
            });
        }
        if (d.values.length === 0 && d?.target_value && d?.actual_value) {
            charts.push({
                id: id,
                data: [
                    {
                        name: "target value",
                        value: d.target_value - d.actual_value,
                    },
                    {
                        name: "actual value",
                        value: d.actual_value,
                    },
                ],
                type: "DOUGHNUT",
            });
        }
        return (
            <div class={`col-md-${x.length > 1 ? "6" : "12"} uii-charts`}>
                {d.name}
                <div id={id} style="height:450px"></div>
            </div>
        );
    });
};

const uui = (x, idx) => {
    return x.childrens.map((c, i) => {
        let target = c.target_text || "";
        target = target.split("##").map((t) => {
            if (t === "number") {
                return (
                    <span style="font-weight:bold;color:#a43332;">
                        {c.target_value}
                    </span>
                );
            }
            return t;
        });
        const percentage =
            target.length > 1
                ? ((c.actual_value / c.target_value) * 100).toFixed(3)
                : null;
        if (target.length > 1) {
            counts.push({
                id: `percentage-${idx}-${i}`,
                val: percentage,
                suf: "%",
            });
        }
        counts.push({
            id: `achived-${idx}-${i}`,
            val: c.actual_value,
            suf: "",
        });
        const dim = c.dimensions?.length
            ? dimensions(c.dimensions, `${idx}-${i}`)
            : false;
        return (
            <div class={`card col-md-${c.dimensions?.length ? 12 : 6}`}>
                <div class="card-body">
                    <div
                        class="uii-col uii-percentage"
                        id={`percentage-${idx}-${i}`}
                    >
                        {percentage ? 0 : " - "}
                    </div>
                    <div class="uii-col uii-detail">
                        <span style="font-weight:bold;">ACHIEVED: </span>
                        <span
                            style="font-weight:bold;color:#a43332;"
                            id={`achived-${idx}-${i}`}
                        >
                            0
                        </span>
                        <br />
                        <span style="font-weight:bold;">TARGET: </span>
                        {target.length > 1 ? target : " - "}
                    </div>
                    {dim ? <div class="row">{dim}</div> : ""}
                </div>
            </div>
        );
    });
};

const groups = (x, i) => {
    return (
        <div class="row">
            <div class="col-md-12">
                <h3 class="responsive font-weight-bold text-center my-4">
                    {x.group}
                </h3>
                <div class="row">{uui(x, i)}</div>
                <hr />
            </div>
        </div>
    );
};

axios
    .get("/api/rsr/impact-reach/uii")
    .then((res) => {
        const data = res.data;
        $("main").append(
            data.map((x, i) => {
                return groups(x, i);
            })
        );
        return { counts: counts, charts: charts };
    })
    .then((res) => {
        //generate countup
        setTimeout(() => {
            res.counts.forEach((x, i) => {
                const countUp = new CountUp(x.id, x.val, { suffix: x.suf });
                if (!countUp.error) {
                    countUp.start();
                }
            });
        }, 300);
        //generate chart option
        res.charts.forEach((x, i) => {
            const options = generateOptions(x.type, x.data);
            const myChart = echarts.init(document.getElementById(x.id));
            myChart.setOption(options);
        });
    });
