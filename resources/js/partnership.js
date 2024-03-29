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
    const text =
        country_id == 0 && partnership_id == 0
            ? "Select country and partnership for information about partnerships in 2SCALE."
            : "Partnership data not found.";
    $("main").append(
        <div class="row" style="margin-top: 30vh;">
            <div class="col-md-12">
                <h5 class="text-center">{text}</h5>
            </div>
        </div>
    );
};

let targetSyncText = "";
targetAndLastSync().then((el) => (targetSyncText = el));

const renderTextVisual = async () => {
    await axios
        .get("/api/flow/partnership/text/" + endpoints)
        .then((res) => {
            $("main").append(
                <div class="d-flex justify-content-center" id="loader-spinner">
                    <div
                        class="spinner-border text-primary loader-spinner"
                        role="status"
                    >
                        <span class="sr-only">Loading...</span>
                    </div>
                </div>
            );
            return res;
        })
        .then((res) => {
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
                workWithText = `${workWithText} ${workWith.join(" and ")}`;
            }
            $("main").append(
                <div class="row visual" style="visibility: hidden;">
                    <div class="col-md-12">
                        {targetSyncText}
                        <h3 class="responsive font-weight-bold text-center my-4">
                            {title}
                        </h3>
                        <div class="row justify-content-center">
                            <div class="col-md-12" style="width: 90%">
                                <h5 class="text-center">
                                    {title} project belongs to the{" "}
                                    <span class="font-weight-bold">
                                        {sector}
                                    </span>{" "}
                                    sector. <br />
                                    {workWith.length === 0
                                        ? ""
                                        : `${workWithText}. `}
                                </h5>
                            </div>
                        </div>
                        <hr />
                    </div>
                </div>
            );
            return res.data.link;
        })
        .then(async (link) => {
            await renderImplementingPartner();
            return link;
        })
        .then(async (link) => {
            await renderCharts();
            return link;
        })
        // comment for now, to remove the link that directs a user/visitor to RSR
        // .then((link) => {
        //     $("main").append(
        //         <div
        //             class="row visual justify-content-center more-information-text"
        //             style="visibility: hidden;"
        //         >
        //             <div className="col-md-12">
        //                 For more information about this partnership, visit the
        //                 partnership page
        //                 <a
        //                     target="_blank"
        //                     href={`${link}`}
        //                     style="margin-left: 4px;"
        //                 >
        //                     here
        //                 </a>
        //                 .
        //             </div>
        //         </div>
        //     );
        // })
        .then((res) => {
            let visuals = $(".visual");
            $("#loader-spinner").remove();
            $(".tmp-footer")[0].style.position = "relative";
            for (let index = 0; index < visuals.length; index++) {
                const element = visuals[index];
                element.style.visibility = "visible";
            }
        })
        .catch((err) => {
            handleNotFound();
        });
};

const renderImplementingPartner = async () => {
    await axios
        .get("/api/rsr/partnership/implementing-partner/" + endpoints)
        .then((res) => {
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
                                    {data.length > 0 ? (
                                        data.map((x, i) => {
                                            return (
                                                <div
                                                    key={`${x.organisation_name}-${i}`}
                                                    class="list-group-item list-group-item-action"
                                                >
                                                    <div class="d-flex w-100 justify-content-between">
                                                        <h5 class="mb-1">
                                                            {
                                                                x.organisation_name
                                                            }
                                                        </h5>
                                                    </div>
                                                </div>
                                            );
                                        })
                                    ) : (
                                        <div>
                                            <center>
                                                No Implementing Partner(s).
                                            </center>
                                        </div>
                                    )}
                                </div>
                            </div>
                        </div>
                        <hr />
                    </div>
                </div>
            );
            return true;
        })
        .catch((err) => {
            handleNotFound();
        });
};

let counts = [];
let charts = [];

const dimensions = (x, idx, chartTitle = null) => {
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
                {chartTitle ? (
                    <div class="uii-title">{chartTitle}</div>
                ) : d.name.length > 0 ? (
                    <div class="uii-title">{d.name}</div>
                ) : (
                    ""
                )}
                <div
                    id={id}
                    style={`height:${
                        d?.height
                            ? d.height
                            : d.values.length
                            ? "450px"
                            : "450px"
                    }`}
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
                counts.push({
                    id: `automate-calculation-item-${idx}-${i}-${itx}`,
                    val: value,
                    suf: "%",
                });
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
        const dim = c.dimensions?.length
            ? dimensions(c.dimensions, `${idx}-${i}`, c?.chart_title)
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
                  `${idx}-${i}`,
                  c?.chart_title
              );
        // custom render for UII-8
        const isUii8 = c?.uii?.toLowerCase()?.includes("uii8");
        return (
            <div class={`${isUii8 ? "col-md-6 uii-8-group" : "col-md-12"}`}>
                <div class={`row ${even && !isUii8 ? "even-row" : "odd-row"}`}>
                    <div class={`${isUii8 ? "col-md-12" : "col-md-4"}`}>
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
                    <div class={`${isUii8 ? "col-md-12" : "col-md-8"}`}>
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

const groups = (x, i, dataLength) => {
    return (
        <div class="row">
            <div class="col-md-12">
                <h3 class="responsive font-weight-bold text-center my-4">
                    {x.group}
                </h3>
                <div class="row">{uui(x, i)}</div>
                {/* {i === dataLength - 1 ? <hr /> : ""} */}
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
                    {data.length > 0 ? (
                        data.map((x, i) => {
                            return groups(x, i, data?.length);
                        })
                    ) : (
                        <div style="height: 450px;">
                            <center>No charts data.</center>
                        </div>
                    )}
                </div>
            );
            return { counts: counts, charts: charts };
        })
        .then((res) => {
            //generate countup
            if (res.counts.length > 0) {
                setTimeout(() => {
                    res.counts.forEach((x, i) => {
                        const countUp = new CountUp(x.id, x.val, {
                            suffix: x.suf,
                        });
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
        })
        .catch((err) => {
            handleNotFound();
        });
};

renderTextVisual();
