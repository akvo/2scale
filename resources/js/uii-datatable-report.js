import { renderRsrTableTemplate, renderRsrTable } from "./rsrDatatables.js";

const baseurl = $("meta[name=path]").attr("content");
const country_id = $("meta[name='country']").attr("content");
const partnership_id = $("meta[name='partnership']").attr("content");
const endpoints = [country_id || 0, partnership_id || 0].join("/");

// Rsr Datatables / UII Report
renderRsrTableTemplate("datatables", "35px", "").then((res) => {
    renderRsrTable(endpoints, baseurl, "datatables").then((res) => {
        // set parent iframe height
        parent.window.document.getElementById(
            "uii-report-filter"
        ).style.visibility = "visible";
        parent.window.document.getElementById("uii-report-data-frame").height =
            document.body.scrollHeight * 2 + "px";
    });
});
