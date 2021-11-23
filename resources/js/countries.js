import createElement from "./app";
import axios from "axios";
import { visualMap } from "./chart-util/chart-style";
import { generateOptions } from "./chart-util";
import { CountUp } from "countup.js";
import {
    formatNumber,
    genCharArray,
    toTitleCase,
    genCharPath,
    targetAndLastSync,
} from "./util";
import sumBy from "lodash/sumBy";
import maxBy from "lodash/maxBy";
import minBy from "lodash/minBy";
import chunk from "lodash/chunk";
import countryStore from "./store/country-store.js";
import uniqBy from "lodash/uniqBy";

const mapName = "africa";

const popupFormatter = (params) => {
    const additionalText = "</br>Click country to show</br>details below";
    if (params?.data?.text) {
        let text = params.data.text.replace(
            "##number##",
            "<b>" + formatNumber(params.value) + "</b>"
        );
        text = text.replace(".", "").split(" ");
        text = chunk(text, 3);
        text = text.map((x) => x.join(" "));
        return text.join("</br>") + "</br>" + additionalText;
    }
    var value = (params.value + "").split(".");
    value = value[0].replace(/(\d{1,3})(?=(?:\d{3})+(?!\d))/g, "$1,");
    if (Number.isNaN(params.value)) {
        return;
    }
    return params.name + ": " + value + "</br>" + additionalText;
};

const dimensions = (x, idx) => {
    return x.map((d, i) => {
        const { currentState } = countryStore;
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
        if (d?.values?.length === 0) {
            let actualValue = d?.actual_value ? d.actual_value : 0;
            let restTarget =
                (d?.target_value ? d.target_value : actualValue) - actualValue;
            const dCharts =
                restTarget < actualValue
                    ? [{ name: "Achieved", value: actualValue }]
                    : [
                          { name: "Achieved", value: actualValue },
                          { name: "Pending", value: restTarget },
                      ];
            currentCharts = [
                ...currentCharts,
                {
                    id: id,
                    data: dCharts,
                    type: "DOUGHNUT",
                },
            ];
        }
        countryStore.update((s) => {
            s.charts = currentCharts;
        });
        return (
            <div class={`col-md-${x.length > 1 ? "6" : "12"} uii-charts`}>
                {d.name.length > 0 ? <div class="uii-title">{d.name}</div> : ""}
                <div
                    id={id}
                    style={`height:${
                        d?.height
                            ? d.height
                            : d.values.length
                            ? "450px"
                            : "200px"
                    }`}
                ></div>
            </div>
        );
    });
};

const uii = (x, idx) => {
    return x.childrens.map((c, i) => {
        const { currentState } = countryStore;
        let currentCounts = countryStore.currentState.counts;
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
            target.length > 1 && c.target_value
                ? ((c.actual_value / c.target_value) * 100).toFixed(3)
                : null;
        if (target.length > 1 && c.target_value) {
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
        // automate calculation
        let automateCalculation = 0;
        if (c?.automate_calculation) {
            let temp = c.automate_calculation?.map((it, itx) => {
                const value = it.value.toFixed(3);
                let text = it.text.split("##").map((t) => {
                    if (t === "number") {
                        return (
                            <span
                                style="font-weight:bold;color:#a43332; margin-left: 4px;"
                                id={`automate-calculation-item-${idx}-${i}-${itx}`}
                            >
                                {value}
                            </span>
                        );
                    }
                    return t;
                });
                currentCounts = [
                    ...currentCounts,
                    {
                        id: `automate-calculation-item-${idx}-${i}-${itx}`,
                        val: value,
                        suf: "%",
                    },
                ];
                return text;
            });
            automateCalculation = (
                <span
                    style="margin-left: 4px"
                    id={`automate-calculation-${idx}-${i}`}
                >
                    | {temp}
                </span>
            );
        }
        // eol automate calculation
        countryStore.update((s) => {
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
                                    {/* show automate calculation */}
                                    {automateCalculation
                                        ? automateCalculation
                                        : ""}
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
        <div class="row uii-row">
            <div class="col-md-12">
                <h3 class="responsive font-weight-bold text-center my-4">
                    {x.group}
                </h3>
                <div class="row">{uii(x, i)}</div>
                <hr />
            </div>
        </div>
    );
};

const updateStates = () => {
    const { currentState } = countryStore;
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
        const myChart = echarts.init(html);
        myChart.setOption(options);
        const newCharts = [
            ...countryStore.currentState.chartList,
            { id: x.id, chart: myChart },
        ];
        countryStore.update((s) => {
            s.chartList = newCharts;
        });
    });
};

const updateMapOptions = () => {
    const { currentState } = countryStore;
    const { maps, mapData } = currentState;
    const options = {
        tooltip: {
            trigger: "item",
            showDelay: 0,
            transitionDuration: 0.2,
            formatter: popupFormatter,
            textStyle: {
                width: 100,
                fontFamily: "MarkPro",
            },
        },
        series: [
            {
                type: "map",
                zoom: 1,
                room: true,
                aspectScale: 1,
                map: mapName,
                data: mapData,
                emphasis: {
                    itemStyle: {
                        areaColor: "#609ca8",
                        shadowColor: "rgba(0, 0, 0, 0.5)",
                        shadowBlur: 10,
                    },
                    label: {
                        fontFamily: "MarkPro",
                        fontWeight: "bold",
                        color: "#fff",
                        textShadowColor: "rgba(0, 0, 0, 0.5)",
                        textSthadowBlur: 10,
                    },
                },
            },
        ],
    };
    maps.setOption(options);
};

const handleCountryClick = (c) => {
    const { currentState } = countryStore;
    const { chartList, data, maps } = currentState;
    const selected = data.find((x) => x.country === c.name);
    if (selected) {
        $("#country-container").empty();
        $("#country-container").append(
            <div>
                <div id="country-selected">Country: {c.name}</div>
                <hr />
            </div>
        );
        $(".uii-row").remove();
        if (chartList.length) {
            chartList.forEach((x) => {
                x.chart.dispose();
            });
            countryStore.update((s) => {
                s.chartList = [];
            });
        }
        countryStore.update((s) => {
            s.country = c.name;
            s.charts = [];
            s.counts = [];
        });
        $("#display").append(
            selected.data.map((x, i) => {
                return groups(x, i);
            })
        );
        updateStates();
    }
};

const changeFilterPath = (path) => {
    countryStore.update((s) => {
        s.selectedPath = path;
    });
    updateFilter();
};

const changeFilter = (path, value) => {
    const { currentState } = countryStore;
    const { filters, data, valuePath, maps, mapData } = currentState;
    let fl = filters.find((x) => x.path === path);
    fl = fl.pathName.split("|");
    const res = data.map((dt) => {
        let d = {};
        let i = 0;
        let target_text = "";
        let target_values = 0;
        do {
            if (i === 0) {
                d = dt.data.find((x) => x.group === fl[i]);
            }
            if (i === 1) {
                d = d?.childrens.find((x) => x.uii === fl[i]);
                target_text = d.target_text;
            }
            if (i === 2) {
                d = d?.dimensions.find((x) => x.name === fl[i]);
            }
            if (i === 3) {
                d = d?.values.find((x) => x.name === fl[i]);
            }
            i += 1;
        } while (i < fl.length);
        let value = d?.actual_value ? d.actual_value : 0;
        if (fl.length === 3 && d?.values) {
            value = sumBy(d.values, "actual_value");
        }
        return {
            name: dt.country,
            value: value,
            text: target_text,
            data: dt,
        };
    });
    const text = res[0].text.replace(
        "##number##",
        "<b>" + formatNumber(sumBy(res, "value")) + "</b>"
    );
    $("#subtitle").empty();
    $("#subtitle").append(text);
    const options = {
        tooltip: {
            trigger: "item",
            showDelay: 0,
            transitionDuration: 0.2,
            formatter: popupFormatter,
            textStyle: {
                width: 100,
                fontFamily: "MarkPro",
            },
        },
        visualMap: {
            ...visualMap,
            max: maxBy(res, "value").value,
            min: minBy(res, "value").value,
        },
        series: [
            {
                type: "map",
                zoom: 1,
                room: true,
                aspectScale: 1,
                map: mapName,
                data: res,
                emphasis: {
                    itemStyle: {
                        areaColor: "#609ca8",
                        shadowColor: "rgba(0, 0, 0, 0.5)",
                        shadowBlur: 10,
                    },
                    label: {
                        fontFamily: "MarkPro",
                        fontWeight: "bold",
                        color: "#fff",
                    },
                },
            },
        ],
    };
    maps.setOption(options);
    if (value) {
        countryStore.update((s) => {
            s.valuePath = path;
        });
        updateFilter();
    }
};

const createFilterList = (
    { name, text, bold, path, parentPath, childrens, value },
    parent,
    valuePath
) => {
    let filterClass = childrens?.length
        ? "filter-content"
        : "filter-content full";
    if (valuePath === path) {
        filterClass = `${filterClass} active`;
    }
    if (bold) {
        text = text
            .replace(bold, "**[b]**")
            .split("**")
            .map((x) => {
                if (x === "[b]") {
                    return <b>{bold}</b>;
                }
                return x;
            });
    }
    if (parent) {
        return (
            <li
                class="list-group-item"
                onClick={() => changeFilterPath(parentPath)}
            >
                <span class="filter-parent">
                    <i class="fa fa-chevron-left"></i>
                </span>
                <span class="filter-content">
                    {text ? (bold ? text.map((x) => x) : text) : name}
                </span>
            </li>
        );
    }
    return (
        <li class="list-group-item">
            <span class={filterClass} onClick={() => changeFilter(path, value)}>
                {text ? (bold ? text.map((x) => x) : text) : name}
            </span>
            {childrens.length ? (
                <span
                    class="filter-childs"
                    onClick={() => changeFilterPath(path)}
                >
                    <i class="fa fa-chevron-right"></i>
                </span>
            ) : (
                ""
            )}
        </li>
    );
};

const updateFilter = (init = false) => {
    $("#filters").empty();
    $("#filters").append(
        <div class="card" style="width: 18rem;">
            <ul class="list-group list-group-flush" id="filter-list"></ul>
        </div>
    );
    const { currentState } = countryStore;
    const { filters, valuePath, selectedPath } = currentState;
    let currentFilter = filters.find((x) => x.path === selectedPath);
    if (currentFilter) {
        currentFilter = {
            ...currentFilter,
            childrens: filters.filter((x) =>
                currentFilter.childrens.includes(x.path)
            ),
        };
        $("#filter-list").append(
            createFilterList(currentFilter, true, valuePath)
        );
        currentFilter.childrens.forEach((x) => {
            $("#filter-list").append(createFilterList(x, false, valuePath));
        });
    } else {
        filters
            .filter((x) => x.parent === null)
            .forEach((x) => {
                $("#filter-list").append(createFilterList(x, false, valuePath));
            });
    }
    if (init) {
        changeFilter("a-a", true);
    }
};

const fetchData = () => {
    axios.get("/api/rsr/country-data").then((res) => {
        let filters = [];
        const baseFilter = res.data.find((x) => x.country === "Burkina Faso");
        const characters = genCharArray("a", "z");
        baseFilter.data.forEach((x, xi) => {
            x.childrens.forEach((c, ci) => {
                let ctext = c?.tab ? c.tab.text : c.target_text;
                let cchilds = [];
                let cpath = genCharPath([xi, ci], characters);
                /*
                c?.dimensions?.forEach((d, di) => {
                    let dchilds = [];
                    let dpath = genCharPath([xi, ci, di], characters);
                    cchilds.push(dpath);
                    d?.values?.forEach((v, vi) => {
                        vi = vi + 1;
                        let vpath = genCharPath([xi, ci, di, vi], characters);
                        dchilds.push(vpath);
                        filters.push({
                            name: v.name,
                            parent: d.name,
                            show: false,
                            path: vpath,
                            parentPath: dpath,
                            pathName: `${x.group}|${c.uii}|${d.name}|${v.name}`,
                            value: true,
                            childrens: false,
                        });
                    });
                    filters.push({
                        name: d.name,
                        parent: c.uii,
                        show: false,
                        path: dpath,
                        parentPath: cpath,
                        pathName: `${x.group}|${c.uii}|${d.name}`,
                        value: d?.actual_value || d?.values?.length,
                        childrens: dchilds,
                    });
                });
                */
                filters.push({
                    name: c.uii,
                    text: ctext,
                    bold: c?.tab?.bold,
                    parent: null,
                    show: false,
                    path: cpath,
                    parentPath: null,
                    pathName: `${x.group}|${c.uii}`,
                    value: c?.actual_value,
                    childrens: cchilds,
                });
            });
        });
        countryStore.update((s) => {
            s.data = res.data;
            s.filters = uniqBy(filters, "name");
            s.valuePath = "a";
            s.selectedPath = null;
        });
        updateMapOptions();
        updateFilter(true);
        $("#loader-spinner").remove();
    });
};

const createMaps = () => {
    const html = document.getElementById("maps");
    const myMap = echarts.init(html);
    myMap.on("click", "series", function (x) {
        handleCountryClick(x);
    });
    countryStore.update((s) => {
        s.maps = myMap;
    });
    if (localStorage.getItem("africa-map")) {
        const mapData = JSON.parse(localStorage.getItem("africa-map"));
        echarts.registerMap(mapName, mapData);
        fetchData();
    } else {
        axios.get("/json/africa.geojson").then((res) => {
            localStorage.setItem("africa-map", JSON.stringify(res.data));
            echarts.registerMap(mapName, res.data);
            fetchData();
        });
    }
};

targetAndLastSync().then((el) => {
    $("#last-sync-temp").append(el);
});

$("main").append(
    <div class="row">
        <div class="col-md-12" id="filters"></div>
        <div class="col-md-12 main-page">
            <div id="last-sync-temp"></div>
            <h2 class="responsive font-weight-bold text-center my-4">
                Meeting Targets
            </h2>
            <h3 id="subtitle"></h3>
            <div id="maps" style="height:750px;"></div>
            <div class="map-notation">Click country to show details</div>
        </div>
        <div id="country-container"></div>
        <div class="col-md-12" id="display"></div>
    </div>
);

$("main").append(
    <div class="d-flex justify-content-center" id="loader-spinner">
        <div class="spinner-border text-primary loader-spinner" role="status">
            <span class="sr-only">Loading...</span>
        </div>
    </div>
);

createMaps();
