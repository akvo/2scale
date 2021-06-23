import createElement from "./app";
import generateCharts, { generateOptions } from "./chart-util";
import { CountUp } from "countup.js";
import { formatNumber } from "./util";
import _ from "lodash";
const axios = window.axios;

let counts = [];
let charts = [];

const dimensions = (x, idx) => {
    return x.map((d, i) => {
        const id = `uii-chart-${i}-${idx}`;
        if (d.values.length > 0) {
            let series = [];
            d.values.map((v) => {
                let restTarget = v.target_value - v.actual_value;
                series.push({
                    group: v.name,
                    value: restTarget < 0 ? 0 : restTarget,
                    name: "Pending",
                });
                series.push({
                    group: v.name,
                    value: v.actual_value,
                    name: "Achieved",
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
                        name: "Pending",
                        value: d.target_value - d.actual_value,
                    },
                    {
                        name: "Achieved",
                        value: d.actual_value,
                    },
                ],
                type: "DOUGHNUT",
            });
        }
        return (
            <div class={`col-md-${x.length > 1 ? "6" : "12"} uii-charts`}>
                {d.name}
                <div
                    id={id}
                    style={`height:${d?.height ? d.height : "450px"}`}
                ></div>
            </div>
        );
    });
};

const uui = (x, idx) => {
    return x.childrens.map((c, i) => {
        let even = false;
        if (i % 2 == 0) {
            even = true;
        }
        let target = c.target_text || "";
        target = target.split("##").map((t) => {
            if (t === "number") {
                return (
                    <span style="font-weight:bold;color:#a43332;">
                        {formatNumber(c.target_value)}
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
            id: `achieved-${idx}-${i}`,
            val: c.actual_value,
            suf: "",
        });
        const dim = c.dimensions?.length
            ? dimensions(c.dimensions, `${idx}-${i}`)
            : dimensions(
                  [
                      {
                          name: "",
                          target_value: c.target_value,
                          actual_value: c.actual_value,
                          values: [],
                          height: "200px",
                      },
                  ],
                  `${idx}-${i}`
              );
        return (
            <div class="col-md-12">
                <div class={`row ${even ? "even-row" : ""}`}>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body">
                                <div
                                    class="uii-col uii-percentage"
                                    id={`percentage-${idx}-${i}`}
                                >
                                    {percentage ? 0 : " - "}
                                </div>
                                <div class="uii-col uii-detail">
                                    <span style="font-weight:bold;">
                                        ACHIEVED:{" "}
                                    </span>
                                    <span
                                        style="font-weight:bold;color:#a43332;"
                                        id={`achieved-${idx}-${i}`}
                                    >
                                        0
                                    </span>
                                    <br />
                                    <span style="font-weight:bold;">
                                        TARGET:{" "}
                                    </span>
                                    {target.length > 1 ? target : " - "}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-8">
                        {c.dimensions?.length ? (
                            <div class="row">{dim}</div>
                        ) : (
                            <div class="row">
                                <div class="col-md-6">{dim}</div>
                            </div>
                        )}
                    </div>
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

const reduceDate = (current, max, result = []) => {
    // JS .getMonth() starting from 0 so we need to + by 1
    if (current < max) {
        result.push(`${current.getFullYear()}-${current.getMonth() + 1}`);
        reduceDate(
            new Date(current.setMonth(current.getMonth() + 1)),
            max,
            result
        );
    }
    return result;
};

const increments = (data) => {
    let monthList = [];
    data.map((x) =>
        x.childrens.map((m) => {
            if (m.name.length > 5) {
                monthList.push({
                    year: Number(m.name.split("-")[0]),
                    month: Number(m.name.split("-")[1]),
                });
            }
        })
    );
    monthList = _.sortBy(monthList, ["year", "month"]);
    let startDate = monthList[0];
    let endDate = monthList[monthList.length - 1];
    startDate = new Date(`${startDate.year}-${startDate.month}-01`);
    endDate = new Date(`${endDate.year}-${endDate.month}-01`);
    monthList = reduceDate(startDate, endDate);
    let flattenData = [];
    data = data.map((x) => {
        let newData = [];
        monthList.map((m) => {
            let value = x.childrens.find((x) => x.name === m);
            if (value) {
                newData.push({
                    name: m,
                    value: newData.length
                        ? newData[newData.length - 1].value + value.value
                        : value.value,
                });
            } else {
                newData.push({
                    name: m,
                    value: newData.length
                        ? newData[newData.length - 1].value
                        : 0,
                });
            }
        });
        newData.map((n) => flattenData.push({ country: x.name, ...n }));
        return { ...x, childrens: newData };
    });
    data = _.chain(flattenData)
        .groupBy("name")
        .map((x, i) => ({
            name: i,
            childrens: x.map((c) => ({ name: c.country, value: c.value })),
        }))
        .value();
    return data;
};

// Page Title
$("main").append(
    <div>
        <div class="row" id="zero-row">
            <div class="col-md-12">
                <h2 class="responsive font-weight-bold text-center my-4">
                    Reaching Targets
                </h2>
            </div>
        </div>
        <hr />
    </div>
);

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
        return true;
    })
    .then((x) => {
        $("main").append(
            <div class="row">
                <div class="col-md-12">
                    <h3 class="responsive font-weight-bold text-center my-4">
                        Target audiences reached with program activities
                    </h3>
                    <div id="first-row"></div>
                    <div id="second-row"></div>
                    <hr />
                </div>
            </div>
        );
        generateCharts({
            type: "BARGROUP",
            endpoint: "flow/rnr-gender?sum=country_id,gender+age",
            title: "",
            id: "number-of-farmer-stack",
            md: 12,
            height: 600,
            parentId: "first-row",
        });
        generateCharts(
            {
                type: "LINESTACK",
                endpoint: "flow/rnr-gender?sum=country_id,year_month",
                title: "",
                id: "number-of-farmer-stack-monthly",
                md: 12,
                height: 600,
                parentId: "second-row",
            },
            increments
        );
        return true;
    });
