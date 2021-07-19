import createElement from "./app";
import { db, storeDB } from "./dexie";
import { generateOptions } from "./chart-util";
import { CountUp } from "countup.js";
import { formatNumber, targetAndLastSync } from "./util";
import _ from "lodash";
const axios = window.axios;

/* Static */
const baseurl = $("meta[name=path]").attr("content");
const country_id = $("meta[name='country']").attr("content");
const partnership_id = $("meta[name='partnership']").attr("content");
const endpoints = [country_id, partnership_id].join("/");

const handleNotFound = () => {
    $("#loader-spinner").remove();
    $("main").append(
        <div class="row" style="margin-top: 325px;">
            <div class="col-md-12">
                <h5 class="text-center">
                    Partnership data not found.
                </h5>
            </div>
        </div>
    );
};

let targetSyncText = "";
targetAndLastSync().then(el => targetSyncText = el);

const renderTextVisual = async () => {
    await axios
        .get("/api/flow/partnership/text/" + endpoints)
        .then(res => {
            $("main").append(
                <div class="d-flex justify-content-center" id="loader-spinner">
                    <div class="spinner-border text-primary loader-spinner" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                </div>
            );
            return res;
        })
        .then(res => {
            const { title, sector, producer, abc, enterprise, link } = res.data;
            const workWith = [];
            let workWithText = "It currently works with";
            if (producer) {
                let plural = producer > 1 ? "s" : "";
                workWith.push(`${producer} producer organisation${plural}`);
            }
            if (abc) {
                let plural = abc > 1 ? "s" : "";
                workWith.push(`${abc} agri business cluster${plural}`);
            }
            if (enterprise) {
                let plural = enterprise > 1 ? "s" : "";
                workWith.push(`${enterprise} enterprise${plural}`);
            }
            if (workWith.length === 3) {
                workWithText = `${workWithText} ${workWith[0]}, ${workWith[1]} and ${workWith[2]}`;
            }
            if (workWith.length <= 2) {
                workWithText = `${workWithText} ${workWith.join(' and ')}`;
            }
            $("main").append(
                <div class="row visual" style="visibility: hidden;">
                    <div class="col-md-12">
                        { targetSyncText }
                        <h3 class="responsive font-weight-bold text-center my-4">
                            { title }
                        </h3>
                        <div class="row justify-content-center">
                            <div class="col-md-12" style="width: 90%">
                                <h5 class="text-center">
                                    { title } project belongs to the <span class="font-weight-bold">{ sector }</span> sector. <br/>
                                    {workWith.length === 0 ? "" : `${workWithText}. `}
                                    For more details please visit <a target='_blank' href={`${res.data.link}`}>project page</a>.
                                </h5>
                            </div>
                        </div>
                        <hr />
                    </div>
                </div>
            );
            return true;
        }).then(async res => {
            await renderImplementingPartner();
            return true;
        }).then(async res => {
            await renderCharts();
            return true;
        }).then(res => {
            let visuals = $(".visual");
            $("#loader-spinner").remove();
            $(".tmp-footer")[0].style.position = "relative";
            for (let index = 0; index < visuals.length; index++) {
                const element = visuals[index];
                element.style.visibility = "visible";
            }
        }).catch(err => {
            handleNotFound();
        });
};

const renderImplementingPartner = async () => {
    await axios
        .get("/api/rsr/partnership/implementing-partner/" + endpoints)
        .then(res => {
            const data = res.data;
            $("main").append(
                <div class="row visual" style="visibility: hidden;">
                    <div class="col-md-12">
                        <h3 class="responsive font-weight-bold text-center my-4">
                            Implementing Partner(s)
                        </h3>
                        <div class="row even-row justify-content-center">
                            <div class="col-md-6">
                                <div class="list-group">
                                    {
                                        data.length > 0 ? data.map((x,i) => {
                                            return (
                                                <div key={`${x.organisation_name}-${i}`} class="list-group-item list-group-item-action">
                                                    <div class="d-flex w-100 justify-content-between">
                                                        <h5 class="mb-1">{x.organisation_name}</h5>
                                                    </div>
                                                </div>
                                            )
                                        }) : <div><center>No Implementing Partner(s).</center></div>
                                    }
                                </div>
                            </div>
                        </div>
                        <hr/>
                    </div>
                </div>
            );
            return true;
        }).catch(err => {
            handleNotFound();
        });;
}

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
        let even = false;
        if (i % 2 != 0) {
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

const renderCharts = async () => {
    await axios
    .get("/api/rsr/partnership/charts/" + endpoints)
    .then((res) => {
        const data = res.data;
        $("main").append(
            <div class="visual" style="visibility: hidden;">
                <div class="row">
                    <div class="col-md-12">
                        <h3 class="responsive font-weight-bold text-center my-4">
                            Impact Charts
                        </h3>
                    </div>
                </div>
                {
                    data.length > 0 ? data.map((x, i) => {
                        return groups(x, i);
                    }) : <div style="height: 450px;"><center>No charts data.</center></div>
                }
            </div>
        );
        return { counts: counts, charts: charts };
    })
    .then((res) => {
        //generate countup
        if (res.counts.length > 0) {
            setTimeout(() => {
                res.counts.forEach((x, i) => {
                    const countUp = new CountUp(x.id, x.val, { suffix: x.suf });
                    if (!countUp.error) {
                        countUp.start();
                    }
                });
            }, 300);
        }
        //generate chart option
        if (res.charts.length > 0) {
            res.charts.forEach((x, i) => {
                const options = generateOptions(x.type, x.data);
                const myChart = echarts.init(document.getElementById(x.id));
                myChart.setOption(options);
            });
        }
        return true;
    }).catch(err => {
        handleNotFound();
    });
};

renderTextVisual();