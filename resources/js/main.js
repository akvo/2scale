import { staticText, gradient, titleCase } from "./util.js";
import Dexie from "dexie";
const selectorbar = $(".selector-bar").length === 1 ? 60 : 0;
const navbar = $("nav.nav").length === 1 ? 56 : 3;
const axios = require("axios");
let iframeheight = window.innerHeight - (navbar + selectorbar);
if (window.location.pathname === "/") {
    iframeheight = iframeheight / 2 + iframeheight + 250;
}

$("#akvo-flow-web").attr("height", iframeheight);
$("#data-frame").attr("height", iframeheight);

/* Akvo Flow Web API */
let prev = "init";
$("#survey-parent").on("change.bs.select", (e) => {
    $("button.dropdown-toggle").click();
    //$('button.btn.dropdown-toggle.btn-pink').dropdown('update');
    $(".filter-option-inner-inner").text("Select Questionnaire");
    let el = "." + e.target.value;
    $(el).show(1);
    if (prev !== "init") {
        $(prev).hide(1);
    }
    prev = el;
});

$("#select-survey").on("change.bs.select", (e) => {
    let url = e.target.attributes["data-url"].value + "/" + e.target.value;
    $("#akvo-flow-web").attr("src", url);
});

const startdate = moment().subtract(2, "year").startOf("month");
const enddate = moment();

$(function () {
    $('input[name="daterange"]').daterangepicker(
        {
            startDate: startdate,
            endDate: enddate,
            opens: "center",
            ranges: {
                Today: [moment(), moment()],
                Yesterday: [
                    moment().subtract(1, "days"),
                    moment().subtract(1, "days"),
                ],
                "Last 7 Days": [moment().subtract(6, "days"), moment()],
                "Last 30 Days": [moment().subtract(29, "days"), moment()],
                "This Month": [
                    moment().startOf("month"),
                    moment().endOf("month"),
                ],
                "Last Month": [
                    moment().subtract(1, "month").startOf("month"),
                    moment().subtract(1, "month").endOf("month"),
                ],
                "Last Year": [
                    moment().subtract(1, "years").startOf("month"),
                    moment(),
                ],
                "All Data": [
                    moment().subtract(10, "years").startOf("month"),
                    moment(),
                ],
            },
        },
        function (start, end, label) {
            const start_date = start.format("YYYY-MM-DD");
            const end_date = start.format("YYYY-MM-DD");
        }
    );
});

/** Database Page */
const changeSurveyPartnershipCode = (country, data, selectedId = false) => {
    $("#select-partnership-survey option").remove();
    let html = `<option data-tokens="all" value="" data-id="0">Select Partnership</options>`;
    if (country !== "") {
        data.forEach((d, i) => {
            let selected = d.id === selectedId ? "selected" : "";
            html +=
                `<option ` +
                selected +
                ` data-tokens="` +
                d.name +
                `" data-id="` +
                d.id +
                `" value="` +
                d.id +
                `" style="max-width: 600px;">` +
                titleCase(d.name) +
                `</option>`;
        });
    }
    $("#select-partnership-survey").append(html);
    $("#select-partnership-survey").selectpicker("refresh");
};

$("#select-country-survey").on("change", (data) => {
    if (data.target.value !== "") {
        axios.get("/api/partnership/" + data.target.value).then((res) => {
            changeSurveyPartnershipCode(data.target.value, res.data);
        });
        return;
    }
    $("#select-partnership-survey option").remove();
    $("#select-partnership-survey").append(
        `<option data-tokens="all" value="" data-id="0">Select Partnership</options>`
    );
    $("#select-partnership-survey").selectpicker("refresh");
    return;
});

/* DataTables API */
$(".btn.dropdown-toggle.btn-light").removeClass("btn-light");
$(".btn.dropdown-toggle.btn-light").addClass("btn-primary");

$("#btn-data-inspect").click(() => {
    const form_select = $("#select-database-survey").val();
    let url = window.location.origin + "/frame/database/" + form_select;

    let date = $('input[name="daterange"]')[0].value.split(" - ");
    date = date.map((x) => {
        return moment(x).format("YYYY-MM-DD");
    });

    const country_select = $("#select-country-survey").val();
    if (country_select) {
        url += "/" + country_select;
    }
    const partnership_select = $("#select-partnership-survey").val();
    if (partnership_select) {
        url += "/" + partnership_select;
    }
    if (form_select === "") {
        $("#notable").modal("show");
    }
    if (form_select !== "") {
        $("#data-frame").attr("src", url + "/" + date[0] + "/" + date[1]);
    }
});

$("#btn-data-download").on("click", () => {
    let iframe = $("#data-frame");
    $("#btn-data-inspect").click();
    var checkTable = setInterval(() => {
        let table = iframe.contents().find("table");
        let btnExcel = iframe.contents().find(".buttons-excel");
        console.info("interval");
        if (table.length > 0 && btnExcel.length > 0) {
            btnExcel.click();
            clearInterval(checkTable);
        }
    }, 1000);
});
/** EOL Database page */

$("#generate-reachreact-page").on("click", () => {
    let params = selectPicker();
    let date = $('input[name="daterange"]')[0].value.split(" - ");
    date = date.map((x) => {
        params = [...params, moment(x).format("YYYY-MM-DD")];
        return moment(x).format("YYYY-MM-DD");
    });
    params = params.join("/");
    $("#data-frame").attr("src", "/frame/reachreact/" + params);
});

/* Partnership Page */
const changePartnershipCode = (country, data, selectedId = false) => {
    $("#partnership-code option").remove();
    let html = `<option data-tokens="all" value="0" data-id="0">Select Partnership</options>`;
    if (country !== "0") {
        data.forEach((d, i) => {
            let selected = d.id === selectedId ? "selected" : "";
            html +=
                `<option ` +
                selected +
                ` data-tokens="` +
                d.name +
                `" data-id="` +
                d.id +
                `" value="` +
                d.id +
                `" style="max-width: 600px;">` +
                titleCase(d.name) +
                `</option>`;
        });
    }
    // html += `<option data-tokens="all" value="0" data-id="0">All Partnerships</options>`;
    $("#partnership-code").append(html);
    $("#partnership-code").selectpicker("refresh");
};

$("#partnership-country").on("change", (data) => {
    if (data.target.value !== "") {
        axios.get("/api/partnership/" + data.target.value).then((res) => {
            changePartnershipCode(data.target.value, res.data);
        });
        if (data.target.value == 0) {
            $("#data-frame").attr("src", "/frame/partnership/0/0");
        }
        return;
    }
    $("#partnership-code option").remove();
    $("#partnership-code").selectpicker("refresh");
    return;
});

const loadDefaultPartnership = async () => {
    let country_id = $("#partnership-country").val();
    await axios.get("/api/partnership/" + country_id).then((res) => {
        changePartnershipCode(country_id, res.data, 11);
    });

    let partnership_id = $("#partnership-code").val();
    $("#data-frame").attr(
        "src",
        "/frame/partnership/" + country_id + "/" + partnership_id
    );
};

if (window.location.pathname === "/partnership") {
    $("#partnership-code").on("change", (data) => {
        let country_id = $("#partnership-country").val();
        if (data.target.value !== "") {
            $("#data-frame").attr(
                "src",
                "/frame/partnership/" + country_id + "/" + data.target.value
            );
            return;
        }
        return;
    });
    // Disable load default partnership
    // loadDefaultPartnership();
}

// * Old partnership page for generate pdf report
$("#generate-partnership-page").on("click", () => {
    generatePartnershipChart();
});

const generatePartnershipChart = async () => {
    await new Promise((resolve) => {
        let params = selectPicker();
        let date = $('input[name="daterange"]')[0].value.split(" - ");
        date = date.map((x) => {
            params = [...params, moment(x).format("YYYY-MM-DD")];
            return moment(x).format("YYYY-MM-DD");
        });
        params = params.join("/");
        $("#data-frame").attr("src", "/frame/partnership/" + params);
        setTimeout(() => {
            resolve(console.info("generated"));
        }, 15000);
    });
    return;
};

const selectPicker = () => {
    let params = [];
    $(".selectpicker").each((d, i) => {
        let value = $(i).val();
        if (value === undefined || value === "") {
            value = 0;
        }
        params = [...params, value];
    });
    return params;
};

// PROFILE GENERATOR
$("#generate-report-link").on("click", () => {
    let country = $("#partnership-country").val();
    let code = $("#partnership-code").val();
    let pid = code == 0 ? country : code;

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

    generatePartnershipChart().then((res) => {
        let todayDate = new Date().toISOString().slice(0, 10);
        let iframe = document.getElementsByTagName("iframe");
        let token = document.querySelector('meta[name="csrf-token"]').content;
        let charts = iframe[0].contentWindow.document.getElementById(
            "chart-report-container"
        );
        let canvas = charts.getElementsByTagName("canvas");
        let canvasTitles = charts.getElementsByClassName("card-header");
        let formData = new FormData();

        let country = $("#partnership-country option:selected").text().trim();
        let partnership = $("#partnership-code option:selected").text().trim();
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

        let cards =
            iframe[0].contentWindow.document.getElementById("third-row-value");
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
                .post("api/rsr-report", formData, {
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
                    console.info("internal server error", err);
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
/* EOL Partnership Page */

const authMessage = () => {
    let authModal = $("#authError").attr("data");
    if (authModal === "not_authorized") {
        $("#myModalAuthTitle").text("Not Authorized");
        $("#myModalAuth").modal("toggle");
        $("#myModalAuthBody").text(
            "You're not authorized, please contact your organization to request an access!"
        );
    }

    if (authModal === "email") {
        $("#myModalAuthTitle").text("Please verify your email");
        $("#myModalAuth").modal("toggle");
        $("#myModalAuthBody").text(
            "An email verification has been sent to your email, please verify your email first!"
        );
    }
};

const revalidate = () => {
    // const now = moment();
    const now = new Date();
    let cachetime = localStorage.getItem("cache-time");
    let cache_version = document
        .getElementsByName("cache-version")[0]
        .getAttribute("value");
    let current_version = localStorage.getItem("cache-version");
    // cachetime = cachetime !== null ? moment(cachetime) : moment().subtract(1, 'days');
    cachetime =
        cachetime !== null
            ? new Date(parseInt(cachetime) + 60 * 60 * 1000)
            : new Date(0);
    if (now > cachetime || cache_version !== current_version) {
        console.info("CLEAR CACHE");
        localStorage.clear();
        Dexie.delete("2scale");
        // let tomorrow = moment().add(1, 'days').format("YYYY-MM-DD");
        // localStorage.setItem('cache-time', tomorrow);
        localStorage.setItem("cache-time", now.getTime());
        localStorage.setItem("cache-version", cache_version);
    }
    if (now < cachetime && cache_version === current_version) {
        console.info("USING CACHED");
    }
};

revalidate();
authMessage();

// Lumen Dashboard
$("#lumen-uii-dropdown").on("change", (data) => {
    if (data.target.value !== "") {
        const uii = data.target.value;
        $("#lumen-dashboard-data-frame").attr(
            "src",
            "/frame/lumen-dashboard/" + uii
        );
        return;
    }
    return;
});
