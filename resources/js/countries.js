import createElement from "./app";
import axios from "axios";
import { popupFormatter, visualMap } from "./chart-util/chart-style";
import { generateOptions } from "./chart-util";
import { CountUp } from "countup.js";
import { formatNumber } from "./util";
import _ from "lodash";
import dataStore from "./store";

const mapName = "africa";

const testArray = ["Kenya", "Uganda", "Niger", "Nigeria"];
const testRandom = () => {
    const rand = Math.floor(Math.random() * testArray.length);
    return testArray[rand];
};

const dimensions = (x, idx) => {
    return x.map((d, i) => {
        const { currentState } = dataStore;
        let currentCharts = currentState.charts;
        const id = `uii-chart-${i}-${idx}`;
        if (d.values.length > 0) {
            let series = [];
            d.values.map((v) => {
                let restTarget = v.target_value - v.actual_value;
                series.push({
                    group: v.name,
                    value: restTarget < 1 ? 0 : restTarget,
                    name: "Pending",
                });
                series.push({
                    group: v.name,
                    value: v.actual_value,
                    name: "Achieved",
                });
                return v.name;
            });
            currentCharts = [
                ...currentCharts,
                {
                    id: id,
                    data: series,
                    type: "BARSTACK",
                },
            ];
        }
        if (d.values.length === 0 && d?.actual_value) {
            let restTarget =
                (d?.target_value ? d.target_value : d.actual_value) -
                d.actual_value;
            currentCharts = [
                ...currentCharts,
                {
                    id: id,
                    data: [
                        {
                            name: "Pending",
                            value: restTarget || 1,
                        },
                        {
                            name: "Achieved",
                            value: d.actual_value,
                        },
                    ],
                    type: "DOUGHNUT",
                },
            ];
        }
        dataStore.update((s) => {
            s.charts = currentCharts;
        });
        return (
            <div class={`col-md-${x.length > 1 ? "6" : "12"} uii-charts`}>
                {d.name.length > 0 ? <div class="uii-title">{d.name}</div> : ""}
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
        const { currentState } = dataStore;
        let currentCounts = dataStore.currentState.counts;
        let even = false;
        if (i % 2 == 0) {
            even = true;
        }
        let target = c.target_text || "";
        target = target.split("##").map((t) => {
            if (t === "number") {
                return (
                    <span style="font-weight:bold;color:#a43332;">
                        {c.target_value ? formatNumber(c.target_value) : "-"}
                    </span>
                );
            }
            return t;
        });
        const percentage =
            target.length > 1
                ? (
                      (c.actual_value /
                          (c.target_value < c.actual_value
                              ? c.actual_value
                              : c.target_value)) *
                      100
                  ).toFixed(3)
                : null;
        if (target.length > 1) {
            currentCounts = [
                ...currentCounts,
                {
                    id: `percentage-${idx}-${i}`,
                    val: percentage,
                    suf: "%",
                },
            ];
        }
        currentCounts = [
            ...currentCounts,
            {
                id: `achieved-${idx}-${i}`,
                val: c.actual_value,
                suf: "",
            },
        ];
        dataStore.update((s) => {
            s.counts = currentCounts;
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
                            <div class="row chart">{dim}</div>
                        ) : (
                            <div class="row chart">
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

const updateStates = () => {
    const { currentState } = dataStore;
    setTimeout(() => {
        currentState.counts.forEach((x, i) => {
            const countUp = new CountUp(x.id, x.val, { suffix: x.suf });
            if (!countUp.error) {
                countUp.start();
            }
        });
    }, 300);
    currentState.charts.forEach((x, i) => {
        const options = generateOptions(x.type, x.data);
        const html = document.getElementById(x.id);
        let myChart = echarts.getInstanceByDom(html);
        if (!myChart) {
            myChart = echarts.init(html);
        }
        myChart.setOption(options);
    });
};

const handleCountryClick = (country) => {
    echarts.util.each((x, i) => {
        console.log(x, i);
    });
    const { currentState } = dataStore;
    const data = currentState.data.find((x) => x.country === country);
    if (data) {
        dataStore.update((s) => {
            s.country = country;
            s.charts = [];
            s.counts = [];
        });
        $("#display").html("");
        $("#display").append(
            data.data.map((x, i) => {
                return groups(x, i);
            })
        );
        updateStates();
    }
    updateMapOptions();
    return;
};

const updateMapOptions = () => {
    const { currentState } = dataStore;
    const { maps, mapData } = currentState;
    const options = {
        tooltip: {
            trigger: "item",
            showDelay: 0,
            transitionDuration: 0.2,
            formatter: popupFormatter,
        },
        visualMap: visualMap,
        series: [
            {
                type: "map",
                zoom: 1,
                room: true,
                aspectScale: 1,
                map: mapName,
                data: mapData,
            },
        ],
    };
    maps.setOption(options);
    maps.on("click", "series", function (x) {
        handleCountryClick(x.name);
    });
};

const createMaps = () => {
    const html = document.getElementById("maps");
    const myMap = echarts.init(html);
    dataStore.update((s) => {
        s.maps = myMap;
    });
    if (localStorage.getItem("africa-map")) {
        const mapData = JSON.parse(localStorage.getItem("africa-map"));
        echarts.registerMap(mapName, mapData);
        updateMapOptions();
    } else {
        axios.get("/json/africa.geojson").then((res) => {
            localStorage.setItem("africa-map", JSON.stringify(res.data));
            echarts.registerMap(mapName, res.data);
        });
    }
    axios.get("/api/rsr/country-data").then((res) => {
        dataStore.update((s) => {
            s.data = res.data;
        });
    });
};

$("main").append(
    <div class="row">
        <div class="col-md-12">
            <h2 class="responsive font-weight-bold text-center my-4">
                Reaching Targets
            </h2>
            <div id="maps" style="height:700px;"></div>
        </div>
        <div class="col-md-12" id="display"></div>
    </div>
);

createMaps();
