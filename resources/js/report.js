import createElement from "./app";
const axios = window.axios;
import { getCharts, getSingleCards } from "./charts.js";
import { targetAndLastSync } from "./util";
import { renderRsrTableTemplate, renderRsrTable } from "./rsrDatatables.js";

const baseurl = $("meta[name=path]").attr("content");

const renderReportForm = () => {
    axios
        .get(baseurl + "/api/flow/partnerships")
        .then((res) => {
            res.data.forEach((c) => {
                let country_opt =
                    '<option value="' + c.id + '">' + c.name + "</option>";
                $("#country-level").append(country_opt);
                $("#profile-country-level").append(country_opt);
                if (c.childrens.length > 0) {
                    c.childrens.forEach((p) => {
                        $("#partnership-level").append(
                            '<option class="partnerships ppp-' +
                                c.id +
                                '" value="' +
                                p.id +
                                '">' +
                                p.name +
                                "</option>"
                        );
                        $("#profile-partnership-level").append(
                            '<option class="profile-partnerships profile-ppp-' +
                                c.id +
                                '" value="' +
                                p.id +
                                '">' +
                                p.name +
                                "</option>"
                        );
                    });
                }
            });
            return res.data;
        })
        .then((res) => {
            $(".partnerships").hide("fast");
        });
};

targetAndLastSync().then((el) => {
    $("#last-sync-temp").append(el);
});

$("main").append(
    <div>
        <div class="row" id="zero-row"></div>
        <div class="row" id="first-row">
            <div class="col-md-12">
                <div class="card">
                    <div id="last-sync-temp"></div>
                    <div class="card-header">
                        <h3>Generate Internal Report</h3>
                    </div>
                    <div class="card-body">
                        <div
                            class="d-flex justify-content-center align-items-center"
                            id="loader-test"
                        >
                            <form class="row form-inline">
                                <div class="col-auto form-group required">
                                    <label class="control-label"></label>
                                    <select
                                        id="country-level"
                                        class="form-control"
                                    >
                                        <option value="0" selected>
                                            Select Country (CTL)
                                        </option>
                                    </select>
                                </div>
                                <div class="col-auto form-group">
                                    <label class="control-label"></label>
                                    <select
                                        id="partnership-level"
                                        class="form-control"
                                    >
                                        <option value="0" selected>
                                            Select Partnership (PF)
                                        </option>
                                    </select>
                                </div>
                                <div class="col-auto form-group required">
                                    <label class="control-label"></label>
                                    <select id="year" class="form-control">
                                        <option value="0" selected>
                                            Year
                                        </option>
                                        <option value="2021">2021</option>
                                        <option value="2022">2022</option>
                                        <option value="2023">2023</option>
                                    </select>
                                </div>
                                <div class="col-auto form-group required">
                                    <label class="control-label"></label>
                                    <select id="selector" class="form-control">
                                        <option value="0" selected>
                                            Report Selector
                                        </option>
                                        <option value="1">Report 1</option>
                                        <option value="2">Report 2</option>
                                        <option value="3">Report 3</option>
                                    </select>
                                </div>
                                <div class="col-auto">
                                    <button
                                        id="generate-word-report"
                                        type="button"
                                        class="btn btn-sm btn-primary"
                                        style="height:35px;"
                                    >
                                        Download
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <hr />
        <div class="row" id="second-row">
            <div class="col-md-12">
                <div class="card">
                    <div id="last-sync-temp"></div>
                    <div class="card-header">
                        <h3>Generate Partnership Profile Report</h3>
                    </div>
                    <div class="card-body">
                        <div
                            class="d-flex justify-content-center align-items-center"
                            id="loader-test"
                        >
                            <form class="row form-inline">
                                <div class="col-auto form-group">
                                    <label class="control-label"></label>
                                    <select
                                        id="profile-country-level"
                                        class="form-control"
                                    >
                                        <option value="0" selected>
                                            Select Country
                                        </option>
                                    </select>
                                </div>
                                <div class="col-auto form-group">
                                    <label class="control-label"></label>
                                    <select
                                        id="profile-partnership-level"
                                        class="form-control"
                                    >
                                        <option value="0" selected>
                                            Select Partnership
                                        </option>
                                    </select>
                                </div>
                                <div class="col-auto">
                                    <button
                                        id="generate-partnership-profile-report"
                                        type="button"
                                        class="btn btn-sm btn-primary"
                                        style="height:35px;"
                                    >
                                        Download
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <hr />
    </div>
);
renderReportForm();

$("main").append("<div class='row' id='total-activities-row'></div>");
/* total-activities Row Total Activities chart */
// getCharts("report/total-activities", "total-activities-row", "12");

// Rsr Datatables
renderRsrTableTemplate("datatables", "65vh");
renderRsrTable(["0", "0"].join("/"), baseurl, "datatables").then((res) => {
    // refine footer style
    $(".tmp-footer")[0].style.position = "relative";
});

// All of Event Function
$("#country-level").on("change", () => {
    let country_id = $("#country-level").val();
    $("#partnership-level").val(0);
    $(".partnerships").hide("fast");
    $(".ppp-" + country_id).show("fast");
});

$("#profile-country-level").on("change", () => {
    let country_id = $("#profile-country-level").val();
    $("#profile-partnership-level").val(0);
    $(".profile-partnerships").hide("fast");
    $(".profile-ppp-" + country_id).show("fast");
});

const showModalError = (response) => {
    $("#myModalAuthTitle").html("Error");
    $("#myModalAuthBody").html(
        <div class="alert alert-warning" role="alert">
            {response}
        </div>
    );
    $("#myModalAuth").modal({ backdrop: "static", keyboard: false });
};

$("#generate-word-report").on("click", () => {
    let country_id = $("#country-level").val();
    let partnership_id = $("#partnership-level").val();
    let year = $("#year").val();
    let selector = $("#selector").val();
    if (
        Number(country_id) === 0 ||
        Number(year) === 0 ||
        Number(selector) === 0
    ) {
        showModalError("Country, Year, and Report Selector are required.");
        return;
    }
    // Loading
    $("#myModalBtnClose").hide();
    $("#myModalAuthTitle").html("Please Wait");
    $("#myModalAuthBody").html(
        <div>
            <br />
            <div class="d-flex justify-content-center" id="loader-spinner">
                <div
                    class="spinner-border text-primary loader-spinner"
                    role="status"
                >
                    <span class="sr-only">Loading...</span>
                </div>
            </div>
            <br />
        </div>
    );
    $("#myModalAuth").modal({ backdrop: "static", keyboard: false });

    axios
        .get(
            baseurl +
                "/api/rsr/word-report/" +
                country_id +
                "/" +
                partnership_id +
                "/" +
                year +
                "/" +
                selector
        )
        .then((res) => {
            $("#loader-spinner").remove();
            $("#myModalAuthTitle").html("Report ready to download");
            $("#myModalAuthBody").html(
                <a target="_blank" href={res.data.link}>
                    <button type="button" class="btn btn-primary">
                        {" "}
                        Download Report
                    </button>
                </a>
            );
            $("#myModalBtnClose").show();
        })
        .catch((err) => {
            console.error(err);
            $("#loader-spinner").remove();
            $("#myModalAuthTitle").html("Error");
            $("#myModalAuthBody").html(
                <div class="alert alert-danger" role="alert">
                    {err.response.data}
                </div>
            );
            $("#myModalBtnClose").show();
        });
});

// PROFILE REPORT GENERATOR
const generatePartnershipChart = async (endpoints) => {
    await new Promise((resolve) => {
        // put a div (hidden) to store the charts for pdf report
        $("#zero-row").append(
            "<div id='chart-report-container' class='invisible' style='margin-top:-999rem; width: 100%;'></div>"
        );

        $("#chart-report-container").append(
            "<hr><div class='row' id='third-row'></div>"
        );
        getSingleCards("report/reachreact/card/" + endpoints, "third-row");

        $("#chart-report-container").append(
            "<hr><div class='row' id='fourth-row'></div>"
        );
        getCharts("report/workstream/" + endpoints, "fourth-row", "12");

        $("#chart-report-container").append(
            "<hr><div class='row' id='fifth-row'></div>"
        );
        getCharts("report/program-theme/" + endpoints, "fifth-row", "12");

        $("#chart-report-container").append(
            "<hr><div class='row' id='sixth-row'></div>"
        );
        getCharts("report/target-audience/" + endpoints, "sixth-row", "12");

        $("#chart-report-container").append(
            "<hr><div class='row' id='seventh-row'></div>"
        );
        getCharts(
            "reachreact/gender/" + endpoints,
            "seventh-row",
            "12",
            "age-category"
        );
        setTimeout(() => {
            resolve(console.log("generated"));
        }, 15000);
    });
    return;
};

$("#generate-partnership-profile-report").on("click", () => {
    let country = $("#profile-country-level").val();
    let code = $("#profile-partnership-level").val();
    let pid = code == 0 ? country : code;
    let todayDate = new Date().toISOString().slice(0, 10);
    const endpoints = [country, code, "2019-11-01", todayDate].join("/");

    // Loading
    $("#myModalBtnClose").hide();
    $("#myModalAuthTitle").html("Please Wait");
    $("#myModalAuthBody").html(
        '<br>\
        <div class="d-flex justify-content-center" id="loader-spinner">\
            <div class="spinner-border text-primary loader-spinner" role="status">\
                <span class="sr-only">Loading...</span>\
            </div>\
        </div><br>'
    );
    $("#myModalAuth").modal({ backdrop: "static", keyboard: false });

    generatePartnershipChart(endpoints).then((res) => {
        // let iframe = document.getElementsByTagName("iframe");
        let token = window.parent.document.querySelector(
            'meta[name="csrf-token"]'
        ).content;
        let charts = document.getElementById("chart-report-container");
        let canvas = charts.getElementsByTagName("canvas");
        let canvasTitles = charts.getElementsByClassName("card-header");
        let formData = new FormData();

        let country = $("#profile-country-level option:selected").text().trim();
        let partnership = $("#profile-partnership-level option:selected")
            .text()
            .trim();
        let filename =
            partnership === "Select Partnership"
                ? country === "Select Country"
                    ? "2SCALE Program"
                    : country
                : partnership;
        filename = filename + " - " + moment().format("MMM D, YYYY");
        formData.set("partnership_id", pid);
        formData.set("filename", filename);
        formData.set("date", todayDate);

        let cards = document.getElementById("third-row-value");
        formData.set(
            "card",
            cards.getAttribute("dataTitle") +
                "|" +
                cards.getAttribute("dataValue")
        );

        let image = 0;
        let imgWidth = [];
        let minWidth = [];
        do {
            let image_url = canvas[image].toDataURL("image/png");
            formData.append("images[]", image_url);
            imgWidth.push(parseInt(canvas[image].width));
            minWidth.push(parseInt(canvas[image].width));
            image++;
        } while (image < canvas.length);

        minWidth = minWidth.sort((a, b) => a - b)[0];
        imgWidth.forEach((x) => {
            let column = Math.round(x / minWidth);
            formData.append("columns[]", column);
        });

        for (let index = 0; index < canvasTitles.length; index++) {
            formData.append("titles[]", canvasTitles[index].textContent);
        }

        setTimeout(() => {
            axios
                .post("rsr-report", formData, {
                    "Content-Type": "multipart/form-data",
                    "X-CSRF-TOKEN": token,
                })
                .then((res) => {
                    $("#loader-spinner").remove();
                    $("#myModalAuthTitle").html("Report ready to download");
                    $("#myModalAuthBody").html(
                        '<a target="_blank" href="' +
                            res.data +
                            '">\
                    <button type="button" class="btn btn-primary"> Download Report</button>\
                </a>'
                    );
                    $("#myModalBtnClose").show();
                })
                .catch((err) => {
                    console.log("internal server error", err);
                    $("#loader-spinner").remove();
                    $("#myModalAuthTitle").html("Error");
                    $("#myModalAuthBody").html(
                        '<div class="alert alert-danger" role="alert">Please try again later!</div>'
                    );
                    $("#myModalBtnClose").show();
                });
        }, 10000);
    });
});
// EOL PROFILE REPORT GENERATOR
