import createElement from "./app";
import axios from "axios";
import { popupFormatter, visualMap } from "./chart-util/chart-style";
import { generateOptions } from "./chart-util";
import { CountUp } from "countup.js";
import { formatNumber, genCharArray, genCharPath } from "./util";
import _ from "lodash";
import countryStore from "./store/country-store.js";

const mapName = "africa";

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
        if (d.values.length === 0 && d?.actual_value) {
            let restTarget =
                (d?.target_value ? d.target_value : d.actual_value) -
                d.actual_value;
            if (restTarget < d.actual_value) {
                currentCharts = [
                    ...currentCharts,
                    {
                        id: id,
                        data: [
                            {
                                name: "Achieved",
                                value: d.actual_value,
                            },
                        ],
                        type: "DOUGHNUT",
                    },
                ];
            } else {
                currentCharts = [
                    ...currentCharts,
                    {
                        id: id,
                        data: [
                            {
                                name: "Pending",
                                value: restTarget,
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
        }
        countryStore.update((s) => {
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

const handleCountryClick = (country) => {
    const { currentState } = countryStore;
    const { maps, mapData, chartList, data } = currentState;
    const selected = data.find((x) => x.country === country);
    if (selected) {
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
            s.country = country;
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
    return;
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

const changeFilter = (path) => {
    countryStore.update((s) => {
        s.selectedPath = path;
    });
    updateFilter();
};

const createFilterList = ({ name, path, parentPath, childrens }, parent) => {
    const filterClass = childrens?.length
        ? "filter-content"
        : "filter-content full";
    if (parent) {
        return (
            <li
                class="list-group-item"
                onClick={() => changeFilter(parentPath)}
            >
                <span class="filter-parent">
                    <i class="fa fa-chevron-left"></i>
                </span>
                <span class="filter-content">{name}</span>
            </li>
        );
    }
    return (
        <li class="list-group-item">
            <span class={filterClass}>{name}</span>
            {childrens.length ? (
                <span class="filter-childs" onClick={() => changeFilter(path)}>
                    <i class="fa fa-chevron-right"></i>
                </span>
            ) : (
                ""
            )}
        </li>
    );
};

const updateFilter = () => {
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
        $("#filter-list").append(createFilterList(currentFilter, true));
        currentFilter.childrens.forEach((x) => {
            $("#filter-list").append(createFilterList(x, false));
        });
    } else {
        filters
            .filter((x) => x.parent === null)
            .forEach((x) => {
                $("#filter-list").append(createFilterList(x, false));
            });
    }
};

const createMaps = () => {
    const html = document.getElementById("maps");
    const myMap = echarts.init(html);
    countryStore.update((s) => {
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
            updateMapOptions();
        });
    }
    axios.get("/api/rsr/country-data").then((res) => {
        let filters = [];
        const baseFilter = res.data.find((x) => x.country === "Burkina Faso");
        const characters = genCharArray("a", "z");
        baseFilter.data.forEach((x, xi) => {
            x.childrens.forEach((c, ci) => {
                let cchilds = [];
                let cpath = genCharPath([xi, ci], characters);
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
                            pathName: `${c.uii} > ${d.name} > ${v.name}`,
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
                        pathName: `${c.uii} > ${d.name}`,
                        value: d?.actual_value || d?.values?.length,
                        childrens: dchilds,
                    });
                });
                filters.push({
                    name: c.uii,
                    parent: null,
                    show: false,
                    path: cpath,
                    parentPath: null,
                    pathName: null,
                    value: c?.actual_value,
                    childrens: cchilds,
                });
            });
        });
        countryStore.update((s) => {
            s.data = res.data;
            s.filters = filters;
            s.valuePath = "a";
            s.selectedPath = null;
        });
        updateFilter();
    });
};

$("main").append(
    <div class="row">
        <div class="col-md-12" id="filters"></div>
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
